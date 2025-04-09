<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Выход пользователя
Auth::logout();

// Перенаправление на главную
header('Location: index.php');
exit;