<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_dashboard')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$totalCards = $pdo->query('SELECT COUNT(*) FROM route_cards')->fetchColumn();
$inProgress = $pdo->query("SELECT COUNT(*) FROM route_cards WHERE status='in_progress'")->fetchColumn();
$done = $pdo->query("SELECT COUNT(*) FROM route_cards WHERE status='done'")->fetchColumn();
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-bg-primary shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Всего маршрутных карт</h5>
                <p class="card-text display-6"><?php echo (int)$totalCards; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-warning shadow-sm">
            <div class="card-body">
                <h5 class="card-title">В работе</h5>
                <p class="card-text display-6"><?php echo (int)$inProgress; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-success shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Завершено</h5>
                <p class="card-text display-6"><?php echo (int)$done; ?></p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
