<?php
// api/profile.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Проверка AJAX запроса
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    http_response_code(403);
    exit;
}

// Проверка авторизации
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

// Получение данных из POST запроса
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

$action = $data['action'];
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'update_bio':
        if (!isset($data['bio'])) {
            echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
            exit;
        }
        
        $bio = trim($data['bio']);
        
        $db = Database::getInstance();
        $db->update(
            'users',
            ['bio' => $bio],
            'id = :id',
            ['id' => $user_id]
        );
        
        echo json_encode(['success' => true, 'message' => 'Информация о себе успешно обновлена']);
        break;
        
    case 'update_theme':
        if (!isset($data['theme']) || !in_array($data['theme'], ['light', 'dark'])) {
            echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
            exit;
        }
        
        $theme = $data['theme'];
        
        $db = Database::getInstance();
        $db->update(
            'users',
            ['theme' => $theme],
            'id = :id',
            ['id' => $user_id]
        );
        
        $_SESSION['theme'] = $theme;
        
        echo json_encode(['success' => true, 'message' => 'Тема успешно обновлена']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
        break;
}