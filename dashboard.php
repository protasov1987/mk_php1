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
$startedStmt = $pdo->query("SELECT rc.id, rc.number, rc.title, rc.order_number, rc.status AS card_status, op.operation_number, op.name AS op_name, op.status AS op_status
    FROM route_cards rc
    JOIN route_operations op ON op.route_card_id = rc.id
    WHERE op.status <> 'waiting'
      AND op.id = (SELECT o2.id FROM route_operations o2 WHERE o2.route_card_id = rc.id AND o2.status <> 'waiting' ORDER BY o2.updated_at DESC LIMIT 1)
    ORDER BY rc.updated_at DESC");
$startedCards = $startedStmt->fetchAll();
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
<div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Активные маршрутные карты</h5>
        <a class="btn btn-sm btn-outline-primary" href="tracker.php">Перейти в трекер</a>
    </div>
    <?php if (!$startedCards): ?>
        <div class="alert alert-light border">Пока нет запущенных операций.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($startedCards as $card): ?>
                <div class="col-md-6">
                    <div class="card glass-card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-semibold">МК № <?php echo h($card['number']); ?> — <?php echo h($card['title']); ?></div>
                                    <div class="text-muted small">Заказ: <?php echo h($card['order_number']); ?></div>
                                </div>
                                <span class="badge bg-secondary text-uppercase"><?php echo h($card['card_status']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted">Текущая операция</div>
                                    <div class="fw-semibold">№<?php echo (int)$card['operation_number']; ?> — <?php echo h($card['op_name']); ?></div>
                                </div>
                                <span class="badge bg-info text-dark text-uppercase"><?php echo h($card['op_status']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
