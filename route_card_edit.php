<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_route_cards_edit')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$cardId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];
$card = [
    'number' => '',
    'order_number' => '',
    'title' => '',
    'status' => 'draft',
];
$operations = [];

if ($cardId) {
    $stmt = $pdo->prepare('SELECT * FROM route_cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $card = $stmt->fetch();
    if (!$card) {
        echo '<div class="alert alert-danger">Маршрутная карта не найдена</div>';
        require_once __DIR__ . '/footer.php';
        exit;
    }
    $stmtOps = $pdo->prepare('SELECT * FROM route_operations WHERE route_card_id = ? ORDER BY position ASC');
    $stmtOps->execute([$cardId]);
    $operations = $stmtOps->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card['number'] = trim($_POST['number'] ?? '');
    $card['order_number'] = trim($_POST['order_number'] ?? '');
    $card['title'] = trim($_POST['title'] ?? '');
    $card['status'] = $_POST['status'] ?? 'draft';

    if ($card['number'] === '' || $card['title'] === '') {
        $errors[] = 'Номер и название обязательны';
    }

    $opNames = $_POST['op_name'] ?? [];
    $opSubdivisions = $_POST['op_subdivision'] ?? [];
    $opTimes = $_POST['op_time'] ?? [];
    $opNumbers = $_POST['op_number'] ?? [];

    $operations = [];
    foreach ($opNames as $idx => $name) {
        if (trim($name) === '') {
            continue;
        }
        $operations[] = [
            'operation_number' => (int)($opNumbers[$idx] ?? ($idx + 1)),
            'name' => trim($name),
            'subdivision' => trim($opSubdivisions[$idx] ?? ''),
            'planned_time_min' => (int)($opTimes[$idx] ?? 0),
            'status' => 'waiting',
            'position' => count($operations) + 1,
        ];
    }

    if (!$errors) {
        if ($cardId) {
            $stmt = $pdo->prepare('UPDATE route_cards SET number=?, order_number=?, title=?, status=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$card['number'], $card['order_number'], $card['title'], $card['status'], $cardId]);
            $pdo->prepare('DELETE FROM route_operations WHERE route_card_id = ?')->execute([$cardId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO route_cards (number, order_number, title, status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$card['number'], $card['order_number'], $card['title'], $card['status'], $_SESSION['user_id']]);
            $cardId = (int)$pdo->lastInsertId();
        }

        if ($operations) {
            $stmtOp = $pdo->prepare('INSERT INTO route_operations (route_card_id, operation_number, name, subdivision, planned_time_min, status, position, created_at, updated_at) VALUES (?,?,?,?,?,"waiting",?,NOW(),NOW())');
            foreach ($operations as $op) {
                $stmtOp->execute([$cardId, $op['operation_number'], $op['name'], $op['subdivision'], $op['planned_time_min'], $op['position']]);
            }
        }

        header('Location: route_cards_list.php');
        exit;
    }
}
?>
<h3><?php echo $cardId ? 'Редактирование' : 'Новая'; ?> маршрутная карта</h3>
<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) { echo '<div>' . h($e) . '</div>'; } ?>
    </div>
<?php endif; ?>
<form method="post">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Номер МК</label>
            <input type="text" class="form-control" name="number" value="<?php echo h($card['number']); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Номер заказа</label>
            <input type="text" class="form-control" name="order_number" value="<?php echo h($card['order_number']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <?php foreach (['draft'=>'Черновик','in_progress'=>'В работе','done'=>'Готово','paused'=>'Пауза','cancelled'=>'Отменено'] as $k=>$v): ?>
                    <option value="<?php echo h($k); ?>" <?php if ($card['status']===$k) echo 'selected'; ?>><?php echo h($v); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Название</label>
            <input type="text" class="form-control" name="title" value="<?php echo h($card['title']); ?>" required>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Операции</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addOperation">Добавить операцию</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white" id="operationsTable">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Наименование</th>
                    <th>Подразделение</th>
                    <th>План, мин</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($operations): foreach ($operations as $op): ?>
                    <tr>
                        <td><input type="number" class="form-control" name="op_number[]" value="<?php echo (int)$op['operation_number']; ?>"></td>
                        <td><input type="text" class="form-control" name="op_name[]" value="<?php echo h($op['name']); ?>"></td>
                        <td><input type="text" class="form-control" name="op_subdivision[]" value="<?php echo h($op['subdivision']); ?>"></td>
                        <td><input type="number" class="form-control" name="op_time[]" value="<?php echo (int)$op['planned_time_min']; ?>"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm remove-op">Удалить</button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        <button class="btn btn-success" type="submit">Сохранить</button>
        <a class="btn btn-secondary" href="route_cards_list.php">Отмена</a>
    </div>
</form>
<script>
const tableBody = document.querySelector('#operationsTable tbody');
document.getElementById('addOperation').addEventListener('click', () => {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="number" class="form-control" name="op_number[]" value="${tableBody.children.length + 1}"></td>
        <td><input type="text" class="form-control" name="op_name[]" required></td>
        <td><input type="text" class="form-control" name="op_subdivision[]"></td>
        <td><input type="number" class="form-control" name="op_time[]" value="0"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm remove-op">Удалить</button></td>
    `;
    tableBody.appendChild(row);
});

tableBody.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-op')) {
        e.target.closest('tr').remove();
    }
});
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
