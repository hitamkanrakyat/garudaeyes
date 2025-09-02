<?php
// app/views/admin_user_form.php
require_once __DIR__ . '/../models/User.php';
$id = (int)($_GET['id'] ?? 0);
$user = $id ? User::findById($id) : ['username'=>'','fullname'=>'','role'=>'user'];
$title = $id ? 'Edit User' : 'Create User';
ob_start();
?>
<div class="card">
    <h2><?php echo $title; ?></h2>
        <form method="post" action="<?php echo htmlspecialchars(url('admin/users/save')); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
                        (function(){ require_once __DIR__ . '/../controllers/AuthController.php'; return AuthController::csrf_token(); })()
                ); ?>">

                <div class="mb-2">
                    <label>Username</label>
                    <input class="form-control bg-dark text-white" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>

                <div class="mb-2">
                    <label>Email</label>
                    <input class="form-control bg-dark text-white" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>

                <div class="mb-2">
                    <label>Fullname</label>
                    <input class="form-control bg-dark text-white" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label>Role</label>
                    <select class="form-select bg-dark text-white" name="role">
                        <option value="user" <?php echo ($user['role'] ?? '') === 'user' ? 'selected':''; ?>>User</option>
                        <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected':''; ?>>Admin</option>
                    </select>
                </div>

                <div class="mt-2">
                        <button class="btn btn-accent" type="submit">Simpan</button>
                </div>
        </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
