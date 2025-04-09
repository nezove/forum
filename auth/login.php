<?php
$page_title = 'Вход';
require_once 'includes/header.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$errors = [];

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $errors[] = 'Введите имя пользователя и пароль';
    } else {
        $result = Auth::login($username, $password);
        
        if ($result['success']) {
            // Перенаправление
            $redirect_url = !empty($redirect) ? $redirect : 'index.php';
            header('Location: ' . $redirect_url);
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
                <h1 class="card-title">Вход</h1>
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
                
                <form method="post" action="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">
                    <div class="form-group">
                        <label for="username" class="form-label">Имя пользователя или Email</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Войти</button>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <div class="auth-links">
                    <a href="register.php">Регистрация</a>
                    <a href="recover.php">Забыли пароль?</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>