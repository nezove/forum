<?php
// Конфигурация базы данных
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bbb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Конфигурация сайта
define('SITE_NAME', 'Современный форум');
define('SITE_URL', 'http://kk.lol');

// Настройки сессии
session_start();

// Временная зона
date_default_timezone_set('Europe/Moscow');

// Обработка ошибок (отключить на продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);