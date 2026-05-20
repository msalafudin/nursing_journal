# Requirements Document: Nursing Journal Application

## Introduction

Aplikasi Nursing Journal adalah sistem pelaporan keperawatan berbasis web untuk RSI Muhammadiyah 2 Kendal. Aplikasi ini memungkinkan perawat untuk menginput data pasien per shift (pagi, siang, malam) dengan field yang spesifik sesuai unit keperawatan, dan menampilkan laporan dalam bentuk grafik/chart berdasarkan unit dan rentang tanggal yang ditentukan. Sistem ini dirancang untuk meningkatkan efisiensi pencatatan data keperawatan dan memudahkan analisis tren pasien melalui visualisasi data yang intuitif.

**Tech Stack:** Laravel 10, MySQL, Tailwind CSS, shadcn/ui, Recharts

## Glossary

- **Aplikasi**: Sistem web pelaporan keperawatan (Nursing Journal Application)
- **Perawat**: Pengguna dengan peran Perawat yang menginput data pasien per shift
- **Admin**: Pengguna dengan peran Admin yang mengelola unit, pengguna, dan melihat laporan
- **Unit**: Ruangan/departemen keperawatan di rumah sakit (IGD, Rawat Inap, Rawat Jalan, VK, ICU, HCU)
- **Shift**: Pembagian waktu kerja dalam satu hari terdiri dari tiga periode: Pagi (07:00-14:00), Siang (14:00-21:00), Malam (21:00-07:00) berdasarkan zona waktu WIB (UTC+7)
- **Laporan**: Data rekapitulasi pasien yang ditampilkan dalam bentuk grafik/chart berdasarkan filter unit, shift, dan rentang tanggal
- **Dashboard**: Halaman utama yang menampilkan ringkasan laporan dan akses ke fitur-fitur utama
- **Form_Input**: Halaman untuk menginput data pasien per shift dengan field yang spesifik sesuai unit
- **Line Chart**: Grafik garis yang menampilkan tren jumlah pasien per hari dengan sumbu X berupa tanggal dan sumbu Y berupa jumlah pasien
- **Candle Chart**: Grafik candlestick yang menampilkan data perbulan dengan nilai open, high, low, close untuk analisis tren bulanan
- **Sesi**: Periode waktu di mana pengguna tetap login dalam aplikasi
- **Validasi**: Proses verifikasi data input sesuai dengan aturan dan format yang ditentukan
- **Notifikasi**: Pesan feedback kepada pengguna tentang hasil aksi (sukses, error, warning)
- **Tooltip**: Informasi tambahan yang muncul saat pengguna mengarahkan kursor ke elemen grafik

## Requirements

### Requirement 1: Autentikasi Pengguna

**User Story:** Sebagai Perawat atau Admin, saya ingin login ke aplikasi dengan akun saya, sehingga data yang saya input atau kelola tercatat atas nama saya dan akses saya sesuai dengan peran saya.

#### Acceptance Criteria

1. WHEN pengguna memasukkan username dan password yang sesuai dengan data akun terdaftar, THE Aplikasi SHALL mengautentikasi pengguna dan mengarahkan ke halaman Dashboard dalam waktu maksimal 3 detik
2. IF pengguna memasukkan username atau password yang tidak sesuai dengan data akun terdaftar, THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan kredensial tidak valid tanpa mengungkapkan field mana yang salah
3. IF pengguna gagal login sebanyak 5 kali berturut-turut dalam waktu 15 menit, THEN THE Aplikasi SHALL memblokir seluruh percobaan login pada akun tersebut selama 15 menit dan menampilkan pesan yang mengindikasikan akun terkunci sementara
4. IF sesi login pengguna tidak aktif selama 60 menit, THEN THE Aplikasi SHALL mengakhiri sesi secara otomatis dan mengarahkan pengguna ke halaman login dengan pesan informasi bahwa sesi telah berakhir
5. THE Aplikasi SHALL menyimpan informasi sesi login termasuk identitas pengguna, peran (Admin atau Perawat), dan unit yang ditugaskan (untuk Perawat)
6. WHEN pengguna menekan tombol logout, THE Aplikasi SHALL mengakhiri sesi login secara langsung dan mengarahkan pengguna ke halaman login dengan pesan konfirmasi logout berhasil
7. THE Aplikasi SHALL menggunakan mekanisme autentikasi yang aman dengan hashing password menggunakan algoritma bcrypt atau setara

### Requirement 2: Input Data Pasien Per Shift

**User Story:** Sebagai Perawat, saya ingin menginput data pasien pada shift saya dengan field yang sesuai dengan unit saya, sehingga data keperawatan tercatat dengan lengkap dan akurat.

#### Acceptance Criteria

1. WHEN Perawat membuka halaman Form_Input, THE Aplikasi SHALL menampilkan form input data pasien dengan field yang spesifik sesuai dengan unit yang ditugaskan kepada Perawat tersebut
2. WHEN Perawat mengisi semua field wajib dengan nilai numerik dalam rentang 0 sampai 9999 dan menekan tombol simpan, THE Aplikasi SHALL menyimpan data pasien ke database dengan mencatat tanggal input, Shift, Unit, dan identitas Perawat yang menginput
3. IF Perawat menekan tombol simpan tanpa mengisi satu atau lebih field wajib, THEN THE Aplikasi SHALL menampilkan pesan validasi secara inline pada setiap field yang belum diisi tanpa menghapus data yang sudah diisi pada field lainnya, dan SHALL mencegah penyimpanan data hingga semua field wajib terisi
4. WHEN data berhasil disimpan, THE Aplikasi SHALL menampilkan notifikasi sukses selama 3 detik dan mengosongkan form untuk input data berikutnya
5. IF penyimpanan data gagal karena gangguan koneksi atau kesalahan server, THEN THE Aplikasi SHALL menampilkan pesan error yang menjelaskan kegagalan penyimpanan dan mempertahankan seluruh data yang telah diisi pada form
6. IF Perawat menyimpan data untuk kombinasi tanggal, Shift, dan Unit yang sudah memiliki data tersimpan, THEN THE Aplikasi SHALL menampilkan dialog konfirmasi apakah Perawat ingin memperbarui data yang sudah ada atau membatalkan penyimpanan
7. WHEN data pasien berhasil diinput, THEN THE Aplikasi SHALL menampilkan textbox yang berisi nilai dari data pasien yang telah diinput pada shift tersebut dalam format teks yang dapat disalin, dan menyediakan tombol copy untuk menyalin teks tersebut ke clipboard
8. WHEN Perawat input data pasien pada Form_Input, THE Aplikasi SHALL menampilkan field wajib yang spesifik sesuai dengan unit Perawat tersebut dengan detail sebagai berikut:
   - **Unit IGD**: Jumlah pasien rawat inap, Jumlah pasien rawat jalan, Jumlah pasien pulang paksa, Keterangan penyakit rawat inap, Keterangan penyakit rawat jalan, Total (terisi otomatis dari jumlah rawat jalan + jumlah rawat inap + jumlah pulang paksa)
   - **Unit Rawat Inap**: Jumlah pasien anak, Jumlah pasien Dalam, Jumlah pasien Saraf, Jumlah pasien Obsgyn, Jumlah pasien Bedah, Jumlah inden, Jumlah RPL, Jumlah pasien pulang, Total (terisi otomatis dari total jumlah)
   - **Unit Rawat Jalan**: Jumlah poli Obgyn, Jumlah poli Dalam, Jumlah poli Anak, Jumlah poli Bedah, Jumlah poli Saraf, Jumlah poli Fisioterapi, Total (terisi otomatis dari total jumlah)
   - **Unit VK**: Jumlah pasien VK, Keterangan
   - **Unit ICU**: Jumlah pasien anak, Jumlah pasien Dalam, Jumlah pasien Saraf, Jumlah pasien Obsgyn, Jumlah pasien Bedah, Jumlah pasien Inden, Jumlah pasien pulang
   - **Unit HCU**: Jumlah pasien anak, Jumlah pasien Dalam, Jumlah pasien Saraf, Jumlah pasien Obsgyn, Jumlah pasien Bedah, Jumlah pasien Inden, Jumlah pasien pulang
9. WHEN Perawat membuka Form_Input, THE Aplikasi SHALL menampilkan Shift aktif sesuai dengan waktu saat ini berdasarkan pembagian waktu shift yang telah ditentukan

### Requirement 3: Pendeteksian Shift Otomatis

**User Story:** Sebagai Perawat, saya ingin sistem mengenali shift saya secara otomatis berdasarkan waktu saat ini, sehingga saya tidak perlu memilih shift secara manual setiap kali input data.

#### Acceptance Criteria

1. WHEN waktu saat ini (berdasarkan waktu server, zona waktu WIB/UTC+7) berada pada pukul 07:00:00 hingga sebelum 14:00:00, THE Aplikasi SHALL menetapkan Shift default sebagai "Pagi"
2. WHEN waktu saat ini berada pada pukul 14:00:00 hingga sebelum 21:00:00, THE Aplikasi SHALL menetapkan Shift default sebagai "Siang"
3. WHEN waktu saat ini berada pada pukul 21:00:00 hingga sebelum 07:00:00 keesokan harinya, THE Aplikasi SHALL menetapkan Shift default sebagai "Malam"
4. THE Aplikasi SHALL menampilkan dropdown pemilihan Shift dengan tiga opsi (Pagi, Siang, Malam) yang dapat diubah oleh Perawat kapan saja sebelum menyimpan data
5. WHEN Perawat membuka halaman Form_Input pada kunjungan baru, THE Aplikasi SHALL mengatur ulang Shift default sesuai dengan waktu saat ini tanpa mempertahankan pilihan shift sebelumnya

### Requirement 4: Tampilan Laporan dalam Grafik

**User Story:** Sebagai Admin, saya ingin melihat laporan keperawatan dalam bentuk grafik/chart, sehingga saya dapat menganalisis data dengan mudah dan cepat.

#### Acceptance Criteria

1. WHEN Admin membuka halaman Laporan, THE Aplikasi SHALL menampilkan halaman dengan filter dan area grafik yang responsif
2. WHEN Admin memilih rentang tanggal pada halaman Laporan, THE Aplikasi SHALL menampilkan perbandingan grafik garis (line chart) dengan sumbu X berupa tanggal dan sumbu Y berupa jumlah pasien per hari sesuai rentang tanggal yang dipilih
3. WHEN Admin memilih filter Unit tertentu, THE Aplikasi SHALL menampilkan grafik hanya untuk Unit yang dipilih dan menyembunyikan grafik multi-unit
4. WHEN Admin memilih opsi "Semua Unit", THE Aplikasi SHALL menampilkan grafik dengan masing-masing Unit ditampilkan sebagai garis terpisah yang dibedakan berdasarkan warna
5. THE Aplikasi SHALL menampilkan grafik menggunakan library Recharts dengan tampilan yang responsif dan dapat beradaptasi dengan ukuran viewport
6. WHEN Admin mengarahkan kursor ke elemen grafik, THE Aplikasi SHALL menampilkan tooltip yang berisi tanggal, nama Unit, jumlah pasien, dan nama Shift pada titik tersebut
7. IF tidak terdapat data pada rentang tanggal dan filter yang dipilih, THEN THE Aplikasi SHALL menampilkan area grafik kosong disertai pesan yang menginformasikan bahwa tidak ada data untuk filter yang dipilih, tanpa menampilkan indikator loading
8. WHEN grafik sedang memuat data, THE Aplikasi SHALL menampilkan indikator loading; IF waktu loading melebihi 5 detik, THEN THE Aplikasi SHALL menyembunyikan indikator loading dan menampilkan pesan error yang menginformasikan kegagalan memuat data
9. THE Aplikasi SHALL menyediakan halaman laporan perbulan berdasarkan unit dalam bentuk candle chart yang menampilkan nilai open, high, low, close untuk setiap hari dalam bulan yang dipilih

### Requirement 5: Filter dan Navigasi Laporan

**User Story:** Sebagai Admin, saya ingin memfilter laporan berdasarkan unit, shift, dan tanggal, sehingga saya dapat melihat data yang spesifik sesuai kebutuhan analisis.

#### Acceptance Criteria

1. THE Aplikasi SHALL menyediakan filter pada halaman Laporan berdasarkan:
   - Unit (mendukung pilihan satu Unit atau semua Unit)
   - Shift (mendukung pilihan satu Shift atau semua Shift)
   - Rentang tanggal (tanggal mulai dan tanggal akhir, maksimal 90 hari ke belakang dari hari ini)
2. WHEN Admin mengubah salah satu filter, THE Aplikasi SHALL memperbarui grafik dalam waktu maksimal 3 detik tanpa perlu reload halaman
3. THE Aplikasi SHALL menyimpan state filter terakhir yang digunakan selama sesi aktif; HOWEVER, WHEN halaman Laporan pertama kali dibuka, THE Aplikasi SHALL selalu menampilkan data hari ini untuk semua Unit dan semua Shift sebagai default, mengabaikan filter yang tersimpan dari sesi sebelumnya
4. WHEN halaman Laporan pertama kali dibuka dan belum ada state filter tersimpan, THE Aplikasi SHALL menampilkan data hari ini untuk semua Unit dan semua Shift sebagai default
5. IF filter yang dipilih tidak menghasilkan data, THEN THE Aplikasi SHALL menampilkan pesan informasi bahwa tidak ada data yang sesuai dengan filter yang dipilih, dan tetap menampilkan area grafik dalam keadaan kosong
6. IF Admin memilih tanggal mulai yang lebih besar dari tanggal akhir, THEN THE Aplikasi SHALL menampilkan pesan validasi bahwa rentang tanggal tidak valid dan tidak memperbarui grafik, tanpa menampilkan pesan lain seperti "tidak ada data"
7. WHEN Admin mengubah filter, THE Aplikasi SHALL memastikan bahwa data yang ditampilkan hanya mencakup data dari Unit dan Shift yang dipilih dalam rentang tanggal yang ditentukan

### Requirement 6: Manajemen Unit

**User Story:** Sebagai Admin, saya ingin mengelola data unit keperawatan, sehingga unit-unit yang ada di rumah sakit dapat tercatat dan digunakan dalam pelaporan.

#### Acceptance Criteria

1. WHEN Admin membuka halaman manajemen Unit, THE Aplikasi SHALL menampilkan daftar semua Unit yang terdaftar beserta nama Unit dan status aktifnya
2. WHEN Admin menambahkan Unit baru dengan nama yang terdiri dari 2 hingga 50 karakter dan hanya mengandung huruf, angka, serta spasi, THE Aplikasi SHALL menyimpan Unit baru ke database dan menampilkan notifikasi sukses
3. IF Admin menambahkan Unit dengan nama yang sudah ada (perbandingan case-insensitive), THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan nama unit sudah terdaftar
4. WHEN Admin mengedit nama Unit dengan nilai yang valid, THE Aplikasi SHALL memperbarui data Unit di database dan menampilkan notifikasi sukses
5. IF Admin menghapus Unit yang masih memiliki data laporan, THEN THE Aplikasi SHALL menampilkan dialog konfirmasi peringatan yang menginformasikan jumlah laporan terkait dan meminta konfirmasi sebelum penghapusan
6. WHEN Admin mengkonfirmasi penghapusan Unit pada dialog konfirmasi, THE Aplikasi SHALL menghapus Unit dari database dan menampilkan notifikasi sukses
7. IF Admin membatalkan penghapusan pada dialog konfirmasi, THEN THE Aplikasi SHALL menutup dialog tanpa melakukan penghapusan dan mempertahankan data Unit tanpa perubahan
8. IF Admin mengisi nama Unit dengan kurang dari 2 karakter atau lebih dari 50 karakter, THEN THE Aplikasi SHALL menampilkan pesan validasi yang mengindikasikan panjang nama harus antara 2 hingga 50 karakter
9. THE Aplikasi SHALL memastikan bahwa setiap Unit memiliki nama yang unik dalam sistem

### Requirement 7: Manajemen Pengguna

**User Story:** Sebagai Admin, saya ingin mengelola akun pengguna (perawat), sehingga setiap perawat memiliki akses yang sesuai dengan unit dan perannya.

#### Acceptance Criteria

1. WHEN Admin membuka halaman manajemen pengguna, THE Aplikasi SHALL menampilkan daftar semua Perawat yang terdaftar beserta nama, username, Unit yang ditugaskan, dan status akun (aktif/nonaktif)
2. WHEN Admin mengisi field username, password (minimal 8 karakter), nama lengkap, dan Unit penugasan lalu menekan tombol simpan, THE Aplikasi SHALL menyimpan data Perawat baru ke database dan menampilkan notifikasi sukses
3. IF penyimpanan data Perawat gagal karena gangguan koneksi atau kesalahan database, THEN THE Aplikasi SHALL menampilkan pesan error generik dan tidak menyimpan data Perawat
4. IF Admin menambahkan Perawat dengan username yang sudah terdaftar, THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan username sudah digunakan
5. THE Aplikasi SHALL membatasi setiap akun pengguna pada tepat satu peran: Admin atau Perawat, dimana Admin memiliki akses ke halaman manajemen pengguna dan Unit, sedangkan Perawat hanya memiliki akses ke Form_Input dan Dashboard
6. WHEN Admin mengubah Unit penugasan Perawat, THE Aplikasi SHALL memperbarui data penugasan di database dan menampilkan notifikasi sukses
7. IF Admin menonaktifkan akun Perawat, THEN THE Aplikasi SHALL mencegah Perawat tersebut login ke Aplikasi, menghapus semua sesi aktif Perawat tersebut, dan mempertahankan seluruh data laporan yang telah diinput oleh Perawat tersebut
8. WHEN Admin mengaktifkan kembali akun Perawat yang sebelumnya nonaktif, THE Aplikasi SHALL memungkinkan Perawat tersebut untuk login kembali ke Aplikasi
9. THE Aplikasi SHALL menyimpan password Perawat dengan menggunakan hashing yang aman (bcrypt atau setara) dan tidak menampilkan password dalam plaintext di halaman manajemen pengguna

### Requirement 8: Responsivitas Tampilan

**User Story:** Sebagai Perawat atau Admin, saya ingin mengakses aplikasi dari perangkat mobile maupun desktop, sehingga saya dapat menginput data atau melihat laporan dari mana saja.

#### Acceptance Criteria

1. THE Aplikasi SHALL menampilkan layout yang responsif pada layar dengan lebar 320px hingga 1920px, di mana seluruh konten halaman (kecuali grafik pada halaman Laporan yang dapat di-scroll horizontal) dapat diakses tanpa horizontal scrolling yang tidak perlu dan semua teks dapat dibaca tanpa perlu zoom
2. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menyesuaikan tampilan navigasi menjadi menu hamburger yang dapat dibuka dan ditutup melalui tap
3. THE Aplikasi SHALL menggunakan komponen UI dari shadcn/ui yang sudah mendukung responsivitas
4. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menampilkan grafik pada halaman Laporan yang dapat di-scroll secara horizontal dengan indikator visual yang jelas bahwa konten dapat di-scroll pada semua ukuran viewport di mana grafik dapat di-scroll
5. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menampilkan Form_Input dalam layout satu kolom dengan semua field dan tombol yang memiliki ukuran tap target minimal 44x44px
6. IF ukuran tap target 44x44px menyebabkan konflik dengan layout satu kolom, THEN THE Aplikasi SHALL memprioritaskan layout satu kolom dan mengizinkan tap target yang lebih kecil dengan tetap memastikan usability yang baik
7. THE Aplikasi SHALL menggunakan Tailwind CSS untuk styling dan memastikan konsistensi visual di semua halaman dan perangkat

### Requirement 9: Dashboard

**User Story:** Sebagai Perawat atau Admin, saya ingin melihat ringkasan informasi penting saat membuka aplikasi, sehingga saya dapat dengan cepat memahami status sistem dan mengakses fitur yang saya butuhkan.

#### Acceptance Criteria

1. WHEN Perawat atau Admin membuka halaman Dashboard, THE Aplikasi SHALL menampilkan halaman yang berisi ringkasan informasi relevan sesuai dengan peran pengguna
2. WHEN Perawat membuka Dashboard, THE Aplikasi SHALL menampilkan informasi tentang unit yang ditugaskan, shift aktif saat ini, dan akses cepat ke Form_Input
3. WHEN Admin membuka Dashboard, THE Aplikasi SHALL menampilkan ringkasan statistik seperti jumlah unit, jumlah pengguna aktif, dan akses cepat ke halaman manajemen Unit, manajemen Pengguna, dan Laporan; IF pengguna yang login adalah Perawat, THEN THE Aplikasi SHALL mencegah akses ke halaman manajemen Unit, manajemen Pengguna, dan Laporan
4. THE Dashboard SHALL menampilkan navigasi yang jelas ke semua fitur yang dapat diakses oleh pengguna sesuai dengan perannya, dan SHALL mencegah akses ke fitur yang tidak sesuai dengan peran pengguna

### Requirement 10: Keamanan Data

**User Story:** Sebagai Admin, saya ingin memastikan bahwa data pasien dan informasi pengguna dilindungi dengan baik, sehingga privasi dan keamanan data terjaga.

#### Acceptance Criteria

1. THE Aplikasi SHALL menggunakan HTTPS untuk semua komunikasi antara client dan server
2. THE Aplikasi SHALL mengimplementasikan CSRF protection pada semua form yang mengubah data
3. THE Aplikasi SHALL melakukan validasi input pada sisi server untuk mencegah SQL injection dan XSS attacks
4. THE Aplikasi SHALL menyimpan password pengguna dengan menggunakan hashing yang aman (bcrypt atau setara) dan tidak pernah menyimpan password dalam plaintext
5. THE Aplikasi SHALL membatasi akses ke data berdasarkan peran pengguna, di mana Perawat hanya dapat melihat dan mengedit data untuk unit yang ditugaskan
6. WHEN pengguna logout atau sesi berakhir karena inaktivitas atau expiration, THE Aplikasi SHALL menghapus semua informasi sesi dari server dan client; HOWEVER, THE Aplikasi SHALL hanya menghapus sesi pada event logout eksplisit atau expiration formal, bukan pada event seperti penutupan browser
