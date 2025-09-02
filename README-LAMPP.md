Setup dengan LAMPP (XAMPP untuk Linux)

1) Pastikan LAMPP berjalan:

   sudo /opt/lampp/lampp start

2) Buat database dan tabel menggunakan mysql client dari LAMPP:

   /opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS sql_garudaeyes_c CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   /opt/lampp/bin/mysql -u root sql_garudaeyes_c < db/init.sql

3) Buat akun admin (gunakan PHP dari LAMPP supaya extension PDO ada sesuai instalasi):

   /opt/lampp/bin/php scripts/create_admin.php -u admin@example.com -p 'YourStrongPassword' -n 'Admin Name'

4) Pastikan file `app/config.php` cocok dengan kredensial (untuk LAMPP default user=root and empty password - file sudah auto-detect `/opt/lampp`).

5) Jalankan situs Anda (misalnya tempatkan folder ini di htdocs atau atur virtual host) dan buka http://localhost/login (extensionless route)

Tips deploy: gunakan `scripts/deploy_once.sh` yang sudah LAMPP-aware. Contoh otomatis import `garuda.sql` dengan kredensial:

```bash
sudo bash scripts/deploy_once.sh --dst /opt/lampp/htdocs/garudaeyes --db-user root --db-pass ''
```

Catatan keamanan:
- Setelah selesai setup, ubah password root MySQL bila server ini dapat diakses publik.
- Aktifkan HTTPS di level server (TLS) ketika mempublikasikan; `app/config.php` akan mencoba mendeteksi `/opt/lampp` dan menonaktifkan forced HTTPS secara default.
