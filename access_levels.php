<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_access_levels')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = $_POST['permissions'] ?? [];
    global $ALL_RESOURCES;

    if ($name === '') {
        $error = 'Название обязательно';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE access_levels SET name=?, description=? WHERE id=?');
            $stmt->execute([$name, $description, $id]);
            $pdo->prepare('DELETE FROM access_level_permissions WHERE access_level_id=?')->execute([$id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO access_levels (name, description) VALUES (?,?)');
            $stmt->execute([$name, $description]);
            $id = (int)$pdo->lastInsertId();
        }
        $stmtPerm = $pdo->prepare('INSERT INTO access_level_permissions (access_level_id, resource_code, allowed) VALUES (?,?,?)');
        foreach ($ALL_RESOURCES as $res) {
            $stmtPerm->execute([$id, $res, in_array($res, $permissions, true) ? 1 : 0]);
        }
        $message = 'Уровень доступа сохранён';
    }
}

$levels = $pdo->query('SELECT * FROM access_levels ORDER BY id')->fetchAll();
$editLevel = null;
$editPerms = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    foreach ($levels as $l) { if ((int)$l['id'] === $id) { $editLevel = $l; break; } }
    if ($editLevel) {
        $stmt = $pdo->prepare('SELECT resource_code FROM access_level_permissions WHERE access_level_id=? AND allowed=1');
        $stmt->execute([$id]);
        $editPerms = array_column($stmt->fetchAll(), 'resource_code');
    }
}
?>
<h3>Уровни доступа</h3>
<?php if ($message): ?><div class="alert alert-success"><?php echo h($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
<div class="row">
    <div class="col-md-7">
        <table class="table table-striped bg-white">
            <thead><tr><th>Название</th><th>Описание</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($levels as $l): ?>
                    <tr>
                        <td><?php echo h($l['name']); ?></td>
                        <td><?php echo h($l['description']); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="access_levels.php?edit=<?php echo (int)$l['id']; ?>">Редактировать</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header"><?php echo $editLevel ? 'Редактировать уровень' : 'Новый уровень'; ?></div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editLevel): ?><input type="hidden" name="id" value="<?php echo (int)$editLevel['id']; ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" class="form-control" name="name" value="<?php echo h($editLevel['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <input type="text" class="form-control" name="description" value="<?php echo h($editLevel['description'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Права доступа</label>
                        <?php foreach ($ALL_RESOURCES as $res): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo h($res); ?>" id="perm_<?php echo h($res); ?>" <?php echo in_array($res, $editPerms, true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="perm_<?php echo h($res); ?>"><?php echo h($res); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-success" type="submit">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
