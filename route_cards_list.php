<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_route_cards')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$filters = [
    'number' => trim($_GET['number'] ?? ''),
    'order_number' => trim($_GET['order_number'] ?? ''),
    'title' => trim($_GET['title'] ?? ''),
];

$sql = 'SELECT rc.*, u.name AS author FROM route_cards rc LEFT JOIN users u ON rc.created_by = u.id WHERE 1=1';
$params = [];
if ($filters['number'] !== '') {
    $sql .= ' AND rc.number LIKE ?';
    $params[] = '%' . $filters['number'] . '%';
}
if ($filters['order_number'] !== '') {
    $sql .= ' AND rc.order_number LIKE ?';
    $params[] = '%' . $filters['order_number'] . '%';
}
if ($filters['title'] !== '') {
    $sql .= ' AND rc.title LIKE ?';
    $params[] = '%' . $filters['title'] . '%';
}
$sql .= ' ORDER BY rc.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Маршрутные карты</h3>
        <p class="text-muted mb-0">Управляйте созданием и просмотром маршрутных карт</p>
    </div>
    <?php if (check_access('menu_route_cards_edit')): ?>
        <a class="btn btn-primary" href="route_card_edit.php">Создать маршрутную карту</a>
    <?php endif; ?>
</div>
<div class="card glass-card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Номер МК</label>
                <input type="text" class="form-control" name="number" value="<?php echo h($filters['number']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Номер заказа</label>
                <input type="text" class="form-control" name="order_number" value="<?php echo h($filters['order_number']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Название</label>
                <input type="text" class="form-control" name="title" value="<?php echo h($filters['title']); ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
            </div>
        </form>
    </div>
</div>
<div class="card glass-card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>№</th>
                    <th>Заказ</th>
                    <th>Название</th>
                    <th>Статус</th>
                    <th>Создал</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $card): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo h($card['number']); ?></td>
                        <td><?php echo h($card['order_number']); ?></td>
                        <td><?php echo h($card['title']); ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?php echo h($card['status']); ?></span></td>
                        <td><?php echo h($card['author']); ?></td>
                        <td class="text-end">
                            <?php if (check_access('menu_route_cards_view')): ?>
                                <a class="btn btn-sm btn-outline-primary" href="route_card_view.php?id=<?php echo (int)$card['id']; ?>">Открыть</a>
                            <?php endif; ?>
                            <?php if (check_access('menu_route_cards_edit')): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="route_card_edit.php?id=<?php echo (int)$card['id']; ?>">Редактировать</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
