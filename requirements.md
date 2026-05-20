# Requirements Document

## Introduction

Aplikasi website pelaporan keperawatan untuk RSI Muhammadiyah 2 Kendal. Aplikasi ini memungkinkan perawat untuk menginput data pasien per shift (pagi, siang, malam) dan menampilkan laporan dalam bentuk grafik/chart berdasarkan unit dan rentang tanggal yang ditentukan.

Tech stack: Laravel, MySQL, Tailwind CSS, shadcn/ui, Recharts.

## Glossary

- **Aplikasi**: Aplikasi website pelaporan keperawatan (Nursing Report App)
- **Perawat**: Pengguna yang menginput data pasien per shift
- **Unit**: Unit/ruangan keperawatan di rumah sakit (contoh: ICU, Rawat Inap, dll)
- **Shift**: Pembagian waktu kerja perawat dalam satu hari, terdiri dari 3 shift: Pagi (07:00-14:00), Siang (14:00-21:00), Malam (21:00-07:00)
- **Laporan**: Data rekapitulasi pasien yang ditampilkan dalam bentuk grafik/chart
- **Dashboard**: Halaman utama yang menampilkan ringkasan laporan
- **Form_Input**: Halaman untuk menginput data pasien per shift
- **Admin**: Pengguna dengan hak akses penuh untuk mengelola data unit dan pengguna

## Requirements

### Requirement 1: Autentikasi Pengguna

**User Story:** Sebagai Perawat, saya ingin login ke aplikasi dengan akun saya, sehingga data yang saya input tercatat atas nama saya dan unit saya.

#### Acceptance Criteria

1. WHEN Perawat memasukkan username dan password yang sesuai dengan data akun terdaftar, THE Aplikasi SHALL mengautentikasi Perawat dan mengarahkan ke halaman Dashboard dalam waktu maksimal 3 detik
2. IF Perawat memasukkan username atau password yang tidak sesuai dengan data akun terdaftar, THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan kredensial tidak valid tanpa mengungkapkan field mana yang salah
3. IF Perawat gagal login sebanyak 5 kali berturut-turut, THEN THE Aplikasi SHALL memblokir seluruh percobaan login pada akun tersebut selama 15 menit dan menampilkan pesan yang mengindikasikan akun terkunci sementara
4. IF sesi login Perawat tidak aktif selama 60 menit, THEN THE Aplikasi SHALL mengakhiri sesi dan mengarahkan Perawat ke halaman login
5. THE Aplikasi SHALL menyimpan informasi sesi login termasuk identitas Perawat dan Unit yang ditugaskan
6. WHEN Perawat menekan tombol logout, THE Aplikasi SHALL mengakhiri sesi login dan mengarahkan Perawat ke halaman login

### Requirement 2: Input Data Pasien Per Shift

**User Story:** Sebagai Perawat, saya ingin menginput data pasien pada shift saya, sehingga data keperawatan tercatat dengan lengkap dan akurat.

#### Acceptance Criteria

1. WHEN Perawat membuka halaman Form_Input, THE Aplikasi SHALL menampilkan form input data pasien berdasarkan unit tertentu (Rawat Inap, Rawat Jalan, IGD, ICU, dan VK)
2. WHEN Perawat mengisi semua field wajib dengan nilai numerik dalam rentang 0 sampai 9999 dan menekan tombol simpan, THE Aplikasi SHALL menyimpan data pasien ke database dengan mencatat tanggal, Shift, Unit, dan identitas Perawat
3. IF Perawat menekan tombol simpan tanpa mengisi satu atau lebih field wajib, THEN THE Aplikasi SHALL menampilkan pesan validasi secara inline pada setiap field yang belum diisi tanpa menghapus data yang sudah diisi pada field lainnya
4. WHEN data berhasil disimpan, THE Aplikasi SHALL menampilkan notifikasi sukses selama 3 detik dan mengosongkan form untuk input data berikutnya
5. THE Form_Input SHALL menampilkan pilihan Shift (Pagi, Siang, Malam) dengan default sesuai waktu saat ini berdasarkan pembagian: Pagi (07:00-14:00), Siang (14:00-21:00), Malam (21:00-07:00)
6. IF penyimpanan data gagal karena gangguan koneksi atau kesalahan server, THEN THE Aplikasi SHALL menampilkan pesan error yang menjelaskan kegagalan penyimpanan dan mempertahankan seluruh data yang telah diisi pada form
7. IF Perawat menyimpan data untuk kombinasi tanggal, Shift, dan Unit yang sudah memiliki data tersimpan, THEN THE Aplikasi SHALL menampilkan konfirmasi apakah Perawat ingin memperbarui data yang sudah ada atau membatalkan penyimpanan
8. WHEN data pasien berhasil diinput, THEN THE Aplikasi SHALL menampilkan textboxt yang berisi nilai dari data pasien yang telah diinput pada shift tersebut dan ada function untuk copy text tersebut.
9. WHEN Perawat input data pasien pada Form_Input, THE Aplikasi SHALL KNOW unit dari perawat tersebut, dan dengan field wajib meliputi: unit IGD [jumlah pasien rawat inap, jumlah pasien rawat jalan, jumlah pasien pulang paksa, keterangan penyakit rawat inap, keterangan penyakit rawat jalan, total = jml rawat jalan + jml rawat inap + jml pulang paksa], unit Rawat Inap [jumlah pasien anak, jumlah pasien Dalam, jumlah pasien Saraf, jumlah pasien Obsgyn, jumlah pasien Bedah, jumlah inden, jumlah RPL, jumlah Pasien Pulang, total (terisi otomatis dari total jumlah)], unit Rawat Jalan [Jumlah poli obgyn, Jumlah poli Dalam, Jumlah poli Anak, Jumlah poli Bedah, Jumlah poli Saraf, Jumlah poli Fisioterapi, total (terisi otomatis dari total jumlah)], unit VK [Jumlah pasien VK, keterangan], unit ICU [jumlah pasien anak, jumlah pasien Dalam, jumlah pasien Saraf, jumlah pasien Obsgyn, jumlah pasien Bedah, Jumlah pasien Inden, Jumlah pasien Pulang], unit HCU [jumlah pasien anak, jumlah pasien Dalam, jumlah pasien Saraf, jumlah pasien Obsgyn, jumlah pasien Bedah, Jumlah pasien Inden, Jumlah pasien Pulang] dan semua itu menampilkan Shift aktif sesuai waktu saat ini.

### Requirement 3: Pengelolaan Shift

**User Story:** Sebagai Perawat, saya ingin sistem mengenali shift saya secara otomatis, sehingga saya tidak perlu memilih shift secara manual setiap kali input data.

#### Acceptance Criteria

1. WHEN waktu saat ini (berdasarkan waktu server, zona waktu WIB/UTC+7) berada pada pukul 07:00:00 hingga sebelum 14:00:00, THE Aplikasi SHALL menetapkan Shift default sebagai "Pagi"
2. WHEN waktu saat ini berada pada pukul 14:00:00 hingga sebelum 21:00:00, THE Aplikasi SHALL menetapkan Shift default sebagai "Siang"
3. WHEN waktu saat ini berada pada pukul 21:00:00 hingga sebelum 07:00:00 keesokan harinya, THE Aplikasi SHALL menetapkan Shift default sebagai "Malam"
4. THE Aplikasi SHALL menampilkan dropdown pemilihan Shift dengan tiga opsi (Pagi, Siang, Malam) yang dapat diubah oleh Perawat kapan saja sebelum menyimpan data
5. WHEN Perawat membuka halaman Form_Input pada kunjungan baru, THE Aplikasi SHALL mengatur ulang Shift default sesuai waktu saat ini

### Requirement 4: Tampilan Laporan dalam Grafik

**User Story:** Sebagai Admin, saya ingin melihat laporan keperawatan dalam bentuk grafik/chart, sehingga saya dapat menganalisis data dengan mudah dan cepat.

#### Acceptance Criteria

1. WHEN Admin memilih rentang tanggal pada halaman Laporan, THE Aplikasi SHALL menyuruh admin untuk menentukan rentang tanggal yang akan dibandingkan kemudian menampilkan perbandingan grafik garis (line chart) dengan sumbu X berupa tanggal dan sumbu Y berupa jumlah pasien per hari sesuai rentang tanggal yang dipilih dan dibandingkan.
2. WHEN Admin memilih filter Unit tertentu, THE Aplikasi SHALL menampilkan grafik hanya untuk Unit yang dipilih
3. WHEN Admin memilih opsi "Semua Unit", THE Aplikasi SHALL menampilkan grafik dengan masing-masing Unit ditampilkan sebagai garis terpisah yang dibedakan berdasarkan warna
4. THE Aplikasi SHALL menampilkan grafik menggunakan library Recharts dengan tampilan yang responsif
5. WHEN Admin mengarahkan kursor ke elemen grafik, THE Aplikasi SHALL menampilkan tooltip yang berisi tanggal, nama Unit, jumlah pasien, dan nama Shift pada titik tersebut
6. IF tidak terdapat data pada rentang tanggal dan filter yang dipilih, THEN THE Aplikasi SHALL segera menampilkan area grafik kosong disertai pesan yang menginformasikan bahwa tidak ada data untuk filter yang dipilih, tanpa menampilkan indikator loading
7. WHEN grafik sedang memuat data, THE Aplikasi SHALL menampilkan indikator loading; IF waktu loading melebihi 5 detik, THEN THE Aplikasi SHALL menyembunyikan indikator loading dan menampilkan pesan error yang menginformasikan kegagalan memuat data
8. THE Aplikasi SHALL menyediakan halaman laporan perbulan berdasarkan unit dalam bentuk candle chart

### Requirement 5: Filter dan Navigasi Laporan

**User Story:** Sebagai Admin, saya ingin memfilter laporan berdasarkan unit, shift, dan tanggal, sehingga saya dapat melihat data yang spesifik sesuai kebutuhan.

#### Acceptance Criteria

1. THE Aplikasi SHALL menyediakan filter berdasarkan Unit (mendukung pilihan satu Unit atau semua Unit), Shift (mendukung pilihan satu Shift atau semua Shift), dan rentang tanggal (tanggal mulai dan tanggal akhir, maksimal 90 hari ke belakang dari hari ini) pada halaman Laporan
2. WHEN Admin mengubah salah satu filter, THE Aplikasi SHALL memperbarui grafik dalam waktu maksimal 3 detik tanpa perlu reload halaman
3. THE Aplikasi SHALL menyimpan state filter terakhir yang digunakan selama sesi aktif sehingga navigasi antar halaman tidak mereset pilihan filter
4. WHEN halaman Laporan pertama kali dibuka dan belum ada state filter tersimpan, THE Aplikasi SHALL menampilkan data hari ini untuk semua Unit dan semua Shift sebagai default
5. IF filter yang dipilih tidak menghasilkan data, THEN THE Aplikasi SHALL menampilkan pesan informasi bahwa tidak ada data yang sesuai dengan filter yang dipilih, dan tetap menampilkan area grafik dalam keadaan kosong
6. IF Admin memilih tanggal mulai yang lebih besar dari tanggal akhir, THEN THE Aplikasi SHALL menampilkan pesan validasi bahwa rentang tanggal tidak valid dan tidak memperbarui grafik

### Requirement 6: Manajemen Unit

**User Story:** Sebagai Admin, saya ingin mengelola data unit keperawatan, sehingga unit-unit yang ada di rumah sakit dapat tercatat dan digunakan dalam pelaporan.

#### Acceptance Criteria

1. WHEN Admin membuka halaman manajemen Unit, THE Aplikasi SHALL menampilkan daftar semua Unit yang terdaftar beserta nama Unit dan status aktifnya
2. WHEN Admin menambahkan Unit baru dengan nama yang terdiri dari 2 hingga 50 karakter dan hanya mengandung huruf, angka, serta spasi, THE Aplikasi SHALL menyimpan Unit baru ke database dan menampilkan notifikasi sukses
3. IF Admin menambahkan Unit dengan nama yang sudah ada (perbandingan case-insensitive), THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan nama unit sudah terdaftar
4. WHEN Admin mengedit nama Unit dengan nilai yang valid, THE Aplikasi SHALL memperbarui data Unit di database dan menampilkan notifikasi sukses
5. IF Admin menghapus Unit yang masih memiliki data laporan, THEN THE Aplikasi SHALL menampilkan dialog konfirmasi peringatan yang menginformasikan jumlah laporan terkait
6. WHEN Admin mengkonfirmasi penghapusan Unit pada dialog konfirmasi, THE Aplikasi SHALL menghapus Unit dari database dan menampilkan notifikasi sukses
7. IF Admin membatalkan penghapusan pada dialog konfirmasi, THEN THE Aplikasi SHALL menutup dialog dan mempertahankan data Unit tanpa perubahan
8. IF Admin mengisi nama Unit dengan kurang dari 2 karakter atau lebih dari 50 karakter, THEN THE Aplikasi SHALL menampilkan pesan validasi yang mengindikasikan panjang nama harus antara 2 hingga 50 karakter

### Requirement 7: Manajemen Pengguna

**User Story:** Sebagai Admin, saya ingin mengelola akun pengguna (perawat), sehingga setiap perawat memiliki akses yang sesuai dengan unit dan perannya.

#### Acceptance Criteria

1. WHEN Admin membuka halaman manajemen pengguna, THE Aplikasi SHALL menampilkan daftar semua Perawat yang terdaftar beserta nama, username, Unit yang ditugaskan, dan status akun (aktif/nonaktif)
2. WHEN Admin mengisi field username, password (minimal 8 karakter), nama lengkap, dan Unit penugasan lalu menekan tombol simpan, THE Aplikasi SHALL menyimpan data Perawat baru ke database dan menampilkan notifikasi sukses; IF penyimpanan gagal karena gangguan koneksi atau kesalahan database, THEN THE Aplikasi SHALL menampilkan pesan error generik dan tidak menyimpan data Perawat
3. IF Admin menambahkan Perawat dengan username yang sudah terdaftar, THEN THE Aplikasi SHALL menampilkan pesan error yang mengindikasikan username sudah digunakan
4. THE Aplikasi SHALL membatasi setiap akun pengguna pada tepat satu peran: Admin atau Perawat, dimana Admin memiliki akses ke halaman manajemen pengguna dan Unit, sedangkan Perawat hanya memiliki akses ke Form_Input dan Dashboard; WHEN peran berhasil ditetapkan, THE Aplikasi SHALL tidak menampilkan pesan error
5. WHEN Admin mengubah Unit penugasan Perawat, THE Aplikasi SHALL memperbarui data penugasan di database dan menampilkan notifikasi sukses
6. IF Admin menonaktifkan akun Perawat, THEN THE Aplikasi SHALL mencegah Perawat tersebut login ke Aplikasi dan mempertahankan seluruh data laporan yang telah diinput oleh Perawat tersebut

### Requirement 8: Responsivitas Tampilan

**User Story:** Sebagai Perawat, saya ingin mengakses aplikasi dari perangkat mobile maupun desktop, sehingga saya dapat menginput data dari mana saja.

#### Acceptance Criteria

1. THE Aplikasi SHALL menampilkan layout yang responsif pada layar dengan lebar 320px hingga 1920px, di mana seluruh konten halaman (kecuali grafik pada halaman Laporan) dapat diakses tanpa horizontal scrolling dan semua teks dapat dibaca tanpa perlu zoom
2. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menyesuaikan tampilan navigasi menjadi menu hamburger yang dapat dibuka dan ditutup melalui tap
3. THE Aplikasi SHALL menggunakan komponen UI dari shadcn/ui yang sudah mendukung responsivitas
4. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menampilkan grafik pada halaman Laporan yang dapat di-scroll secara horizontal dengan indikator visual bahwa konten dapat di-scroll
5. WHILE viewport memiliki lebar 768px atau kurang, THE Aplikasi SHALL menampilkan Form_Input dalam layout satu kolom dengan semua field dan tombol yang memiliki ukuran tap target minimal 44x44px; IF ukuran tap target 44x44px menyebabkan konflik dengan layout satu kolom, THEN THE Aplikasi SHALL memprioritaskan layout satu kolom dan mengizinkan tap target yang lebih kecil
