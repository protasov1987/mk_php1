<?php
require_once __DIR__ . '/header.php';

if (!check_access('menu_users')) {
    echo '<div class="alert alert-danger">Нет прав доступа</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_pdo();
$message = '';
$error = '';

function ean13_valid(?string $code): bool {
    if ($code === null || $code === '') return true; // allow null
    if (!preg_match('/^\d{13}$/', $code)) return false;
    $digits = array_map('intval', str_split($code));
    $sum = 0;
    foreach ($digits as $i => $d) {
        $sum += ($i % 2 === 0) ? $d : $d * 3;
    }
    return $sum % 10 === 0;
}

function password_unique(PDO $pdo, string $password1, ?string $password2, ?int $currentId = null): bool
{
    $stmt = $pdo->prepare('SELECT id, password1_hash, password2_hash, password2_ean13 FROM users' . ($currentId ? ' WHERE id <> ?' : ''));
    $stmt->execute($currentId ? [$currentId] : []);
    while ($row = $stmt->fetch()) {
        if (password_verify($password1, $row['password1_hash']) || (!empty($row['password2_hash']) && password_verify($password1, $row['password2_hash']))) {
            return false;
        }
        if ($password2) {
            if (!empty($row['password2_hash']) && password_verify($password2, $row['password2_hash'])) return false;
            if ($password2 === $row['password2_ean13']) return false;
        }
        if ($password2 && password_verify($password2, $row['password1_hash'])) return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_user') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $accessLevel = (int)($_POST['access_level_id'] ?? 0);
        $password1 = trim($_POST['password1'] ?? '');
        $password2 = trim($_POST['password2'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isObserver = isset($_POST['is_observer']) ? 1 : 0;

        if ($name === '' || $accessLevel === 0) {
            $error = 'Имя и уровень доступа обязательны';
        } elseif ($password1 !== '' && strlen($password1) < 6) {
            $error = 'Первый пароль должен быть не менее 6 символов';
        } elseif ($password2 !== '' && !ean13_valid($password2)) {
            $error = 'Второй пароль должен быть корректным EAN-13';
        } elseif ($password1 !== '' && !password_unique($pdo, $password1, $password2 ?: null, $id)) {
            $error = 'Такой пароль уже используется другим пользователем';
        }

        if ($error === '') {
            if ($id) {
                // update
                $stmt = $pdo->prepare('UPDATE users SET name=?, access_level_id=?, is_active=?, is_observer=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$name, $accessLevel, $isActive, $isObserver, $id]);
                if ($password1 !== '') {
                    $pdo->prepare('UPDATE users SET password1_hash=? WHERE id=?')->execute([password_hash($password1, PASSWORD_DEFAULT), $id]);
                }
                if ($password2 !== '') {
                    $pdo->prepare('UPDATE users SET password2_ean13=?, password2_hash=? WHERE id=?')->execute([$password2, password_hash($password2, PASSWORD_DEFAULT), $id]);
                }
                $message = 'Пользователь обновлен';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (name, access_level_id, password1_hash, password2_ean13, password2_hash, is_observer, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
                $stmt->execute([
                    $name,
                    $accessLevel,
                    password_hash($password1, PASSWORD_DEFAULT),
                    $password2 ?: null,
                    $password2 ? password_hash($password2, PASSWORD_DEFAULT) : null,
                    $isObserver,
                    $isActive,
                ]);
                $message = 'Пользователь создан';
            }
        }
    }
}

$levels = $pdo->query('SELECT * FROM access_levels ORDER BY id')->fetchAll();
$users = $pdo->query('SELECT u.*, a.name AS access_name FROM users u LEFT JOIN access_levels a ON u.access_level_id = a.id ORDER BY u.id')->fetchAll();
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    foreach ($users as $u) {
        if ((int)$u['id'] === $id) { $editUser = $u; break; }
    }
}
?>
<h3>Пользователи</h3>
<?php if ($message): ?><div class="alert alert-success"><?php echo h($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
<div class="row">
    <div class="col-md-7">
        <table class="table table-striped bg-white">
            <thead><tr><th>Имя</th><th>Уровень</th><th>Статус</th><th>Наблюдатель</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo h($u['name']); ?></td>
                        <td><?php echo h($u['access_name']); ?></td>
                        <td><?php echo $u['is_active'] ? 'Активен' : 'Заблокирован'; ?></td>
                        <td><?php echo $u['is_observer'] ? 'Да' : 'Нет'; ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="users.php?edit=<?php echo (int)$u['id']; ?>">Редактировать</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header"><?php echo $editUser ? 'Редактировать пользователя' : 'Новый пользователь'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_user">
                    <?php if ($editUser): ?><input type="hidden" name="id" value="<?php echo (int)$editUser['id']; ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" class="form-control" name="name" value="<?php echo h($editUser['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Уровень доступа</label>
                        <select name="access_level_id" class="form-select" required>
                            <option value="">--</option>
                            <?php foreach ($levels as $level): ?>
                                <option value="<?php echo (int)$level['id']; ?>" <?php if (($editUser['access_level_id'] ?? 0) == $level['id']) echo 'selected'; ?>><?php echo h($level['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Первый пароль</label>
                        <input type="password" class="form-control" name="password1" <?php echo $editUser ? '' : 'required'; ?> placeholder="Введите или сгенерируйте">
                        <div class="form-text">Не менее 6 символов, буквы и цифры</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Второй пароль (EAN-13)</label>
                        <input type="text" class="form-control" name="password2" value="<?php echo h($editUser['password2_ean13'] ?? ''); ?>" placeholder="13 цифр">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo ($editUser['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isActive">Активен</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_observer" id="isObserver" <?php echo ($editUser['is_observer'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isObserver">Наблюдатель</label>
                    </div>
                    <button class="btn btn-success" type="submit">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
