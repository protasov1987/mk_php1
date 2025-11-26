<?php
require_once __DIR__ . '/config.php';
require_login();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Маршрутные карты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #f9fcff 50%, #eef2f7 100%);
            min-height: 100vh;
        }
        .navbar-brand { font-weight: 700; letter-spacing: 0.3px; }
        .user-name { font-weight: 600; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
        }
        .pill-highlight {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 999px;
            padding: 10px 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }
        .navbar-custom {
            background: linear-gradient(90deg, #1f2a44 0%, #1b365d 50%, #162c4a 100%);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Маршрутные карты</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (check_access('menu_dashboard')): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Дашборд</a></li>
                <?php endif; ?>
                <?php if (check_access('menu_route_cards')): ?>
                    <li class="nav-item"><a class="nav-link" href="route_cards_list.php">Маршрутные карты</a></li>
                <?php endif; ?>
                <?php if (check_access('menu_tracker')): ?>
                    <li class="nav-item"><a class="nav-link" href="tracker.php">Трекер</a></li>
                <?php endif; ?>
                <?php if (check_access('menu_users')): ?>
                    <li class="nav-item"><a class="nav-link" href="users.php">Пользователи</a></li>
                <?php endif; ?>
                <?php if (check_access('menu_access_levels')): ?>
                    <li class="nav-item"><a class="nav-link" href="access_levels.php">Уровни доступа</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center text-white w-50 justify-content-center user-name">
                <?php echo h($_SESSION['user_name'] ?? ''); ?>
            </div>
            <div class="d-flex">
                <a class="btn btn-outline-light" href="logout.php">Выход</a>
            </div>
        </div>
    </div>
</nav>
<div class="container mb-4">
