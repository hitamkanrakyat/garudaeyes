        <script>
            (function(){
                const apiUrl = '<?php echo url("/api/chart.php"); ?>';
                fetch(apiUrl, {credentials: 'same-origin'})
                    .then(r=>r.json())
                    .then(payload => {
                        const ctx = document.getElementById('loginChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: payload.labels,
                                datasets: [{
                                    label: 'Login attempts (last 24h)',
                                    backgroundColor: 'rgba(54,162,235,0.2)',
                                    borderColor: 'rgba(54,162,235,1)',
                                    data: payload.data,
                                    tension: 0.3,
                                }]
                            },
                            options: { responsive: true }
                        });
                    }).catch(err => {
                        console.error('Chart API error', err);
                    });
            })();
        </script>
foreach ($rows as $r) { $map[$r['h']] = (int)$r['c']; }
foreach ($hours as $h) { $counts[] = $map[$h] ?? 0; }
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>Dashboard</h1>
            <p class="muted">Halo, <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User'); ?> — role: <?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?></p>
        </div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="card card-g p-3">
                <h5>Profil</h5>
                <p class="muted">Informasi akun Anda.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-g p-3">
                <h5>Monitoring</h5>
                <p class="muted">Statistik dan status.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-g p-3">
                <h5>Face Recognition</h5>
                <p class="muted">Fitur pengenalan wajah.</p>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a class="btn btn-secondary" href="<?php echo htmlspecialchars(url('logout')); ?>">Logout</a>
    </div>
</div>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card card-g p-3 chart-card">
                    <h5>Traffic (hari ini)</h5>
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card card-g p-3">
                    <h5>Ringkasan</h5>
                    <ul class="list-unstyled small-muted">
                        <li>Pengguna aktif: <strong>42</strong></li>
                        <li>Permintaan hari ini: <strong>1,234</strong></li>
                        <li>Error 5xx: <strong>0</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            var ctx = document.getElementById('trafficChart');
            if (!ctx) return;
            var labels = <?php echo json_encode($labels); ?>;
            var data = <?php echo json_encode($counts); ?>;
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Login attempts',
                        data: data,
                        borderColor: '#ff9900',
                        backgroundColor: 'rgba(255,153,0,0.12)',
                        tension: 0.3
                    }]
                },
                options: {responsive:true, maintainAspectRatio:false}
            });
        })();
    </script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
