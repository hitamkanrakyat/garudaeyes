<?php
// app/views/admin_users.php
require_once __DIR__ . '/../models/User.php';
$title = 'User Management - GARUDA EYES';
$limit = 20;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;
$users = User::all($limit, $offset);
$total = User::countAll();
ob_start();
?>
<div class="card card-g p-3">
    <h2>Manajemen Pengguna</h2>
    <p class="muted">Total pengguna: <?php echo $total; ?></p>
    <table class="admin-table mt-3">
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Fullname</th><th>Role</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['id']); ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                <td><?php echo htmlspecialchars($u['role']); ?></td>
                <td>
                    <div class="admin-actions">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars(url('admin/users/edit?id=' . $u['id'])); ?>">Edit</a>
                        <form method="post" action="<?php echo htmlspecialchars(url('admin/users/delete')); ?>" style="display:inline" onsubmit="return confirm('Hapus user?');">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((function(){ require_once __DIR__ . '/../controllers/AuthController.php'; return AuthController::csrf_token(); })()); ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
