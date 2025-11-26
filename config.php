<?php
// Basic configuration and helper functions
session_start();

// Database configuration - adjust for deployment
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'mk_app';
$db_user = getenv('DB_USER') ?: 'mk_user';
$db_pass = getenv('DB_PASS') ?: 'password';
$db_port = getenv('DB_PORT') ?: '3306';

// List of known resources for permissions
$ALL_RESOURCES = [
    'menu_dashboard',
    'menu_route_cards',
    'menu_route_cards_edit',
    'menu_route_cards_view',
    'menu_users',
    'menu_access_levels',
];

function get_pdo(): PDO
{
    static $pdo = null;
    global $db_host, $db_name, $db_user, $db_pass, $db_port;
    if ($pdo === null) {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    }
    return $pdo;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    update_last_activity();
}

function update_last_activity(): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }
    if (!empty($_SESSION['is_observer'])) {
        return; // No timeout for observer
    }
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity'] > 300)) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function check_access(string $resource_code): bool
{
    if (empty($_SESSION['access_level_id'])) {
        return false;
    }
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT allowed FROM access_level_permissions WHERE access_level_id = ? AND resource_code = ? LIMIT 1');
    $stmt->execute([$_SESSION['access_level_id'], $resource_code]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    return (bool)$row['allowed'];
}

function ensure_default_data(): void
{
    $pdo = get_pdo();

    // ensure access_levels exists with admin and observer if users empty
    $pdo->exec('SET NAMES utf8mb4');
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users');
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        return;
    }

    global $ALL_RESOURCES;

    $pdo->beginTransaction();
    try {
        // Create access levels
        $stmtLevel = $pdo->prepare('INSERT INTO access_levels (name, description) VALUES (?, ?)');
        $stmtLevel->execute(['Администратор', 'Полный доступ']);
        $adminLevelId = (int)$pdo->lastInsertId();
        $stmtLevel->execute(['Наблюдение', 'Только просмотр дашборда']);
        $observerLevelId = (int)$pdo->lastInsertId();

        // Admin permissions all resources allowed
        $stmtPerm = $pdo->prepare('INSERT INTO access_level_permissions (access_level_id, resource_code, allowed) VALUES (?, ?, 1)');
        foreach ($ALL_RESOURCES as $res) {
            $stmtPerm->execute([$adminLevelId, $res]);
        }

        // Observer permissions only dashboard
        $stmtPerm->execute([$observerLevelId, 'menu_dashboard']);

        // Create default users
        $now = date('Y-m-d H:i:s');
        $stmtUser = $pdo->prepare('INSERT INTO users (name, access_level_id, password1_hash, password2_ean13, password2_hash, is_observer, is_active, created_at, updated_at) VALUES (?, ?, ?, NULL, NULL, ?, 1, ?, ?)');
        $stmtUser->execute(['Администратор', $adminLevelId, password_hash('admin', PASSWORD_DEFAULT), 0, $now, $now]);
        $stmtUser->execute(['Наблюдатель', $observerLevelId, password_hash('viewer', PASSWORD_DEFAULT), 1, $now, $now]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Run default data check on include
try {
    ensure_default_data();
} catch (Exception $e) {
    // If database is not ready, avoid breaking login page; message can be displayed later
}

