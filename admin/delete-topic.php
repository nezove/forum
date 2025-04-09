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

// Получение категории
$category_id = $topic['category_id'];

// Удаление темы
$db->query(
    "DELETE FROM topics WHERE id = :id",
    ['id' => $topic_id]
);

// Перенаправление к категории
header('Location: ../category.php?id=' . $category_id);
exit;