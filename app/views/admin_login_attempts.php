<?php
// app/views/admin_login_attempts.php
require_once __DIR__ . '/../models/User.php';
$title = 'Login Attempts - Admin';
$pdo = get_pdo();

$q_user = trim($_GET['user'] ?? '');
$q_ip = trim($_GET['ip'] ?? '');
$q_from = trim($_GET['from'] ?? '');
$q_to = trim($_GET['to'] ?? '');

$conds = [];
$params = [];
if ($q_user !== '') { $conds[] = '(username = :user)'; $params[':user'] = $q_user; }
if ($q_ip !== '') { $conds[] = '(ip = :ip)'; $params[':ip'] = $q_ip; }
if ($q_from !== '') { $conds[] = '(created_at >= :from)'; $params[':from'] = $q_from; }
if ($q_to !== '') { $conds[] = '(created_at <= :to)'; $params[':to'] = $q_to; }

$sql = 'SELECT la.*, u.username AS real_username FROM login_attempts la LEFT JOIN users u ON la.user_id = u.id';
if (!empty($conds)) $sql .= ' WHERE ' . implode(' AND ', $conds);
$sql .= ' ORDER BY la.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="card card-g p-3">
  <h2>Login Attempts</h2>
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-3"><input name="user" value="<?php echo htmlspecialchars($q_user); ?>" class="form-control" placeholder="username"></div>
    <div class="col-md-3"><input name="ip" value="<?php echo htmlspecialchars($q_ip); ?>" class="form-control" placeholder="IP"></div>
    <div class="col-md-2"><input name="from" value="<?php echo htmlspecialchars($q_from); ?>" type="datetime-local" class="form-control"></div>
    <div class="col-md-2"><input name="to" value="<?php echo htmlspecialchars($q_to); ?>" type="datetime-local" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Filter</button></div>
  </form>

  <table class="admin-table">
    <thead><tr><th>ID</th><th>User</th><th>Username</th><th>IP</th><th>Success</th><th>When</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['user_id'] ? $r['real_username'] : '-'); ?></td>
          <td><?php echo htmlspecialchars($r['username']); ?></td>
          <td><?php echo htmlspecialchars($r['ip']); ?></td>
          <td><?php echo $r['success'] ? 'Yes' : 'No'; ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          <td>
            <?php if (!$r['success'] && $r['user_id']): ?>
              <form method="post" action="<?php echo htmlspecialchars(url('admin/actions/unlock_user')); ?>" style="display:inline">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($r['user_id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((function(){ require_once __DIR__ . '/../controllers/AuthController.php'; return AuthController::csrf_token(); })()); ?>">
                <button class="btn btn-sm btn-warning" type="submit">Unlock</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
