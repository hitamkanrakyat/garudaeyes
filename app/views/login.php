<?php
// app/views/login.php — adapted from provided login.html
require_once __DIR__ . '/../controllers/AuthController.php';
$title = 'Login — GARUDA EYES';
ob_start();
?>
<div class="card login-card card-g">
    <div class="card-body">
        <h3 class="mb-2">GARUDA EYES</h3>
        <p class="muted">Masuk untuk melanjutkan</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars(url('login')); ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Email atau Username</label>
                <input name="username" type="text" class="form-control bg-dark text-white border-0" placeholder="Masukkan email atau username" required>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">Kata Sandi</label>
                <input id="pwd" name="password" type="password" class="form-control bg-dark text-white border-0" placeholder="Masukkan password" required>
                <button type="button" id="togglePwd" class="btn btn-sm btn-outline-light position-absolute" style="right:8px;top:36px">Tampilkan</button>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthController::csrf_token()); ?>">

            <div class="d-flex align-items-center justify-content-between">
                <button class="btn btn-accent" type="submit">Masuk</button>
                <a href="<?php echo htmlspecialchars(url('forgot')); ?>" class="muted">Lupa password?</a>
            </div>
        </form>
    </div>
</div>

<script>
    // show / hide password
    (function(){
        var btn = document.getElementById('togglePwd');
        var pwd = document.getElementById('pwd');
        if (!btn || !pwd) return;
        btn.addEventListener('click', function(){
            if (pwd.type === 'password') { pwd.type = 'text'; btn.textContent = 'Sembunyikan'; }
            else { pwd.type = 'password'; btn.textContent = 'Tampilkan'; }
        });
    })();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
