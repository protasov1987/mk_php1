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
        body { background: #f4f6f9; }
        .navbar-brand { font-weight: 700; }
        .user-name { font-weight: 600; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Маршрутные карты</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (check_access('menu_dashboard')): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Дашборд</a></li>
                <?php endif; ?>
                <?php if (check_access('menu_route_cards')): ?>
                    <li class="nav-item"><a class="nav-link" href="route_cards_list.php">Маршрутные карты</a></li>
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
