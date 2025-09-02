<?php
// app/views/home.php - adapted from provided index.html template
$title = 'GARUDA EYES - Landing';
ob_start();
?>
<header>
    <div class="logo">
        <img src="<?php echo (defined('BASE_PATH') && BASE_PATH ? BASE_PATH : ''); ?>/logo.png" alt="Logo" style="height:32px">
    </div>
    <nav>
        <ul>
            <li><a href="<?php echo htmlspecialchars(url('')); ?>">Home</a></li>
            <li><a href="#">Live</a></li>
            <li><a href="#">Sports</a></li>
            <li><a href="#">Series</a></li>
            <li><a href="#">Movies</a></li>
            <li><a href="#">TV Show</a></li>
            <li><a href="#">Lainnya</a></li>
        </ul>
    </nav>
    <div class="header-right">
        <div class="search-box">
            <input type="text" placeholder="Nonton apa hari ini?">
        </div>
        <button class="langganan-btn">LANGGANAN</button>
    <a class="login-link" href="<?php echo htmlspecialchars(url('login')); ?>">Masuk</a>
    </div>
</header>

<div class="hero">
    <video autoplay muted loop playsinline class="bg-video">
        <source src="<?php echo htmlspecialchars(url('BBB.mp4')); ?>" type="video/mp4">
    Browser Anda tidak mendukung video tag.
  </video>
  <div class="overlay"></div>
  <div class="hero-content">
    <h2>Kisah Kura-kura sombong</h2>
    <p>Kura-kura terjatuh akibat kesombongannya! Bagaimana bisa? Yuk, tonton videonya!</p>
    <div class="hero-buttons">
      <button class="btn-primary">Cek Sekarang</button>
      <button class="btn-secondary">Daftarku</button>
    </div>
  </div>
</div>

<section class="packages">
    <h2>Bebas Pilih Paket Sesukamu</h2>
    <div class="package-list">
        <div class="package-card">
            <img src="https://thumbor.prod.vidiocdn.com/LztUO...jpg" alt="Paket 1">
            <div class="package-content">
                <p>Nonton Tayangan 5 Match Eksklusif Superstar Knockout King of The Ring</p>
                <p class="price">Mulai Rp 29.000</p>
                <a href="#" class="buy-btn">Beli Sekarang</a>
            </div>
        </div>
        <div class="package-card">
            <img src="https://thumbor.prod.vidiocdn.com/8-tT_...jpg" alt="Paket 2">
            <div class="package-content">
                <p>Nonton Tayangan 4 Match Eksklusif HW Sport Night Bali</p>
                <p class="price">Mulai Rp 29.000</p>
                <a href="#" class="buy-btn">Beli Sekarang</a>
            </div>
        </div>
        <div class="package-card">
            <img src="https://thumbor.prod.vidiocdn.com/V-1gKL...jpg" alt="Paket 3">
            <div class="package-content">
                <p>Lebih dari 70 Sports untuk semua pelanggan</p>
                <p class="price">Mulai Rp 1,5 Jt / Tahun</p>
                <a href="#" class="buy-btn">Langganan Sekarang</a>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
