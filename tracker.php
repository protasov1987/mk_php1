<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_tracker')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_access('menu_route_cards_edit')) {
    $operationId = (int)($_POST['operation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($operationId) {
        apply_operation_action($pdo, $operationId, $action, (int)$_SESSION['user_id']);
        $actionMessage = 'Статус операции обновлён';
    }
}

$activeOps = (int)$pdo->query("SELECT COUNT(*) FROM route_operations WHERE status='in_progress'")->fetchColumn();

$sql = "SELECT rc.id AS card_id, rc.number, rc.title, rc.order_number, rc.status AS card_status, rc.updated_at,
               op.id AS op_id, op.operation_number, op.name AS op_name, op.subdivision, op.status AS op_status, op.planned_time_min
        FROM route_cards rc
        LEFT JOIN route_operations op ON rc.id = op.route_card_id
        ORDER BY rc.updated_at DESC, op.position ASC";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

$cards = [];
foreach ($rows as $row) {
    if (!isset($cards[$row['card_id']])) {
        $cards[$row['card_id']] = [
            'number' => $row['number'],
            'title' => $row['title'],
            'order_number' => $row['order_number'],
            'status' => $row['card_status'],
            'updated_at' => $row['updated_at'],
            'operations' => [],
        ];
    }
    if ($row['op_id']) {
        $cards[$row['card_id']]['operations'][] = [
            'id' => $row['op_id'],
            'operation_number' => $row['operation_number'],
            'name' => $row['op_name'],
            'subdivision' => $row['subdivision'],
            'status' => $row['op_status'],
            'planned_time_min' => $row['planned_time_min'],
        ];
    }
}

function statusBadge(string $status): string {
    $map = [
        'waiting' => 'secondary',
        'in_progress' => 'warning',
        'done' => 'success',
        'paused' => 'info',
        'cancelled' => 'danger',
        'draft' => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . h($color) . ' text-uppercase">' . h($status) . '</span>';
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="mb-1">Трекер операций</h3>
        <p class="text-muted mb-0">Контролируйте время и статусы всех операций в маршрутных картах</p>
    </div>
    <div class="pill-highlight text-nowrap">
        Активных операций: <strong><?php echo $activeOps; ?></strong>
    </div>
</div>
<?php if ($actionMessage): ?>
    <div class="alert alert-success"><?php echo h($actionMessage); ?></div>
<?php endif; ?>

<?php if (!$cards): ?>
    <div class="alert alert-info">Маршрутные карты пока не созданы.</div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($cards as $cardId => $card): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h5 class="mb-1">МК № <?php echo h($card['number']); ?> — <?php echo h($card['title']); ?></h5>
                            <div class="text-muted small">Заказ: <?php echo h($card['order_number']); ?> · Обновлено: <?php echo h($card['updated_at']); ?></div>
                        </div>
                        <div class="text-end">
                            <?php echo statusBadge($card['status']); ?>
                            <a class="btn btn-sm btn-outline-primary ms-2" href="route_card_view.php?id=<?php echo (int)$cardId; ?>">Открыть</a>
                        </div>
                    </div>
                    <?php if (!$card['operations']): ?>
                        <div class="alert alert-light mb-0">Операции не добавлены</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th>
                                        <th>Операция</th>
                                        <th>Подразделение</th>
                                        <th>План, мин</th>
                                        <th>Статус</th>
                                        <th>Время</th>
                                        <?php if (check_access('menu_route_cards_edit')): ?><th class="text-end">Действия</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($card['operations'] as $op): ?>
                                        <?php $elapsed = calculate_operation_duration($pdo, (int)$op['id'], $op['status']); ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo (int)$op['operation_number']; ?></td>
                                            <td><?php echo h($op['name']); ?></td>
                                            <td class="text-muted"><?php echo h($op['subdivision']); ?></td>
                                            <td><?php echo (int)$op['planned_time_min']; ?></td>
                                            <td><?php echo statusBadge($op['status']); ?></td>
                                            <td><?php echo format_duration($elapsed); ?></td>
                                            <?php if (check_access('menu_route_cards_edit')): ?>
                                            <td class="text-end">
                                                <form method="post" class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                                    <input type="hidden" name="operation_id" value="<?php echo (int)$op['id']; ?>">
                                                    <?php foreach (['start'=>'▶️','pause'=>'⏸️','resume'=>'⏯️','finish'=>'✅','cancel'=>'✖️'] as $code=>$label): ?>
                                                        <button name="action" value="<?php echo h($code); ?>" class="btn btn-sm btn-outline-secondary" type="submit" title="<?php echo h($code); ?>">
                                                            <?php echo h($label); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </form>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
