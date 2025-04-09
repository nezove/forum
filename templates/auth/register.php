<?php
$page_title = 'Регистрация';
require_once 'includes/header.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$errors = [];

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Валидация
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Имя пользователя должно содержать не менее 3 символов';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Имя пользователя не должно превышать 50 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $errors[] = 'Имя пользователя может содержать только буквы, цифры, дефис и подчеркивание';
    }
    
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!validate_email($email)) {
        $errors[] = 'Введите корректный email';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать не менее 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    // Если ошибок нет, регистрируем пользователя
    if (empty($errors)) {
        $result = Auth::register($username, $email, $password);
        
        if ($result['success']) {
            // Перенаправление на главную
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Регистрация</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="username" class="form-label">Имя пользователя</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required autofocus>
                        <div class="form-text">Имя пользователя должно содержать от 3 до 50 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <div class="form-text">Пароль должен содержать не менее 6 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <div class="auth-links">
                    <a href="login.php">Уже есть аккаунт? Войти</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>