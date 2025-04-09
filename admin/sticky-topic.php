<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Проверка прав администратора
if (!Auth::check() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

// Проверка наличия ID темы
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../admin.php');
    exit;
}

$topic_id = (int)$_GET['id'];

// Получение информации о теме
$db = Database::getInstance();
$topic = $db->fetch(
    "SELECT * FROM topics WHERE id = :id",
    ['id' => $topic_id]
);

if (!$topic) {
    header('Location: ../admin.php');
    exit;
}

// Закрепление темы
$db->update(
    'topics',
    ['is_sticky' => 1],
    'id = :id',
    ['id' => $topic_id]
);

// Перенаправление обратно к теме
header('Location: ../topic.php?id=' . $topic_id);
exit;