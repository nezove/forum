<?php
require_once 'db.php';

class Auth {
    // Регистрация нового пользователя
    public static function register($username, $email, $password) {
        $db = Database::getInstance();
        
        // Проверка существования пользователя
        $existingUser = $db->fetch(
            "SELECT * FROM users WHERE username = :username OR email = :email", 
            ['username' => $username, 'email' => $email]
        );
        
        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                return ['success' => false, 'message' => 'Пользователь с таким именем уже существует'];
            } else {
                return ['success' => false, 'message' => 'Пользователь с таким email уже существует'];
            }
        }
        
        // Хеширование пароля
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Добавление пользователя
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash
        ]);
        
        if ($userId) {
            // Автоматическая авторизация
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            
            return ['success' => true, 'message' => 'Регистрация успешна', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Ошибка при регистрации'];
        }
    }
    
    // Авторизация пользователя
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        $user = $db->fetch(
            "SELECT * FROM users WHERE username = :username OR email = :email", 
            ['username' => $username, 'email' => $username]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
        }
        
        // Обновление времени последнего посещения
        $db->update('users', 
            ['last_visit' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $user['id']]
        );
        
        // Установка сессии
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['theme'] = $user['theme'];
        
        return ['success' => true, 'message' => 'Вход выполнен успешно', 'user' => $user];
    }
    
    // Выход пользователя
    public static function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Выход выполнен успешно'];
    }
    
    // Проверка авторизации
    public static function check() {
        return isset($_SESSION['user_id']);
    }
    
    // Получение текущего пользователя
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT * FROM users WHERE id = :id", 
            ['id' => $_SESSION['user_id']]
        );
    }
    
    // Восстановление пароля - создание токена
    public static function createResetToken($email) {
        $db = Database::getInstance();
        
        $user = $db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь с таким email не найден'];
        }
        
        // Создание уникального токена
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $db->update('users', 
            ['reset_token' => $token, 'reset_expires' => $expires], 
            'id = :id', 
            ['id' => $user['id']]
        );
        
        return [
            'success' => true, 
            'message' => 'Токен для сброса пароля создан', 
            'token' => $token,
            'email' => $email
        ];
    }
    
    // Восстановление пароля - проверка токена
    public static function verifyResetToken($token) {
        $db = Database::getInstance();
        
        $user = $db->fetch(
            "SELECT * FROM users WHERE reset_token = :token AND reset_expires > NOW()", 
            ['token' => $token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Недействительный или истекший токен'];
        }
        
        return ['success' => true, 'user_id' => $user['id']];
    }
    
    // Восстановление пароля - смена пароля
    public static function resetPassword($token, $password) {
        $verification = self::verifyResetToken($token);
        
        if (!$verification['success']) {
            return $verification;
        }
        
        $db = Database::getInstance();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $db->update('users', 
            [
                'password' => $passwordHash, 
                'reset_token' => null, 
                'reset_expires' => null
            ], 
            'id = :id', 
            ['id' => $verification['user_id']]
        );
        
        return ['success' => true, 'message' => 'Пароль успешно изменен'];
    }
}