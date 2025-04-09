<?php
// api/like.php
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

if (!isset($data['post_id']) || !is_numeric($data['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

$post_id = (int)$data['post_id'];

// Обработка лайка
$result = toggle_like($post_id);

// Возвращаем результат
echo json_encode($result);