<?php // app/views/layout.php
?><!-- basic layout -->
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($title ?? 'Garuda Eyes'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(url('assets/styles.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="" crossorigin="anonymous">
</head>
<body>
    <header class="site-header">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <img src="<?php echo htmlspecialchars(url('logo.png')); ?>" alt="logo" style="height:38px">
                <div class="brand">GARUDA EYES</div>
            </div>
            <nav class="d-flex align-items-center">
                <a class="text-white me-3" href="<?php echo htmlspecialchars(url('')); ?>"><i class="fa fa-home me-1"></i>Home</a>
                <?php if (!empty($_SESSION['username'])): ?>
                    <a class="text-white me-3" href="<?php echo htmlspecialchars(url('dashboard')); ?>"><i class="fa fa-chart-pie me-1"></i>Dashboard</a>
                    <a class="text-white me-3" href="<?php echo htmlspecialchars(url('logout')); ?>"><i class="fa fa-sign-out-alt me-1"></i>Logout</a>
                <?php else: ?>
                    <a class="text-white me-3" href="<?php echo htmlspecialchars(url('login')); ?>"><i class="fa fa-sign-in-alt me-1"></i>Login</a>
                <?php endif; ?>
                <button id="themeToggle" class="btn btn-sm btn-outline-light ms-2" title="Toggle theme"><i class="fa fa-adjust"></i></button>
            </nav>
        </div>
    </header>
    <div class="container">
        <?php if (!empty($_GET['msg'])): ?>
            <div class="flash"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="flash"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        <?php endif; ?>
        <?php echo $content; ?>
    </div>
    <footer class="footer">&copy; <?php echo date('Y'); ?> Garuda Eyes</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            // theme toggle persisted in localStorage
            (function(){
                var toggle = document.getElementById('themeToggle');
                function applyTheme(t){
                    if (t === 'light') document.documentElement.classList.add('light');
                    else document.documentElement.classList.remove('light');
                }
                var cur = localStorage.getItem('ge_theme') || 'dark';
                applyTheme(cur);
                if (toggle) toggle.addEventListener('click', function(){
                    cur = (localStorage.getItem('ge_theme')||'dark') === 'dark' ? 'light':'dark';
                    localStorage.setItem('ge_theme', cur);
                    applyTheme(cur);
                });
            })();
        </script>
</body>
</html>
