<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_route_cards_view')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT rc.*, u.name AS author FROM route_cards rc LEFT JOIN users u ON rc.created_by = u.id WHERE rc.id = ?');
$stmt->execute([$cardId]);
$card = $stmt->fetch();
if (!$card) {
    echo '<div class="alert alert-danger">Маршрутная карта не найдена</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_access('menu_route_cards_edit')) {
    $operationId = (int)($_POST['operation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($operationId) {
        apply_operation_action($pdo, $operationId, $action, (int)$_SESSION['user_id']);
    }
    header('Location: route_card_view.php?id=' . $cardId);
    exit;
}

$stmtOps = $pdo->prepare('SELECT * FROM route_operations WHERE route_card_id = ? ORDER BY position ASC');
$stmtOps->execute([$cardId]);
$operations = $stmtOps->fetchAll();
?>
<style>
@media print {
    body { background: #fff; }
    .no-print { display: none; }
}
</style>
<div class="d-flex justify-content-between mb-3 no-print">
    <div>
        <a class="btn btn-secondary" href="route_cards_list.php">Назад</a>
    </div>
    <button class="btn btn-outline-primary" onclick="window.print()">Печать</button>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>МК № <?php echo h($card['number']); ?></h5>
                <div>Заказ: <?php echo h($card['order_number']); ?></div>
                <div>Автор: <?php echo h($card['author']); ?></div>
                <div>Статус: <?php echo h($card['status']); ?></div>
            </div>
            <div class="col-md-4 text-center">
                <?php if ($card['number']): ?>
                    <svg id="cardBarcode"></svg>
                    <div><?php echo h($card['number']); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div>Название: <?php echo h($card['title']); ?></div>
                <div>Обновлено: <?php echo h($card['updated_at']); ?></div>
            </div>
        </div>
    </div>
</div>
<div class="table-responsive mt-3">
    <table class="table table-bordered align-middle bg-white">
        <thead>
            <tr>
                <th>№</th>
                <th>Операция</th>
                <th>Подразделение</th>
                <th>План, мин</th>
                <th>Статус</th>
                <th>Факт</th>
                <?php if (check_access('menu_route_cards_edit')): ?><th class="no-print">Действия</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($operations as $op): ?>
                <?php $elapsed = calculate_operation_duration($pdo, (int)$op['id'], $op['status']); ?>
                <tr>
                    <td><?php echo (int)$op['operation_number']; ?></td>
                    <td><?php echo h($op['name']); ?></td>
                    <td><?php echo h($op['subdivision']); ?></td>
                    <td><?php echo (int)$op['planned_time_min']; ?></td>
                    <td><?php echo h($op['status']); ?></td>
                    <td><?php echo format_duration($elapsed); ?></td>
                    <?php if (check_access('menu_route_cards_edit')): ?>
                    <td class="no-print">
                        <form method="post" class="d-flex gap-1 flex-wrap">
                            <input type="hidden" name="operation_id" value="<?php echo (int)$op['id']; ?>">
                            <?php foreach (['start'=>'Старт','pause'=>'Пауза','resume'=>'Продолжить','finish'=>'Завершить','cancel'=>'Отмена'] as $code=>$label): ?>
                                <button name="action" value="<?php echo h($code); ?>" class="btn btn-sm btn-outline-secondary" type="submit"><?php echo h($label); ?></button>
                            <?php endforeach; ?>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
<?php if ($card['number']): ?>
JsBarcode("#cardBarcode", "<?php echo h($card['number']); ?>", {format: "EAN13", height: 60});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
