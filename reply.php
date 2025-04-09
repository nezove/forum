<?php
// api/reply.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Проверка AJAX запроса
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    http_response_code(403);
    exit;
}

// Получение данных из POST запроса
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['topic_id']) || !is_numeric($data['topic_id']) || !isset($data['content']) || empty($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

$topic_id = (int)$data['topic_id'];
$content = trim($data['content']);

// Создание ответа
$result = create_post($topic_id, $content);

if ($result['success']) {
    // Получаем данные созданного поста для отображения
    $db = Database::getInstance();
    $post = $db->fetch(
        "SELECT p.*, u.username, u.avatar, u.created_at as joined,
         (SELECT COUNT(*) FROM posts WHERE user_id = p.user_id) as post_count
         FROM posts p
         JOIN users u ON p.user_id = u.id
         WHERE p.id = :id",
        ['id' => $result['post_id']]
    );
    
    // Форматируем дату
    $post['created_at'] = format_date($post['created_at'], 'full');
    $post['joined'] = format_date($post['joined'], 'full');
    
    // Форматируем содержимое
    $post['content'] = format_post_content($post['content']);
    
    $result['post'] = $post;
}

// Возвращаем результат
echo json_encode($result);