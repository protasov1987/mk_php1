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
    <h3>Маршрутные карты</h3>
    <?php if (check_access('menu_route_cards_edit')): ?>
        <a class="btn btn-primary" href="route_card_edit.php">Создать маршрутную карту</a>
    <?php endif; ?>
</div>
<form class="row g-3 mb-3">
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
        <button class="btn btn-secondary" type="submit">Фильтр</button>
    </div>
</form>
<table class="table table-striped table-bordered bg-white">
    <thead>
        <tr>
            <th>№</th>
            <th>Заказ</th>
            <th>Название</th>
            <th>Статус</th>
            <th>Создал</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cards as $card): ?>
            <tr>
                <td><?php echo h($card['number']); ?></td>
                <td><?php echo h($card['order_number']); ?></td>
                <td><?php echo h($card['title']); ?></td>
                <td><?php echo h($card['status']); ?></td>
                <td><?php echo h($card['author']); ?></td>
                <td>
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
<?php require_once __DIR__ . '/footer.php'; ?>
