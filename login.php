<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    if ($password === '') {
        $error = 'Введите пароль';
    } else {
        try {
            $pdo = get_pdo();
            $stmt = $pdo->query('SELECT * FROM users WHERE is_active = 1');
            $users = $stmt->fetchAll();
            $found = null;
            foreach ($users as $user) {
                if (password_verify($password, $user['password1_hash'])) {
                    $found = $user;
                    break;
                }
                if (!empty($user['password2_hash']) && password_verify($password, $user['password2_hash'])) {
                    $found = $user;
                    break;
                }
            }
            if ($found) {
                $_SESSION['user_id'] = $found['id'];
                $_SESSION['user_name'] = $found['name'];
                $_SESSION['access_level_id'] = $found['access_level_id'];
                $_SESSION['is_observer'] = (int)$found['is_observer'] === 1;
                $_SESSION['last_activity'] = time();
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Неверный пароль';
            }
        } catch (Exception $e) {
            $error = 'Ошибка подключения к БД: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1b365d 0%, #2f4b86 40%, #f4f7fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.7);
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 420px;">
    <div class="card shadow-lg auth-card">
        <div class="card-header text-center bg-transparent border-0 pt-4 pb-0">
            <h4 class="mb-1">Добро пожаловать</h4>
            <p class="text-muted mb-0">Введите пароль, чтобы продолжить</p>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
