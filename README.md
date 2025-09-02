# Garuda Eyes — Panduan Setup dan Deploy untuk Pemula

Dokumentasi singkat langkah demi langkah untuk menyiapkan dan menjalankan aplikasi Garuda Eyes di VPS baru menggunakan skrip otomatis `scripts/provision_full_auto.sh` yang sudah disertakan di repo.

Target pembaca: pemula yang punya akses root (sudo) ke VPS berbasis Debian/Ubuntu.

Ringkasan singkat
- Skrip provisioning satu perintah: meng-update sistem, meng-install Apache/MariaDB/PHP, meng-deploy kode, membuat database, meng-import seed, membuat admin, dan (opsional) mendapatkan sertifikat TLS.
- Lokasi skrip: `scripts/provision_full_auto.sh`

Checklist sebelum mulai
- VPS baru berbasis Debian/Ubuntu (Ubuntu 20.04/22.04 atau Debian setara).
- Domain publik (mis. `garudaeyes.com`) dengan A record mengarah ke IP VPS — perlu untuk mendapatkan TLS otomatis.
- Akses root atau sudo pada VPS.
- File repository (kode) sudah ada di VPS atau Anda akan upload/clone repository ke VPS.

1) Persiapan awal (jika belum ada code di VPS)

- Upload atau clone repo ke VPS, contoh:

```bash
# login ke VPS, lalu:
cd /home/youruser
git clone <repo-url> garudaeyes
cd garudaeyes
```

2) Variabel penting di skrip

Buka `scripts/provision_full_auto.sh` dan perhatikan bagian Defaults (baris awal):

```bash
# Defaults
DOMAIN="garudaeyes.com"
ADMIN_USER="admin"
ADMIN_PASS="Admin@Garuda2025!"
ADMIN_EMAIL="admin@garudaeyes.com"
```

Penjelasan singkat tiap variabel:
- DOMAIN: nama domain publik yang akan digunakan (wajib bila ingin mendapatkan TLS otomatis).
- ADMIN_USER: username admin yang akan dibuat/diupdate di database.
- ADMIN_PASS: password akun admin — gunakan password kuat dan ganti default.
- ADMIN_EMAIL: alamat email admin, juga digunakan oleh certbot untuk pendaftaran.

Anda tidak harus mengedit file; Anda bisa mengoper nilai ini ketika menjalankan skrip (lihat contoh menjalankan).

3) Menjalankan skrip provisioning (cara mudah)

Jalankan sebagai root atau via sudo dari direktori repo:

```bash
sudo bash scripts/provision_full_auto.sh \
  --domain garudaeyes.com \
  --admin-user admin \
  --admin-pass 'YourStrongP@ssw0rd!' \
  --admin-email 'admin@garudaeyes.com' \
  --src /path/to/garudaeyes
```

Opsi penting:
- `--no-ssl` : jangan coba dapatkan sertifikat Let's Encrypt (berguna jika DNS belum diarahkan).
- `--src /path/to/garudaeyes` : jika Anda menjalankan skrip dari luar folder repo.

4) Apa yang dilakukan skrip (ringkasan)
- apt update/upgrade dan install paket (apache2, mariadb-server, php, ekstensi yang diperlukan)
- enable Apache modules (rewrite, headers, ssl)
- backup webroot lama `/var/www/<domain>` bila ada
- deploy file dengan rsync ke `/var/www/<domain>` dan set permission
- buat database `garudaeyes` dan user DB `garudaeyes` (password di-generate otomatis kecuali Anda modifikasi skrip)
- import `garuda.sql` atau `db/init.sql` bila ada
- update `app/config.php` untuk SITE_DOMAIN dan (jika cocok) DB constants
- jalankan skrip migrasi yang disertakan bila ada
- buat atau update akun admin
- (opsional) jalankan certbot untuk mendapatkan sertifikat TLS

5) Verifikasi pasca-deploy

- Cek status Apache:

```bash
systemctl status apache2
```

- Cek vhost tersedia:

```bash
ls /etc/apache2/sites-enabled | grep $DOMAIN
```

- Cek log jika error:

```bash
tail -n 200 /var/log/apache2/$DOMAIN-error.log
```

- Cek database dan admin user:

```bash
mysql -u root -e "USE garudaeyes; SELECT id,username,email,role FROM users LIMIT 5;"
```

6) Menjalankan aplikasi secara lokal untuk development (opsional)

Jika Anda hanya ingin menjalankan aplikasi secara lokal tanpa Apache/MariaDB sistem, gunakan server PHP built-in dan MySQL terpisah (Anda perlu import DB):

```bash
# import db (misal menggunakan garuda.sql)
mysql -u root < garuda.sql

# jalankan web server PHP untuk testing di http://localhost:8000
php -S localhost:8000 -t .
```

7) Troubleshooting umum
- Certbot gagal: periksa DNS A record dan port 80/443 terbuka. Gunakan `--no-ssl` untuk melanjutkan deploy tanpa TLS.
- Apache 500 / PHP error: cek `/var/log/apache2/<domain>-error.log` dan file PHP yang bermasalah.
- Skrip gagal saat membuat DB: pastikan MariaDB berjalan (`systemctl status mariadb`) dan Anda memiliki hak root.

8) Keamanan pasca-deploy (penting)
- Segera ganti password admin jika Anda menggunakan password default.
- Hapus file-sample atau skrip yang tidak perlu dari webroot.
- Pastikan `app/config.php` tidak berisi password sensitif yang dapat di-commit ke git.

9) Catatan untuk pengguna LAMPP/XAMPP

Jika Anda ingin deploy ke LAMPP/XAMPP (bukan instalasi paket sistem), ada skrip khusus di `scripts/deploy_once.sh` dan `scripts/deploy_final.sh` yang mengadaptasi binary LAMPP (`/opt/lampp`). Beritahu saya jika Anda ingin skrip provisioning yang memakai LAMPP, saya akan tambah varian.

10) Jika butuh bantuan

Jika ingin saya:
- generate password admin / DB yang kuat untuk Anda, atau
- menyesuaikan skrip agar DB password ditentukan via flag `--db-pass`, atau
- membuat varian untuk CentOS/AlmaLinux,

...kirim permintaan dan saya akan update skrip.

---
README ini dibuat untuk memberi panduan cepat dan aman bagi pemula agar dapat menjalankan skrip provisioning satu-kali dan meng-deploy aplikasi Garuda Eyes.
