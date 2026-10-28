# 👟 QxuanStore — Modern Shoe E-Commerce Platform

QxuanStore adalah platform toko online (*e-commerce*) modern berbasis web yang dirancang khusus untuk penjualan produk sepatu sneakers dan casual. Sistem ini telah dilengkapi dengan keranjang belanja, manajemen variasi ukuran sepatu, gerbang pembayaran otomatis (*payment gateway*) **Midtrans Snap**, pelacakan ekspedisi, serta dashboard panel admin yang komprehensif.

---

## 📌 1. Spesifikasi Sistem (System Specifications)

### A. Teknologi & Backend
* **Bahasa Pemrograman:** PHP (Mendukung PHP 8.1 hingga PHP 8.5+)
* **Database Management System (DBMS):** MySQL / MariaDB (Nama database default: `db_bukawarung`)
* **Arsitektur Kode:** Modular PHP Native dengan pemisahan direktori terstruktur:
  * `/admin`: Modul pengelolaan inventaris toko, kategori, dan pesanan masuk.
  * `/user`: Modul area pelanggan (profil, keranjang, checkout, riwayat pesanan).
  * `/auth`: Modul otentikasi login & registrasi pelanggan.
  * `/api`: Endpoint REST JSON untuk Webhook Midtrans dan konfirmasi penerimaan pesanan.
  * `/config`: Konfigurasi database, session guard, helpers, dan kredensial Midtrans.
  * `/includes`: Template header dan footer konsisten.

### B. Payment Gateway & Integrasi Eksternal
* **Payment Gateway:** **Midtrans Snap API** (Sandbox Mode siap uji & Production Ready).
* **Metode Pembayaran:** 
  * **QRIS Otomatis:** GoPay, ShopeePay, DANA, LinkAja, BCA QRIS.
  * **Virtual Account:** BCA, BNI, BRI, Bank Mandiri, Bank Permata.
  * **Transfer Bank Otomatis & Kartu Kredit.**
* **Pelacakan Ekspedisi:** Tautan pelacakan resi instan nasional via integrasi CekResi.com dan kurir ekspedisi (J&T Express, JNE, SiCepat, dll).

### C. Keamanan Sistem
* **Enkripsi Password:** Algoritma Bcrypt melalui `password_hash()` dan `password_verify()`.
* **Proteksi Database:** Menggunakan MySQL Prepared Statements (`mysqli_prepare` / binding parameters) untuk proteksi total dari serangan SQL Injection.
* **Proteksi XSS:** Sanitasi output dengan `htmlspecialchars()`.
* **Access Control:** Proteksi sesi terpisah antara hak akses Pelanggan (`require_user()`) dan Administrator (`require_admin()`).

### D. Tampilan & UI/UX
* **Styling:** Modern CSS3 murni (CSS Custom Properties/Variables, Flexbox, CSS Grid Responsive).
* **Komponen Interaktif:** SweetAlert2 (Modal alert, pop-up konfirmasi, loading status).
* **Ikon:** FontAwesome 6 Pro/Free CDN.
* **Tipografi:** Google Font *Plus Jakarta Sans*.

---

## 🔄 2. Alur Kerja Sistem (Business Logic & Order Lifecycle)

Sistem pesanan di QxuanStore memiliki siklus 5 tahap yang transparan dari awal belanja hingga sepatu diterima:

```
[1. Pilih Sepatu & Size] ──> [2. Checkout & Buat Pesanan] ──> [3. Bayar via Midtrans]
                                                                        │ (Otomatis)
                                                                        ▼
[5. Pesanan Selesai] <────── [4. Pengiriman & Resi] <─────── [Status: Diproses]
  (Barang Diterima)            (Admin Input No Resi)           (Packing Sepatu)
```

### Tahap 1: Pemilihan Sepatu & Ukuran (Customer)
1. Pelanggan menjelajahi katalog sepatu di halaman beranda atau katalog produk.
2. Memilih ukuran sepatu yang tersedia (Size 38 s/d 44). Stok ukuran berkurang secara otomatis ketika pesanan dibuat.
3. Menambahkan sepatu ke keranjang belanja (`user/cart.php`).

### Tahap 2: Checkout & Pemesanan (Customer)
1. Pelanggan menuju halaman checkout (`user/checkout.php`).
2. Data nama, nomor WhatsApp, dan alamat domisili terisi otomatis dari profil pelanggan.
3. Pelanggan memilih opsi pengiriman:
   * **Ekspedisi (J&T Express, JNE Regular, SiCepat Reguler):** Nomor resi otomatis dibuatkan oleh sistem dan pesanan dikirimkan via kurir ke alamat pembeli.
   * **Ambil di Toko Fisik:** Bebas biaya ongkir dan mendapatkan potongan harga sepatu sebesar **Rp 15.000**.
4. Sistem membuat data pesanan di tabel `tb_order` dengan status awal **`Menunggu Pembayaran`**, nomor Invoice unik (contoh: `INV-20261028-XXXXXX`), nomor resi otomatis, dan status `is_read = 0` (memicu notifikasi merah pesanan baru di dashboard admin).

### Tahap 3: Pembayaran Otomatis via Midtrans Gateway
1. Pelanggan diarahkan ke halaman pembayaran (`user/order-success.php`).
2. Sistem otomatis mengaitkan **Snap Token** transaksi.
3. Pelanggan mengklik tombol **"Bayar Sekarang via Midtrans Gateway"**:
   * **Mode Demo / Sandbox:** Jika belum mengonfigurasi API Keys akun Midtrans pribadi di `config/midtrans.php`, sistem otomatis menampilkan dialog simulator pembayaran instan yang elegan (QRIS GoPay/ShopeePay/DANA & Virtual Account).
   * **Mode Produksi / Akun Asli:** Jika kredensial akun Midtrans telah diisi, pop-up resmi Snap JS akan terbuka langsung.
4. **Verifikasi Otomatis:** Begitu pembayaran diselesaikan, sistem memproses callback `api/verify_payment.php`.
5. Status pesanan seketika berubah dari `"Menunggu Pembayaran"` menjadi **`"Diproses"`**, serta mencatat metode pembayaran dan waktu bayar secara real-time.

### Tahap 4: Pemrosesan, Cetak Struk, & Pengiriman Produk (Admin)
1. Admin melihat badge notifikasi **"Baru"** di menu *Pesanan* sidebar (`admin/orders/index.php`).
2. Admin dapat mengklik tombol **"Cetak Struk"** untuk mencetak struk fisik / PDF bukti transaksi untuk disisipkan ke dalam kardus sepatu.
3. Nomor resi pengiriman telah otomatis terisi sesuai kurir yang dipilih pelanggan. Admin menyiapkan dan mengemas sepatu, lalu mengubah status menjadi **`"Dikirim"`**.
4. Di panel admin, tombol aksi langsung menampilkan **"Lacak Resi Pengiriman (CekResi.com)"** untuk memonitor perjalanan paket secara langsung.

---

## 📦 3. Logika Penerimaan Barang: Bagaimana Admin Tahu Barang Sudah Diterima?

Pada transaksi e-commerce, setelah paket dikirim oleh kurir, Admin membutuhkan kepastian apakah barang sudah sampai ke tangan pembeli atau belum. Di QxuanStore, logika ini dirancang dengan mekanisme terintegrasi:

### Mekanisme 1: Konfirmasi Mandiri oleh Pembeli (*Customer Receipt Confirmation*) [UTAMA]
* Ketika pesanan berstatus **`Dikirim`**, di akun pelanggan (`user/orders.php` dan `user/order-success.php`) akan muncul tombol hijau: **"Pesanan Sudah Diterima"**.
* Begitu pembeli menerima sepatu dan memeriksa kondisinya, pembeli menekan tombol tersebut.
* Sistem memanggil `api/confirm_order_received.php`, otomatis mengubah status pesanan menjadi **`"Selesai"`**, dan menyimpan timestamp penerimaan pada kolom **`waktu_diterima`**.
* **Status Terkunci Otomatis (Data Integrity):** Begitu status menjadi `Selesai`, form ubah status di panel admin (`admin/orders/detail.php`) **otomatis dikunci dan dinonaktifkan**, sehingga status pesanan tidak dapat diubah-ubah lagi demi menjaga integritas data riwayat transaksi.

### Mekanisme 2: Pelacakan Resi Ekspedisi Real-Time (*Live Tracking*)
* Pada halaman detail pesanan Admin (`admin/orders/detail.php`), ketika status pesanan adalah `"Dikirim"`, tersedia tombol langsung:
  **"Lacak Resi Pengiriman (CekResi.com)"**.
* Admin dapat memantau status fisik paket secara live (apakah *Manifested*, *On Transit*, *With Delivery Courier*, atau *Delivered*).

### Mekanisme 3: Konfirmasi Langsung via WhatsApp Customer
* Admin dibekali link/tombol **"Tanya via WhatsApp"** pada rincian data penerima paket yang langsung membuka obrolan ke nomor WhatsApp pembeli dengan pesan otomatis yang menyebutkan model sepatu, kurir, nomor resi, serta menanyakan apakah pesanan telah sampai dan mengarahkan customer untuk menekan tombol **"Pesanan Sudah Diterima"** di riwayat pesanan mereka.

---

## 🗄️ 4. Struktur Database Utama

| Nama Tabel | Deskripsi & Fungsi |
|---|---|
| `tb_admin` | Data akun administrator (ID, username, password hash, email, no HP, alamat). |
| `tb_user` | Data akun pelanggan (ID, nama lengkap, email, password hash, no WhatsApp, alamat domisili). |
| `tb_category` | Kategori produk sepatu (Sneakers, Casual, Running, Formal, dll). |
| `tb_produk` | Master data katalog sepatu (nama produk, kategori, harga, deskripsi, gambar, status). |
| `tb_produk_size` | Manajemen varian ukuran (size 38-44) dan stok fisik per ukuran. |
| `tb_order` | Header transaksi pesanan (invoice_no, total_bayar, status_order, metode_pembayaran, waktu_bayar, waktu_diterima, kurir, no_resi). |
| `tb_order_detail`| Rincian item per pesanan (id_order, id_produk, nama_produk, ukuran, harga, qty, subtotal). |

### Siklus Status Pesanan (`status_order`):
1. **`Menunggu Pembayaran`** — Pesanan baru dibuat, menunggu pembayaran customer.
2. **`Diproses`** — Pembayaran lunas via Midtrans Gateway, barang sedang disiapkan toko.
3. **`Dikirim`** — Barang telah diserahkan ke ekspedisi kurir, nomor resi telah diinput.
4. **`Selesai`** — Barang telah diterima customer dan transaksi selesai.
5. **`Dibatalkan`** — Pesanan dibatalkan (kadaluarsa/permintaan khusus).

---

## 🚀 5. Panduan Instalasi & Penggunaan

### Kebutuhan Sistem:
* Web Server: Apache (Laragon / XAMPP)
* PHP Versi: PHP 8.1 / 8.2 / 8.3 / 8.4 / 8.5+
* Database: MySQL / MariaDB
* Ekstensi PHP yang dibutuhkan: `mysqli`, `curl`, `json`, `session`, `mbstring`.

### Langkah Instalasi:
1. Pindahkan folder project `store-sepatu` ke direktori web server:
   * **Laragon:** `C:\laragon\www\store-sepatu`
   * **XAMPP:** `C:\xampp\htdocs\store-sepatu`
2. Nyalakan layanan Apache dan MySQL pada control panel Laragon atau XAMPP.
3. Buka browser dan akses **phpMyAdmin** (`http://localhost/phpmyadmin/`).
4. Buat database baru bernama:
   ```sql
   CREATE DATABASE db_bukawarung;
   ```
5. Buka berkas [config/database.php](file:///c:/laragon/www/store-sepatu/config/database.php) dan sesuaikan kredensial jika menggunakan password:
   ```php
   $hostname = 'localhost';
   $username = 'root';
   $password = '';
   $dbname   = 'db_bukawarung';
   ```
6. Buka website di browser:
   * **Halaman Toko (Customer):** `http://localhost/store-sepatu/index.php`
   * **Halaman Panel Admin:** `http://localhost/store-sepatu/admin/login.php`

---

## 🔐 6. Akun Default untuk Pengujian

### Akun Administrator Toko:
* **URL Login:** `http://localhost/store-sepatu/admin/login.php`
* **Username:** `admin`
* **Password:** `12345`

### Akun Pelanggan (Customer):
* **URL Login:** `http://localhost/store-sepatu/auth/login.php`
* Pelanggan dapat mendaftar akun baru melalui tombol **"Daftar Akun Baru"** atau menggunakan akun customer terdaftar.
