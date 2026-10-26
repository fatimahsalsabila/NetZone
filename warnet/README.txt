NETZONE - versi terbaru dengan perbaikan booking

Struktur:
index.php, booking.php, lokasi.php, kategori.php, pilih-komputer.php,
konfirmasi.php, riwayat.php, profil.php, data.php, script.js, styles.css,
includes/header.php, includes/footer.php, assets/logo-netzone.png

Install dengan Laragon:
1. Extract folder warnet ke C:\laragon\www\
2. Pastikan foldernya menjadi C:\laragon\www\warnet\
3. Jalankan Apache dari Laragon.
4. Buka http://localhost/warnet/

Fitur booking:
- Pengguna wajib memilih lokasi cabang sebelum memilih kategori dan jadwal; lokasi disimpan dan ditampilkan di konfirmasi, riwayat, serta daftar booking admin.
- Tanggal masa lalu ditolak di browser dan halaman PHP.
- Memilih jumlah orang dan komputer.
- Memilih beberapa komputer sesuai jumlah yang diminta.
- Ringkasan tanggal, jam, durasi, kategori, komputer, jumlah orang, metode pembayaran, dan total harga.
- Popup konfirmasi sebelum menyimpan booking.
- Booking, detail komputer, pembayaran, dan riwayat tersimpan di MySQL.
- Pembayaran proyek menyediakan tampilan QRIS dan Virtual Account bank. Gambar QR
  dan nomor VA yang tampil hanya contoh. Setelah pengguna mengonfirmasi pembayaran,
  website menampilkan proses singkat lalu otomatis menandai simulasi pembayaran
  berhasil dan booking dikonfirmasi. Halaman pembayaran berhasil menunggu pengguna
  menekan tombol untuk membuka riwayat pesanan; tidak ada transaksi atau transfer
  uang sungguhan.
- Ketersediaan komputer diperiksa dari booking aktif di database.


Akun lokal (demo):
- Sign Up menyimpan akun dan password dalam bentuk hash di tabel users.
- Login bisa memakai email, nomor telepon, atau username.
- Booking, riwayat, dan profile memerlukan login; halaman lokasi dan kategori bisa dilihat tanpa login.
- Password minimal 8 karakter dengan minimal 1 huruf kapital, 1 angka, dan 1 simbol. Tombol mata tersedia untuk show/hide password.
- Forgot password memakai kode verifikasi demo yang tampil di halaman. Ini BUKAN pengiriman email/SMS sungguhan; hubungkan provider sebelum digunakan online.
- Koneksi database diatur di config/database.php. Database harus memiliki tabel users, categories, computers, bookings, booking_details, dan payments.
- Saat keenam tabel tersebut masih kosong, data akun, booking, dan komputer lama dari folder storage diimpor otomatis satu kali. File JSON lama tidak lagi menjadi penyimpanan aktif.
- Aplikasi menambahkan kolom people, username_snapshot, payment_method_detail, price_per_hour, computer_name_snapshot, dan category_name_snapshot bila belum tersedia pada tabel terkait.
- Gunakan HTTPS, CSRF protection, rate limiting, dan provider OTP resmi sebelum aplikasi dipakai online.


=== Landing page & Admin (update) ===

Landing page:
- index.php sekarang landing page untuk pengunjung yang belum login (styles: landing.css).
- Setelah login, user diarahkan ke beranda.php (Beranda lama), admin ke admin/index.php.

Membuat akun admin pertama:
1. Buka http://localhost/warnet/setup-admin.php (hanya bisa dari localhost).
2. Isi data admin lalu login seperti biasa. Halaman setup otomatis terkunci setelah ada admin; file-nya boleh dihapus.
   Alternatif: ubah role akun menjadi admin pada tabel users.

Halaman admin (admin/):
- Dashboard: ringkasan booking, jadwal 7 hari, serta booking lama yang masih tertunda.
- Booking: setujui / batalkan / tandai selesai, filter status, tanggal, dan pencarian.
- Komputer & Harga: tambah, ubah, hapus komputer, harga per jam, status aktif/maintenance.

Data aplikasi sekarang disimpan di MySQL:
- Booking berstatus Menunggu setelah dikirim pengguna dan perlu disetujui admin.
- Komputer yang sudah dipesan pada jam yang sama tidak bisa dipilih lagi.
- Komputer dan kategori harga disimpan pada tabel computers dan categories; data awal dibuat otomatis dari storage/computers.json.
- Booking baru otomatis dikonfirmasi setelah simulasi pembayaran berhasil; admin
  tidak perlu menyetujui booking baru secara manual. Booking lama yang masih
  berstatus Menunggu tetap dapat dikelola dari halaman admin.
- Data booking lama dari localStorage browser tidak ikut dipindahkan.
