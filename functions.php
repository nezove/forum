<?php
require_once 'db.php';
require_once 'auth.php';

// Генерация хлебных крошек
function generate_breadcrumbs($items) {
    $html = '<nav class="breadcrumbs" aria-label="Навигация">';
    $html .= '<ol>';
    
    foreach ($items as $index => $item) {
        $isLast = $index === count($items) - 1;
        
        if ($isLast) {
            $html .= '<li class="breadcrumb-item current" aria-current="page">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item">';
            $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a>';
            $html .= '</li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

// Получение категорий
function get_categories($parent_id = null) {
    $db = Database::getInstance();
    
    $params = [];
    $whereClause = "WHERE parent_id " . ($parent_id === null ? "IS NULL" : "= :parent_id");
    
    if ($parent_id !== null) {
        $params['parent_id'] = $parent_id;
    }
    
    return $db->fetchAll(
        "SELECT * FROM categories {$whereClause} ORDER BY sort_order ASC",
        $params
    );
}

// Получение всех родительских категорий для определенной категории
function get_category_parents($category_id, &$parents = []) {
    $db = Database::getInstance();
    $category = $db->fetch(
        "SELECT * FROM categories WHERE id = :id",
        ['id' => $category_id]
    );
    
    if ($category) {
        array_unshift($parents, $category);
        
        if ($category['parent_id']) {
            get_category_parents($category['parent_id'], $parents);
        }
    }
    
    return $parents;
}

// Получение данных категории
function get_category($id) {
    $db = Database::getInstance();
    return $db->fetch(
        "SELECT * FROM categories WHERE id = :id",
        ['id' => $id]
    );
}

// Получение тем в категории с пагинацией
function get_topics($category_id, $page = 1, $per_page = 20) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, u.avatar, 
         (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) AS replies,
         (SELECT MAX(p.created_at) FROM posts p WHERE p.topic_id = t.id) AS last_post_date,
         (SELECT u2.username FROM posts p2 
          JOIN users u2 ON p2.user_id = u2.id 
          WHERE p2.topic_id = t.id 
          ORDER BY p2.created_at DESC LIMIT 1) AS last_poster
         FROM topics t
         JOIN users u ON t.user_id = u.id
         WHERE t.category_id = :category_id
         ORDER BY t.is_sticky DESC, t.last_reply_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'category_id' => $category_id,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM topics WHERE category_id = :category_id",
        ['category_id' => $category_id]
    );
    
    return [
        'topics' => $topics,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Получение данных темы
function get_topic($id) {
    $db = Database::getInstance();
    
    // Увеличение счетчика просмотров
    $db->update(
        'topics',
        ['views' => new \PDO\PDOExpression('views + 1')],
        'id = :id',
        ['id' => $id]
    );
    
    return $db->fetch(
        "SELECT t.*, u.username, u.avatar, c.name as category_name, c.id as category_id
         FROM topics t
         JOIN users u ON t.user_id = u.id
         JOIN categories c ON t.category_id = c.id
         WHERE t.id = :id",
        ['id' => $id]
    );
}

// Получение сообщений в теме с пагинацией
function get_posts($topic_id, $page = 1, $per_page = 20) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $posts = $db->fetchAll(
        "SELECT p.*, u.username, u.avatar, u.created_at as user_joined,
         (SELECT COUNT(*) FROM posts WHERE user_id = p.user_id) as post_count,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :current_user) as user_liked
         FROM posts p
         JOIN users u ON p.user_id = u.id
         WHERE p.topic_id = :topic_id
         ORDER BY p.created_at ASC
         LIMIT :limit OFFSET :offset",
        [
            'topic_id' => $topic_id,
            'current_user' => Auth::check() ? $_SESSION['user_id'] : 0,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM posts WHERE topic_id = :topic_id",
        ['topic_id' => $topic_id]
    );
    
    return [
        'posts' => $posts,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Создание новой темы
function create_topic($title, $content, $category_id) {
    if (!Auth::check()) {
        return ['success' => false, 'message' => 'Необходимо авторизоваться'];
    }
    
    $db = Database::getInstance();
    
    // Создание темы
    $topic_id = $db->insert('topics', [
        'title' => $title,
        'user_id' => $_SESSION['user_id'],
        'category_id' => $category_id,
        'created_at' => date('Y-m-d H:i:s'),
        'last_reply_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$topic_id) {
        return ['success' => false, 'message' => 'Ошибка при создании темы'];
    }
    
    // Создание первого сообщения
    $post_id = $db->insert('posts', [
        'topic_id' => $topic_id,
        'user_id' => $_SESSION['user_id'],
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$post_id) {
        return ['success' => false, 'message' => 'Ошибка при создании сообщения'];
    }
    
    return [
        'success' => true, 
        'message' => 'Тема успешно создана', 
        'topic_id' => $topic_id
    ];
}

// Добавление ответа в тему
function create_post($topic_id, $content) {
    if (!Auth::check()) {
        return ['success' => false, 'message' => 'Необходимо авторизоваться'];
    }
    
    $db = Database::getInstance();
    
    // Проверка, не закрыта ли тема
    $topic = $db->fetch(
        "SELECT is_locked FROM topics WHERE id = :id",
        ['id' => $topic_id]
    );
    
    if ($topic['is_locked'] && !$_SESSION['is_admin']) {
        return ['success' => false, 'message' => 'Тема закрыта для ответов'];
    }
    
    // Создание сообщения
    $post_id = $db->insert('posts', [
        'topic_id' => $topic_id,
        'user_id' => $_SESSION['user_id'],
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$post_id) {
        return ['success' => false, 'message' => 'Ошибка при создании сообщения'];
    }
    
    // Обновление даты последнего ответа в теме
    $db->update(
        'topics',
        ['last_reply_at' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $topic_id]
    );
    
    // Создание уведомлений для автора темы и участников обсуждения
    create_reply_notifications($topic_id, $post_id, $_SESSION['user_id']);
    
    return [
        'success' => true, 
        'message' => 'Ответ успешно добавлен', 
        'post_id' => $post_id
    ];
}

// Создание уведомлений при ответе
function create_reply_notifications($topic_id, $post_id, $sender_id) {
    $db = Database::getInstance();
    
    // Получение темы и автора
    $topic = $db->fetch(
        "SELECT t.*, u.username FROM topics t JOIN users u ON t.user_id = u.id WHERE t.id = :id",
        ['id' => $topic_id]
    );
    
    // Получение уникальных пользователей, участвовавших в обсуждении
    $participants = $db->fetchAll(
        "SELECT DISTINCT user_id FROM posts WHERE topic_id = :topic_id AND user_id != :sender_id",
        ['topic_id' => $topic_id, 'sender_id' => $sender_id]
    );
    
    // Уведомление автора темы, если он не отправитель
    if ($topic['user_id'] != $sender_id) {
        $db->insert('notifications', [
            'user_id' => $topic['user_id'],
            'sender_id' => $sender_id,
            'type' => 'reply',
            'content' => "Новый ответ в вашей теме '{$topic['title']}'",
            'link' => "topic.php?id={$topic_id}&highlight={$post_id}",
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Уведомление других участников
    foreach ($participants as $participant) {
        // Пропустить автора темы (мы уже уведомили его выше)
        if ($participant['user_id'] == $topic['user_id']) {
            continue;
        }
        
        $db->insert('notifications', [
            'user_id' => $participant['user_id'],
            'sender_id' => $sender_id,
            'type' => 'reply',
            'content' => "Новый ответ в теме '{$topic['title']}'",
            'link' => "topic.php?id={$topic_id}&highlight={$post_id}",
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// Поставить/убрать лайк сообщению
function toggle_like($post_id) {
    if (!Auth::check()) {
        return ['success' => false, 'message' => 'Необходимо авторизоваться'];
    }
    
    $db = Database::getInstance();
    
    // Проверка существования лайка
    $like = $db->fetch(
        "SELECT * FROM likes WHERE post_id = :post_id AND user_id = :user_id",
        ['post_id' => $post_id, 'user_id' => $_SESSION['user_id']]
    );
    
    if ($like) {
        // Удаление лайка
        $db->query(
            "DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id",
            ['post_id' => $post_id, 'user_id' => $_SESSION['user_id']]
        );
        
        $action = 'unliked';
    } else {
        // Добавление лайка
        $db->insert('likes', [
            'post_id' => $post_id,
            'user_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Получение автора сообщения для уведомления
        $post = $db->fetch(
            "SELECT p.*, t.title as topic_title, t.id as topic_id 
             FROM posts p 
             JOIN topics t ON p.topic_id = t.id 
             WHERE p.id = :post_id",
            ['post_id' => $post_id]
        );
        
        // Создание уведомления для автора сообщения
        if ($post['user_id'] != $_SESSION['user_id']) {
            $db->insert('notifications', [
                'user_id' => $post['user_id'],
                'sender_id' => $_SESSION['user_id'],
                'type' => 'like',
                'content' => "Вашему сообщению в теме '{$post['topic_title']}' поставили лайк",
                'link' => "topic.php?id={$post['topic_id']}&highlight={$post_id}",
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $action = 'liked';
    }
    
    // Подсчет общего количества лайков для сообщения
    $count = $db->fetch(
        "SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id",
        ['post_id' => $post_id]
    );
    
    return [
        'success' => true,
        'action' => $action,
        'likes' => $count['count']
    ];
}

// Форматирование сообщения (замена ссылок, упоминаний и т.д.)
function format_post_content($content) {
    // Простое экранирование HTML
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    // Замена переводов строк на <br>
    $content = nl2br($content);
    
    // Преобразование URL в ссылки
    $urlPattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    $content = preg_replace($urlPattern, '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $content);
    
    // Обработка упоминаний (@username)
    $content = preg_replace_callback('/@([a-zA-Z0-9_-]+)/', function($matches) {
        $db = Database::getInstance();
        $username = $matches[1];
        
        $user = $db->fetch(
            "SELECT id FROM users WHERE username = :username",
            ['username' => $username]
        );
        
        if ($user) {
            return '<a href="profile.php?id=' . $user['id'] . '" class="mention">@' . $username . '</a>';
        }
        return '@' . $username;
    }, $content);
    
    return $content;
}

// Получение данных профиля пользователя
function get_user_profile($user_id) {
    $db = Database::getInstance();
    
    $user = $db->fetch(
        "SELECT u.*, 
         (SELECT COUNT(*) FROM topics WHERE user_id = u.id) as topic_count,
         (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
         (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = u.id) as likes_received
         FROM users u 
         WHERE u.id = :id",
        ['id' => $user_id]
    );
    
    return $user;
}

// Получение тем пользователя
function get_user_topics($user_id, $page = 1, $per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $topics = $db->fetchAll(
        "SELECT t.*, c.name as category_name,
         (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies
         FROM topics t
         JOIN categories c ON t.category_id = c.id
         WHERE t.user_id = :user_id
         ORDER BY t.created_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'user_id' => $user_id,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM topics WHERE user_id = :user_id",
        ['user_id' => $user_id]
    );
    
    return [
        'topics' => $topics,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Получение сообщений пользователя
function get_user_posts($user_id, $page = 1, $per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $posts = $db->fetchAll(
        "SELECT p.*, t.title as topic_title, t.id as topic_id,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count
         FROM posts p
         JOIN topics t ON p.topic_id = t.id
         WHERE p.user_id = :user_id
         ORDER BY p.created_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'user_id' => $user_id,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id",
        ['user_id' => $user_id]
    );
    
    return [
        'posts' => $posts,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Получение закладок пользователя
function get_user_bookmarks($user_id, $page = 1, $per_page = 10) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $bookmarks = $db->fetchAll(
        "SELECT t.*, c.name as category_name, b.created_at as bookmarked_at,
         u.username as author_name, 
         (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies,
         (SELECT MAX(p.created_at) FROM posts p WHERE p.topic_id = t.id) as last_post_date
         FROM bookmarks b
         JOIN topics t ON b.topic_id = t.id
         JOIN categories c ON t.category_id = c.id
         JOIN users u ON t.user_id = u.id
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'user_id' => $user_id,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM bookmarks WHERE user_id = :user_id",
        ['user_id' => $user_id]
    );
    
    return [
        'bookmarks' => $bookmarks,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Добавить/удалить закладку
function toggle_bookmark($topic_id) {
    if (!Auth::check()) {
        return ['success' => false, 'message' => 'Необходимо авторизоваться'];
    }
    
    $db = Database::getInstance();
    
    // Проверка существования закладки
    $bookmark = $db->fetch(
        "SELECT * FROM bookmarks WHERE topic_id = :topic_id AND user_id = :user_id",
        ['topic_id' => $topic_id, 'user_id' => $_SESSION['user_id']]
    );
    
    if ($bookmark) {
        // Удаление закладки
        $db->query(
            "DELETE FROM bookmarks WHERE topic_id = :topic_id AND user_id = :user_id",
            ['topic_id' => $topic_id, 'user_id' => $_SESSION['user_id']]
        );
        
        $action = 'removed';
    } else {
        // Добавление закладки
        $db->insert('bookmarks', [
            'topic_id' => $topic_id,
            'user_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $action = 'added';
    }
    
    return [
        'success' => true,
        'action' => $action
    ];
}

// Получение уведомлений пользователя
function get_user_notifications($user_id, $page = 1, $per_page = 20) {
    $db = Database::getInstance();
    $offset = ($page - 1) * $per_page;
    
    $notifications = $db->fetchAll(
        "SELECT n.*, u.username as sender_username, u.avatar as sender_avatar
         FROM notifications n
         LEFT JOIN users u ON n.sender_id = u.id
         WHERE n.user_id = :user_id
         ORDER BY n.created_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'user_id' => $user_id,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );
    
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id",
        ['user_id' => $user_id]
    );
    
    // Отметка уведомлений как прочитанных
    $db->update(
        'notifications',
        ['is_read' => true],
        'user_id = :user_id AND is_read = 0',
        ['user_id' => $user_id]
    );
    
    return [
        'notifications' => $notifications,
        'total' => $total['count'],
        'pages' => ceil($total['count'] / $per_page),
        'current_page' => $page
    ];
}

// Получение количества непрочитанных уведомлений
function get_unread_notifications_count($user_id) {
    $db = Database::getInstance();
    
    $result = $db->fetch(
        "SELECT COUNT(*) as count FROM notifications 
         WHERE user_id = :user_id AND is_read = 0",
        ['user_id' => $user_id]
    );
    
    return $result['count'];
}

// Генерация пагинации
function generate_pagination($current_page, $total_pages, $url_pattern) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Кнопка "Предыдущая"
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $prev_url = str_replace('{page}', $prev_page, $url_pattern);
        $html .= "<a href=\"{$prev_url}\" class=\"pagination-item\">«</a>";
    } else {
        $html .= "<span class=\"pagination-item disabled\">«</span>";
    }
    
    // Определение диапазона страниц для отображения
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    // Если мы находимся возле начала или конца, показываем больше страниц с другой стороны
    if ($start <= 3) {
        $end = min($total_pages, $start + 4);
    }
    if ($end >= $total_pages - 2) {
        $start = max(1, $end - 4);
    }
    
    // Отображение первой страницы и многоточия
    if ($start > 1) {
        $first_url = str_replace('{page}', 1, $url_pattern);
        $html .= "<a href=\"{$first_url}\" class=\"pagination-item\">1</a>";
        
        if ($start > 2) {
            $html .= "<span class=\"pagination-item dots\">...</span>";
        }
    }
    
    // Отображение страниц
    for ($i = $start; $i <= $end; $i++) {
        $page_url = str_replace('{page}', $i, $url_pattern);
        
        if ($i == $current_page) {
            $html .= "<span class=\"pagination-item active\">{$i}</span>";
        } else {
            $html .= "<a href=\"{$page_url}\" class=\"pagination-item\">{$i}</a>";
        }
    }
    
    // Отображение последней страницы и многоточия
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= "<span class=\"pagination-item dots\">...</span>";
        }
        
        $last_url = str_replace('{page}', $total_pages, $url_pattern);
        $html .= "<a href=\"{$last_url}\" class=\"pagination-item\">{$total_pages}</a>";
    }
    
    // Кнопка "Следующая"
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $next_url = str_replace('{page}', $next_page, $url_pattern);
        $html .= "<a href=\"{$next_url}\" class=\"pagination-item\">»</a>";
    } else {
        $html .= "<span class=\"pagination-item disabled\">»</span>";
    }
    
    $html .= '</div>';
    
    return $html;
}

// Форматирование даты
function format_date($date, $format = 'full') {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($format === 'relative') {
        if ($diff < 60) {
            return 'только что';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' ' . pluralize($minutes, 'минута', 'минуты', 'минут') . ' назад';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ' . pluralize($hours, 'час', 'часа', 'часов') . ' назад';
        } elseif ($diff < 172800) { // 2 дня
            return 'вчера в ' . date('H:i', $timestamp);
        } else {
            return date('d.m.Y в H:i', $timestamp);
        }
    } else {
        return date('d.m.Y в H:i', $timestamp);
    }
}

// Функция для правильного склонения слов
function pluralize($number, $one, $few, $many) {
    if ($number % 10 == 1 && $number % 100 != 11) {
        return $one;
    } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
        return $few;
    } else {
        return $many;
    }
}

// Валидация e-mail
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Безопасное перенаправление
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Генерация случайного имени файла для загрузки
function generate_filename($extension) {
    return md5(uniqid(rand(), true)) . '.' . $extension;
}

// Загрузка аватара
function upload_avatar($file, $user_id) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
    }
    
    // Проверка типа файла
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла. Разрешены только JPG, PNG и GIF'];
    }
    
    // Проверка размера файла (макс. 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Размер файла не должен превышать 2MB'];
    }
    
    // Определение расширения файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Генерация нового имени файла
    $new_filename = generate_filename($extension);
    $upload_path = 'assets/uploads/avatars/' . $new_filename;
    
    // Перемещение загруженного файла
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'message' => 'Ошибка при сохранении файла'];
    }
    
    // Обновление аватара пользователя в базе данных
    $db = Database::getInstance();
    
    // Получение старого аватара
    $user = $db->fetch(
        "SELECT avatar FROM users WHERE id = :id",
        ['id' => $user_id]
    );
    
    // Удаление старого аватара, если это не аватар по умолчанию
    if ($user['avatar'] !== 'default-avatar.png' && file_exists('assets/uploads/avatars/' . $user['avatar'])) {
        unlink('assets/uploads/avatars/' . $user['avatar']);
    }
    
    // Обновление аватара в базе данных
    $db->update(
        'users',
        ['avatar' => $new_filename],
        'id = :id',
        ['id' => $user_id]
    );
    
    return [
        'success' => true,
        'message' => 'Аватар успешно обновлен',
        'avatar' => $new_filename
    ];
}