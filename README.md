## README QxuanStore - Toko Sepatu



## Deskripi
  QxuanStore Toko Sepatu adalah website e-commerce yang digunakan untuk menjual produk sepatu

# Cara Menggunakan/ insttal Website ini :

1. pastikan anda sudah menginstall xampp dengan PHP version 8.1, jika belum ada, anda bisa Instal Xampp terlebih dahulu, 
   dengan PHP version 8.1 (versi php yang saya gunakan)

2. Jika sudah ada XAMPP, Buka File Expoler dan cari folder project db_bukawarung.rar lalu Extrak File tersebut 
    (db_bukawarung.rar)

3. Jika sudah di extrak, pindahkan folder db_bukawarung dengan cara copy(CTRL + C) folder nya nya ke Data C > 
   xampp >  htdocs > lalu paste (CTRL + V)

4. Lalu buka folder yang sudah dipaste tadi(db_bukawarung), disitu sudah saya masukan file sql(file yang berisi data/struktur
    database) nya untuk dihubungkan kedatabase, lalu kita akan memasukan file tersebut ke database phpmyadmin

5. buka Aplikasi XAMPP nya, lalu klik start pada action Apache dan MYSQL, lalu klik admin disamping tombol stop dibagian
    mysql, atau bisa juga kita search dibrowser dengan cara ketik http://localhost/phpmyadmin/

6. Jika sudah masuk ke menu phpmyadmin, kita akan buat database baru dengan cara klik New atau Baru disebelah kiri lalu buat
    database dengan nama db_bukawarung, atau bisa juga dengan script yaitu klik kolom SQL yang ada di bar atas, lalu masukan script CREATE DATABASE `db_bukawarung` lalu klik kirim

7. Jika kita klik database db_bukawarung yang sudah dibuat tadi, struktur database nya kosong atau tidak ada, disini kita akan
   mengimport dengan 
   sturktur database yang sudah saya buat dan saya masukan di folder yang sudah di extrak sebelumnya dengan nama db_bukawarung dengan 
   tipe SQL File, di menu halaman phpmyadmin, klik database yang sudah dibuat(db_bukawarung) lalu klik Import di menu bar atas,
   jika sudah di klik, pada menu berkas untuk impor : ada bacaan Choose file atau pilih file, lalu kita klik menu file Choose file 
   lalu kita cari file database yang sudah dibuat dengan cara klik local data C > XAMPP > htdocs > pilih folder db_bukawarung lalu Klik
   folder tersebut, jika sudah di klik, scrool kebawah sedikit nanti ada file db_bukawarung dengan type : SQL File, lalu kita klik file tersebut
   jika sudah klik file databasenya nanti dimenu import pada phpmyadmin di bagian bawah ada tombol kirim, lalu klik tombol kirim, 
   jika sudah berhasil akan muncul tampilan yang ada ceklis hijau yang artinya sudah berhasil mengimport strutur databasenya, nnti jika 
   kita klik database db_bukawarung maka akan muncul beberapa table seperti, tb_admin, tb_category, dan tb_produk

8. Jika sudah berhasil import file database, konfigurasikan koneksi database MySQL dengan cara  buka file db_bukawarung dengan
   text editor
   (gunakan text editor sesuai keinginan anda),
   Cek pada file koneksi.php nya

   <?php
   $koneksi = new mysqli("localhost","root","","db_bukawarung");
   ?>

   $servername= "localhost"; // nama host database
   $username = "root"; // username database
   $password= ""; //password database
   $dbname = "db_bukawarung"; // nama database

   pastikan nama file nya sesuai dengan yang terdapat pada PhpMyAdmin yaitu nama database nya 'db_bukawarung'(sesuaikan dengan file database yang terdapat pada Pada PhpMyAdmin) atau sesuaikan dengan pengaturan anda

9. Jika sudah meng import file/struktur databsenya dan sudah dicek dibagian koneksinya, kita akan masuk / mengakses ke website
   yang sudah dibuat yaitu dengan cara search di browser, ketik URL http://localhost/Project Uas/index.php lalu klik enter

10. pada halaman index disitu untuk user membeli barang, dan memilih barang mana yang ingin dibeli setelah itu akan diarahkan 
    ke WhatsApp admin untuk memesan produk tersebut.

11. dan untuk admin kita bisa ketik URL http:/localhost/Project Uas/login.php ,halaman ini untuk login sebagai admin

## Akun Admin

12. Akun Admin nya :
    * username : admin                    | dengan id_admin 1
    * password : 12345

13. Fitur Admin :
   * Dashboard = Didalam Dashboard terdapat pemberitahuan bahwa admin login 

   * Profile = Di halaman profile ini admin bisa mengubah data profile admin seperti nama,no telephone,email,alamat,dan password
   * Kategory = Di halaman Kategory kita bisa menambahkan kategory barang dan juga bisa mengubah dan menghapus data kategory barang
   * Produk = Di halaman produk ini admin bisa menambah data produk sesuai kategory nya, bisa mengubah status produk in stock atau sold out dan juga bisa mengubah dan menghapus data produk yang ada di dalam nya.
   * Logout = Logout ini berfungsi untuk keluar dari halaman admin dan akan diarahkan ke halaman login.php

14. Fitur User :
    * Home = Di halaman ini berisikan pencarian produk yang dicari dan ada juga produk yang dijual seperti gambar,nama produk dan harga produk
    * Detail Produk = Di halaman ini user harus klik gambar produk yang ada di menu lalu akan dibawa ke detail produk dan jika ingin memesan produk, user bisa klik logo WhatsApp setelah itu user akan diarahkan ke WhatsApp admin untuk melakukan pemesanan
    * Logout : Logout ini berfungsi untuk keluar dari halaman user dan akan diarahkan ke halaman login.php 