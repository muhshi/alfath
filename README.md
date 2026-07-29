<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Deployment

Untuk melakukan akumulasi deploy produksi secara otomatis pada server, jalankan script deployment:

```bash
chmod +x deploy.sh
./deploy.sh
```

Script `deploy.sh` secara otomatis mengeksekusi:
1. Peringatan mode pemeliharaan (`php artisan down`).
2. Pull update code terbaru dari git (`git pull origin main`).
3. Optimalisasi instalasi dependensi PHP & JS (`composer install --no-dev`, `pnpm/npm run build`).
4. Migrasi basis data aman (`php artisan migrate --force`).
5. Pembersihan & pembaharuan cache produksi (`config`, `route`, `view`, `event`).
6. Pengaktifan kembali sistem (`php artisan up`).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Changelog

### 2026-07-29
- **Executive Public Dashboard Sensus Ekonomi 2026**:
  - Menambahkan controller `ExecutiveDashboardController` untuk agregasi data 8 tabel (`alokasi_pengawas`, `master_petugas`, `monitoring_se2026`, `monitoring_sls_se2026`, `ub_pencacah`, `ub_pengawas`, `usaha_keluarga`, `usaha_perusahaan`).
  - Menambahkan rute publik `/dashboard-se2026` di `routes/web.php`.
  - Pembaruan Judul & Keterangan Beban: Mengubah judul KPI 1 menjadi **Total Beban Pendataan** dengan subteks *"Campuran KK Berusaha, KK Tidak Berusaha & Bangunan Khusus Usaha"*.
  - Pembaruan Rincian SDM (1.000 Petugas): Menambahkan rincian tim **UM/UMK (992): 876 PPL + 116 PML** dan **Usaha Besar (8): 6 PPL + 2 PML**.
  - Pembaruan Identitas Logo BPS: Menggunakan logo resmi `assets/logo_bps.png` pada header **Dashboard Executive SE2026** dan **Beranda ALFATH**.
  - Redesain Halaman Utama (Clean Corporate Style): Menghilangkan gradasi warna menyolok dan mengubah tampilan Beranda ALFATH menjadi desain korporat eksekutif yang sangat bersih (*Pure White Card*, aksen *Border Left SE Orange*, typography *Outfit*, dan lencana *BPS Kabupaten Demak*).
  - Penegasan Kepanjangan ALFATH: Menyajikan nama resmi **ALFATH — Aplikasi Fasih Monitoring Harian** secara jelas pada banner utama.
  - Pengurutan & Pagination Tabel Survei (`home.blade.php`): Mengurutkan data survei mengutamakan status **Aktif Berjalan** terlebih dahulu, disusul urutan tanggal periode/pembuatan terkini. Menambahkan dukungan **Pagination (10 Data per Halaman)** untuk antisipasi volume survei skala besar di masa mendatang.
  - Penyesuaian Pagination Tabel Survei (5 Data per Halaman): Mengubah batasan pagination menjadi 5 survei per halaman (`paginate(5)`) serta membuat bilah footer navigasi pagination dan indikator jumlah data selalu tampil konsisten di bagian bawah tabel.
  - Menampilkan Status **Terakhir Diperbarui**: Mengambil nilai `MAX(updated_at)` dari tabel `monitoring_se2026` dan memunculkannya pada header badge utama dashboard (contoh: `Terakhir Diperbarui: 29 Jul 2026 | 09:20 WIB`).
  - Orientasi Chart Kecamatan (Vertical Bar Chart): Menampilkan 14 Kecamatan di **Sumbu X** dan Persentase Capaian (%) di **Sumbu Y**.
  - Perataan Output Skrip Deploy (`deploy.sh`): Membungkam pesan peringatan permission `Operation not permitted` dari berkas kompilasi view milik pengguna Docker `www-data` (`chmod 2>/dev/null`) agar hasil keluaran terminal bersih.
  - Pembaruan Subjudul Card Dashboard: Mengubah subjudul kartu Keberadaan Bangunan Usaha Perusahaan dari *"Sektor Komersial & Industri"* menjadi *"Bangunan Khusus Usaha dan Usaha Besar"*.
  - Label Nilai & Persentase Langsung pada Grafik: Mengintegrasikan pustaka `chartjs-plugin-datalabels` untuk menampilkan nilai persentase secara langsung di atas bar chart kecamatan (misal: `85.4%`), di dalam potongan doughnut chart (misal: `92.2%`), dan di titik chart tren harian agar lebih mudah dibaca secara langsung.
  - Kartu KPI Sebaran Progres Petugas: Menambahkan kartu KPI baru pada jajaran header yang mengelompokkan jumlah petugas berdasarkan persentase capaian pendataannya (`< 50%`, `50% - 70%`, dan `> 70%`).
  - Perbaikan Agregasi Keberadaan Usaha: Memperbarui logika query pada `ExecutiveDashboardController` untuk menghitung akumulasi status `Ditemukan` (`Ditemukan` + `Baru`) dan `Tidak Ditemukan / Tutup` (`Tutup` + `Ganda` + `Tidak Ditemukan`) pada tabel `usaha_keluarga` dan `usaha_perusahaan` secara presisi mengikuti kueri agregasi Metabase.
  - Penyesuaian Teks Legenda Grafik & Kartu: Mengubah seluruh label keterangan pada kartu dan grafik Temuan Usaha Keluarga serta Bangunan Usaha Perusahaan dari *"Operasional/Ditemukan"* & *"Tutup/Alih Fungsi"* menjadi **"Ditemukan / Baru"** dan **"Tutup / Ganda / Tidak Ditemukan"**.

