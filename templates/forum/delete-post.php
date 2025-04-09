<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

// Проверка наличия ID сообщения
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Получение информации о сообщении
$db = Database::getInstance();
$post = $db->fetch(
    "SELECT p.*, t.id as topic_id, t.user_id as topic_author_id, 
     (SELECT COUNT(*) FROM posts WHERE topic_id = p.topic_id) as post_count,
     (SELECT MIN(id) FROM posts WHERE topic_id = p.topic_id) as first_post_id
     FROM posts p 
     JOIN topics t ON p.topic_id = t.id 
     WHERE p.id = :id",
    ['id' => $post_id]
);

if (!$post) {
    header('Location: index.php');
    exit;
}

// Проверка прав на удаление (автор сообщения или администратор)
$is_admin = Auth::check() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$is_author = $post['user_id'] == $_SESSION['user_id'];
$is_first_post = $post['id'] == $post['first_post_id'];

// Нельзя удалить первое сообщение темы обычному пользователю
if ($is_first_post && !$is_admin) {
    header('Location: topic.php?id=' . $post['topic_id']);
    exit;
}

if (!$is_admin && !$is_author) {
    header('Location: topic.php?id=' . $post['topic_id']);
    exit;
}

// Если это первое сообщение темы, то удаляем всю тему
if ($is_first_post) {
    // Удаление темы и всех связанных сообщений
    $db->query(
        "DELETE FROM topics WHERE id = :topic_id",
        ['topic_id' => $post['topic_id']]
    );
    
    // Перенаправление на категорию
    header('Location: category.php?id=' . $post['category_id']);
    exit;
} else {
    // Удаление обычного сообщения
    $db->query(
        "DELETE FROM posts WHERE id = :id",
        ['id' => $post_id]
    );
    
    // Обновление даты последнего ответа в теме
    $db->query(
        "UPDATE topics SET last_reply_at = (
         SELECT MAX(created_at) FROM posts WHERE topic_id = :topic_id
         ) WHERE id = :topic_id",
        ['topic_id' => $post['topic_id']]
    );
    
    // Перенаправление на тему
    header('Location: topic.php?id=' . $post['topic_id']);
    exit;
}