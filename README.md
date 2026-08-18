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
  - Perbaikan Agregasi Keberadaan Usaha: Memperbarui logika query pada `ExecutiveDashboardController` untuk menghitung akumulasi status `Ditemukan` (`Ditemukan` + `Baru`) dan `Tidak Ditemukan / Tutup` (`Tutup` + `Ganda` + `Tidak Ditemukan`) pada tabel `usaha_keluarga` dan `usaha_perusahaan` secara presisi mengikuti kueri agregasi Metabase dengan membatasi kriteria tingkat Sub-SLS (`LENGTH(kode) = 16`) agar terhindar dari penggandaan hitung data ringkasan.
  - Pemisahan 5 Status Keberadaan Usaha: Memecah tampilan legenda grafik doughnut dan stat grid pada kartu **Temuan Usaha Keluarga** dan **Keberadaan Bangunan Usaha Perusahaan** menjadi 5 kategori status terpisah (**Ditemukan**, **Baru**, **Tidak Ditemukan**, **Ganda**, dan **Tutup**) lengkap dengan skema warna beriklim eksekutif.

### 2026-07-31
- **Fitur Upload & Pengolahan Data Excel Usaha (FASIH DB)**:
  - Ekstraksi Khusus Sheet Usaha: Mengekstrak hanya sheet `USAHA PERUSAHAAN` dan `USAHA KELUARGA` dari file Excel Export Progres Pendataan Sub-SLS dan mengabaikan sheet lainnya.
  - Dukungan Metabase Historical Snapshot Date Filter: Memperbarui unique index database `fasih` pada tabel `usaha_perusahaan` dan `usaha_keluarga` menjadi komposit `UNIQUE (kode, tanggal_data)`. Menjaga kelengkapan snapshot historis per tanggal agar query Metabase dapat melakukan filtering tanggal secara presisi tanpa menimpa data lama.
  - Performa Engine Python (Pandas 2-Stage Multi-Row Batch): Mengimplementasikan parser Python decoupling 2-stage yang sangat cepat (~9,6 detik untuk 33.409 baris data) menggunakan batch multi-row single query values.
  - Eloquent Models `UsahaPerusahaan` & `UsahaKeluarga`: Menambahkan model Eloquent `App\Models\UsahaPerusahaan` dan `App\Models\UsahaKeluarga` pada koneksi DB `fasih`.
  - Filament Upload Action & Artisan Command: Menambahkan tombol header action `Upload Excel Usaha` di halaman Filament Fasih Scraper dan perintah Artisan `php artisan usaha:import-excel {filepath}`.
  - Penyesuaian `deploy.sh` & `Dockerfile`: Memastikan pustaka Python `pandas`, `openpyxl`, dan `pymysql` terpasang otomatis pada container Docker produksi (`Dockerfile`) serta memastikan skrip `deploy.sh` mengakomodasi izin direktori storage & migrasi otomatis.
  - Perbaikan Tipe Properti & Signature Filament 3 Resource: Memperbaiki tipe deklarasi `$navigationGroup`, `$navigationIcon`, `$view`, serta method signature `form(Form $form)` pada `CategoryResource`, `TeamResource`, `SurveyResource`, dan `FasihScraper` agar kompatibel penuh dengan kelas induk Filament v3.
  - Pembaruan Aset Frontend Filament (Fix Livewire Intercept Error): Mempublikasikan ulang aset JS/CSS Filament 3.3 (`php artisan filament:assets`) untuk mengganti berkas aset publik yang Using method lama (seperti `Livewire.interceptMessage`), serta memastikan konfigurasi `$middleware->trustProxies(at: '*')` di `bootstrap/app.php`.
  - Perbaikan Properti Widget Filament (`ScraperLogViewer`): Mengubah deklarasi properti `$view` pada `App\Livewire\ScraperLogViewer` menjadi `protected static string $view` agar kompatibel penuh dengan kelas induk `Filament\Widgets\Widget`.
  - Penyesuaian Batas Ukuran Upload Livewire & Mime Types Excel: Menyesuaikan batas `maxSize(65536)` (64 MB) pada komponen `FileUpload` Filament dan aturan `config/livewire.php` (`temporary_file_upload.rules`), menghapus pembatasan `acceptedFileTypes` agar tidak terjadi error "file type invalid" pada browser, serta menambahkan berkas `.user.ini` (`upload_max_filesize = 64M`, `post_max_size = 64M`) untuk menaikkan batas upload PHP bawaan (2M).

### 2026-08-03
- **Perbaikan Deklarasi Properti Filament v3 Resources & Widgets**:
  - Memperbaiki tipe deklarasi `$navigationGroup`, `$navigationIcon`, dan `$heading` pada `CategoryResource`, `TeamResource`, `SurveyResource`, `FasihScraper`, `CategoryDistributionChartWidget`, dan `DailyVisitorsChartWidget` menjadi `protected static ?string` agar kompatibel penuh dengan kelas induk Filament v3 dan PHP 8.2+.
  - Mempublikasikan ulang aset JavaScript Filament 3.3 (`php artisan filament:assets`) ke direktori `public/js/filament/` untuk menghilangkan error `Livewire.interceptMessage` pada browser pasca-pull.
  - Memperbaiki properti `$view` pada `App\Livewire\ScraperLogViewer` menjadi `protected static string $view` agar sesuai dengan spesifikasi widget Filament.

### 2026-08-06
- **Integrasi Import Data Progres Pemutakhiran Keluarga SE2026 (`se2026_pemutakhiran_keluarga`) & Penataan Tabel SE2026**:
  - Penataan Prefix Nama Tabel SE2026: Merename tabel fisik `usaha_perusahaan` menjadi `se2026_usaha_perusahaan` dan `usaha_keluarga` menjadi `se2026_usaha_keluarga` pada database `fasih`.
  - Kompatibilitas Metabase (SQL View Layer): Membuat Database View `usaha_perusahaan` dan `usaha_keluarga` yang merujuk ke tabel fisik `se2026_...` agar dashboard dan query Metabase tetap berjalan 100% lancar tanpa breaking change.
  - Skema Tabel `se2026_pemutakhiran_keluarga`: Membuat migrasi tabel baru `se2026_pemutakhiran_keluarga` pada koneksi DB `fasih` untuk menampung 14 indikator progres pemutakhiran keluarga per Sub-SLS 16 digit beserta `tanggal_data`.
  - Eloquent Model `Se2026PemutakhiranKeluarga`: Menambahkan model Eloquent `App\Models\Se2026PemutakhiranKeluarga` serta memperbarui model `UsahaPerusahaan` & `UsahaKeluarga` ke nama tabel ber-prefix `se2026_`.
  - Importer Python Performa Tinggi (`process_pemutakhiran_keluarga_excel.py`): Mengembangkan script Python parser Excel fast-bulk upsert (`ON DUPLICATE KEY UPDATE`) yang mampu mengolah >8.200 baris data Sub-SLS 16 digit hanya dalam 4-5 detik.
  - Perintah Artisan `php artisan import:pemutakhiran-keluarga`: Menambahkan command Laravel `import:pemutakhiran-keluarga {file}` untuk pengolahan import Excel dari terminal maupun background job.
- **Dashboard Tabel Petugas SE2026 (`/dashboard-pengolahan`)**:
  - Halaman Dashboard & Rute Baru: Menambahkan rute `/dashboard-pengolahan` (`dashboard.pengolahan`) dan controller `PengolahanController@index` dengan penamaan baru **Tabel Petugas SE2026**.
  - Agregasi Multi-Tabel SE2026: Menggabungkan data dari `monitoring_se2026`, `alokasi_pengawas`, `master_petugas`, `se2026_usaha_perusahaan`, `se2026_usaha_keluarga`, dan `se2026_pemutakhiran_keluarga` per Petugas (Pencacah) & Kecamatan.
  - Koreksi Perhitungan **Muatan Murni**: Mengabaikan data `se2026_usaha_keluarga` dalam perhitungan Muatan Murni dan menetapkan formula `Muatan Murni = (Usaha Perusahaan Ditemukan + Baru) + (Keluarga Ditemukan + Baru)`.
  - Penambahan Kolom **Belum Dikerjakan & Usaha Keluarga**: Menambahkan kolom **Belum Dikerjakan** (`Beban Saat Ini - Total Submit`) berwarna merah soft tepat di sebelah kolom *Muatan Murni ⭐*, serta menampilkan kolom informasi **Usaha Keluarga (Ditemukan)** di sebelah kolom Usaha Perusahaan (lengkap dengan fitur *sorting* & ekspor Excel `.xlsx`).
  - Pencarian & Filter Lengkap: Menyediakan form pencarian real-time (Nama/Email Petugas & Pengawas), filter 14 Kecamatan BPS Demak, serta filter tanggal data snapshot.
  - Interactive Column Sorting: Menyediakan pengurutan interaktif (Ascending/Descending ▲/▼) pada header tabel untuk seluruh kolom metrik termasuk Nama Pengawas, Belum Dikerjakan, dan Usaha Keluarga.
  - **Fitur Export Excel Native (`.xlsx`) Hasil Filter**: Memasang dependensi `phpoffice/phpspreadsheet` untuk memfasilitasi ekspor file Microsoft Excel murni (`.xlsx`) berdesain profesional (banner header, warna kolom `Muatan Murni ⭐`, formatting ribuan `#,##0` & persentase `0.00%`, serta auto-fit lebar kolom) sesuai filter & pengurutan yang aktif.
  - **Penataan Posisi Kolom Muatan Murni & Kerapian Pagination**: Memindahkan kolom **Muatan Murni ⭐** menjadi kolom pertama kategori metrik perhitungan (tepat setelah nama pengawas) baik pada tabel dashboard maupun pada file ekspor Excel. Serta merapikan tampilan footer pagination menggunakan `pagination::bootstrap-5` tanpa duplikasi teks penjelas.
  - **Kompatibilitas Upgrade Filament v5 (Fix Deployment `deploy.sh` Error)**:
    - Memperbarui tipe deklarasi properti `$navigationGroup` (menjadi `\UnitEnum|string|null`) dan `$navigationIcon` (menjadi `\BackedEnum|string|null`) pada `CategoryResource`, `TeamResource`, `SurveyResource`, dan `FasihScraper` agar kompatibel penuh dengan kelas induk Filament v5 (`Filament\Resources\Resource` dan `Filament\Pages\Page`).
    - Memperbarui signature method `form(Schema $schema): Schema` pada `CategoryResource`, `TeamResource`, dan `SurveyResource` menggunakan `Filament\Schemas\Schema` sesuai standar Filament v5.
    - Memperbarui properti `$view` pada `App\Filament\Pages\FasihScraper` menjadi instance property (`protected string $view`) sesuai kelas induk `Filament\Pages\Page`.
    - Memperbarui properti `$heading` pada `CategoryDistributionChartWidget` dan `DailyVisitorsChartWidget` menjadi instance property (`protected ?string $heading`) sesuai kelas induk `Filament\Widgets\ChartWidget`.
    - Menyelesaikan error `artisan package:discover` (code 255) yang terjadi saat script `deploy.sh` dijalankan di server.
### 2026-08-07
- **Tabel Petugas SE2026 (High-Contrast Segmented Pill Tabs UI, DataTables Integration, Tabel Agregasi PML & Multi-Sheet Excel Export)**:
  - Desain Tab Berkontras Tinggi (*High-Contrast Segmented Pill Tabs UI*): Mengubah tampilan navigasi Tab di atas kartu tabel menjadi komponen *segmented control pill tabs* dengan kontras warna yang sangat tajam dan jelas. Tab Aktif memiliki warna latar belakang solid yang mencolok (Sky Blue untuk PPL, Indigo untuk PML, dan Emerald Green untuk SLS) lengkap dengan *badge counter pill* transparan serta efek bayangan (*glowing shadow*), sehingga user dapat dengan mudah mengenali Tab mana yang sedang aktif.
  - Tabel Agregasi PML (Pengawas): Menambahkan kueri `getPmlQuery` di `PengolahanController.php` dan **Tab 2: Ringkasan Per PML (Pengawas)** pada UI untuk mengagregasikan seluruh hasil pendataan (Muatan Murni, Belum Dikerjakan, Beban, Total Submit, % Progres, Usaha Perusahaan, Usaha Keluarga, dan Pemutakhiran Keluarga) berdasarkan Pengawas (PML), lengkap dengan statistik Jumlah PPL dan Jumlah SLS yang didampingi.
  - Navigasi 3 Tab Terpadu: Menyiapkan 3 Tab utama pada halaman `/dashboard-pengolahan` yaitu **Tab 1: Ringkasan Per PPL (Pencacah)**, **Tab 2: Ringkasan Per PML (Pengawas)**, dan **Tab 3: Alokasi Per SLS / Sub-SLS**.
  - Ekspor Excel 3 Sheet (`.xlsx`): Memperbarui fitur ekspor Excel untuk menghasilkan 3 Sheet sekaligus dalam 1 workbook (`Sheet 1: Ringkasan PPL`, `Sheet 2: Ringkasan PML`, dan `Sheet 3: Rincian Alokasi Per SLS`).
  - Eliminasi Tampilan Berantakan (FOUC Prevention & Loading Overlay): Menambahkan animated loading overlay (`#table-loading-overlay`) dan transisi CSS halus (`datatable-pre-init` & `datatable-initialized`) sehingga saat halaman di-refresh/filter, tidak ada lagi tampilan tabel unstyled yang berantakan. Tabel ditampilkan secara mulus (*smooth fade-in*) setelah DataTables selesai diproses.
  - Bebas N+1 Query (100% Optimized SQL JOINs): Memastikan kueri `getFilteredQuery`, `getPmlQuery`, dan `getSlsQuery` di `PengolahanController.php` dieksekusi murni menggunakan SQL `LEFT JOIN` dan `leftJoinSub` (hanya 3 kueri SQL per request), menjamin tidak ada N+1 query problem.
  - Resolusi Nama SLS Multi-Tabel (`COALESCE` Fallback): Memperbarui kueri `getSlsQuery` untuk mengambil nama SLS secara berlapis dari 3 tabel (`monitoring_sls_se2026.nmsls`, `sipw.nama_sls`, dan `se2026_pemutakhiran_keluarga.sub_sls`). Jika nama SLS bernilai `NULL` atau `-`, sistem otomatis mengambil nama dari Wilkerstat / Pemutakhiran Keluarga atau menampilkan format `SLS [Kode]`.
  - Penataan Header Tabel Ringkas & Centered: Mempersempit lebar kolom tabel, merata-tengahkan judul header (`text-align: center !important`), dan menyusun judul panjang menjadi berbaris ke bawah (`white-space: normal`, `<br>`, dan padding compact) agar tampilan proporsional dan tidak stelling.
  - Penanganan Error AMD `define` & jQuery Collision: Memuat instance jQuery terisolasi dengan `jQuery.noConflict(true)` dan IIFE wrapper untuk menjamin fungsi `$.fn.DataTable` selalu siap tanpa terganggu oleh bundling scripts lain.
### 2026-08-10
- **Optimasi Performa Database & Pemulihan Tab Ranking Kinerja Petugas**:
  - Restorasi Tab **Ranking Kinerja Petugas (🏆)**: Mengembalikan Tab 4 Ranking Kinerja Petugas beserta kalkulasi Skor Kinerja (0-100), Target Milestone 95% s.d. 20 Agustus, indikator laju harian, serta sistem warning anomali SLS (Usaha Perusahaan < 5% / Usaha Keluarga < 10%) & 3-day stagnancy signal.
  - Pemulihan 4 Tab Utama: Menjamin ke-4 tab (`Tab 1: PPL`, `Tab 2: PML`, `Tab 3: SLS`, dan `Tab 4: Ranking Kinerja`) berfungsi 100% normal dan lancar saat berpindah tab.
  - Database Performance Indexes (`2026_08_10_000001_add_performance_indexes_se2026`): Menambahkan composite index `idx_tgl_email` pada `monitoring_se2026(tanggal_tarik, email_pencacah)` dan `idx_email_pengawas` pada `alokasi_pengawas.email_pengawas`. Berhasil memotong waktu eksekusi agregasi dari >30 detik (timeout) menjadi ~17 detik tanpa menghilangkan fitur ranking maupun data statistik penting.
  - **Sistem Catatan & Approval Anomali Usaha SLS SE2026**:
    - Otomasi Resolve Probing Usaha: Jika data pendataan bertambah (UP ≥ 5% / UK ≥ 10%) akibat update scraping/import, warning anomali pada SLS tersebut **hilang/teratasi secara otomatis**.
    - Fitur Input Catatan Klarifikasi Petugas: Menambahkan modal interaktif `modalAnomaliDetail` pada Dashboard Pengolahan dengan tombol **"✏️ Beri Catatan Klarifikasi"** untuk SLS yang datanya tidak berubah (misal: kawasan persawahan/pemukiman non-usaha) dan mengirim via AJAX POST `/dashboard-pengolahan/catatan-anomali`.
    - Halaman Approval Admin Filament (`Se2026AnomaliCatatanResource`): Menambahkan resource admin Filament baru di kelompok *Sensus Ekonomi 2026* untuk menyetujui (Approve) atau menolak (Reject) catatan klarifikasi petugas, lengkap dengan *navigation badge counter* pengajuan Pending berwarna merah.
    - Fix Filament Action Class Error: Memperbaiki import namespace `Filament\Actions` di `Se2026AnomaliCatatanResource.php` untuk mengatasi error `Class "Filament\Tables\Actions\Action" not found`.
    - Hirarki Badge Warning Anomali Usaha: Memperbarui logika status badge agar badge `🚨 X SLS Belum Ditindaklanjuti` **tetap tampil menonjol** selama masih terdapat SLS yang belum diberi catatan klarifikasi (badge `⏳ Menunggu Approval` hanya tampil apabila **seluruh SLS** pada petugas tersebut telah dikirimkan catatannya).
    - Fix Upload Excel Failed Error (Limit Size PHP & Livewire Logging): Meng-upgrade `upload_max_filesize` dan `post_max_size` dari default `2M`/`8M` menjadi **`128M`** (via `php.ini`, `.user.ini`, & `public/.htaccess`), membuat direktori `storage/app/livewire-tmp` & `storage/app/private/uploads/excel-se2026`, menambahkan instrumen logging diagnostik penuh di `storage/logs/laravel.log`, serta menerapkan *dynamic APP_URL host matching* pada `AppServiceProvider.php` untuk mencegah kegagalan verifikasi signed URL (HTTP 401).
    - Fitur Freeze Header Tabel (*Sticky Table Header & Vertical Scroll Container*): Menambahkan CSS `max-height: calc(100vh - 270px); overflow-y: auto` pada container `.table-responsive` serta `position: sticky; top: 0; z-index: 15` dengan background solid dan *inset shadow divider* pada ke-4 tabel di Dashboard Pengolahan (`Tab PPL`, `Tab PML`, `Tab SLS`, & `Tab Ranking Kinerja`). Tabel kini memiliki *vertical scrollbar* internal tersendiri sehingga saat pengguna melakukan scroll pada data tabel, header tabel tetap terkunci di bagian atas box tabel tanpa menggeser layout halaman utama.
    - Menambahkan method `getTargetDateForTable()` untuk memfilter `tanggal_data` secara presisi sesuai `$selectedDate` aktif (atau snapshot terbaru), sehingga perhitungan Muatan Murni kembali 100% akurat.
    - **Koreksi Pemetaan Kolom Parser Excel FASIH (`ImportExcelUsaha.php`)**: Memperbaiki pergeseran indeks kolom Excel FASIH pada sheet `USAHA PERUSAHAAN` (kolom CAPI Ditemukan, Tutup, Ganda, Tidak Ditemukan, Baru) dan `USAHA KELUARGA` (kolom Ditemukan, Tutup, Ganda, Tidak Ditemukan, Baru, Usaha Dalam Keluarga) di mana data 61.089 *Tidak Ditemukan* sebelumnya tersimpan sebagai *Baru* sehingga membengkakkan Muatan Murni dan membuat status Tidak Ditemukan hilang. Data Excel telah di-import ulang dan kini 100% tepat sesuai indikator FASIH.

### 2026-08-13
- **Peningkatan Dashboard Pengolahan & Sistem SE2026**:
  - **Peningkatan Batas Upload File Excel di Docker & FrankenPHP**: Menambahkan konfigurasi `request_body { max_size 100MB }` pada `Caddyfile` dan `uploads.ini` (100MB) pada `Dockerfile` agar unggah file Excel FASIH ukuran besar (hingga 64MB+) di server berbasis Docker/FrankenPHP berjalan lancar tanpa error upload.
  - **Penyesuaian Istilah Usaha: BKU & UK**: Memperbarui istilah **UP (Usaha Perusahaan)** menjadi **BKU (Bangunan Khusus Usaha)** di seluruh kartu KPI, header tabel, modal detail anomali, serta berkas ekspor Excel, sedangkan istilah **UK (Usaha Keluarga)** tetap dipertahankan.
  - **Reposisi Tab Utama: Ranking Kinerja Menjadi Tab 1**: Memindahkan **Ranking Kinerja Petugas** menjadi Tab Utama pertama (Tab 1) yang aktif secara default saat membuka Dashboard Pengolahan, serta menjadikannya Sheet 1 pada berkas ekspor Excel.
  - **Kalkulasi Stagnan Dinamis & Perlindungan Petugas On-Track**: Menghitung secara fleksibel jumlah hari/snapshot berturut-turut di mana submit petugas tidak bertambah (`🚨 Stagnan X hari`). Petugas yang capaiannya sudah mencapai/melebihi Target Harian Standar (`capaianPct >= dynamicTargetPct`) dilindungi dengan status **`✅ On-Track`** (bebas dari badge Stagnan merah), sehingga petugas yang mencuri start/progres tinggi tidak merasa dihukum saat beristirahat 1-2 hari.
  - **Tampilan Submit Harian (`submit X/hari`) & Pertambahan Draft (`+X draft/hari`) pada Cell Status Warning**: Menampilkan informasi laju submit selisih harian (`submit X/hari`) berdampingan dengan badge pertambahan draft harian (`+X draft/hari`) di bagian atas cell kolom Status Warning di tabel Ranking Kinerja Petugas, di atas badge status (`Stagnan X hari`, `Progres Lambat`, `On-Track`, atau `Selesai 100%`).
  - **Apresiasi Data Draft & Persentase Gabungan**: Menghitung `total_draft`, `pct_draft`, dan persentase gabungan `pct_submit_draft` (`(Submit + Draft) / Beban * 100`). Menampilkan statistik `+Draft: X%` pada kartu KPI utama "TOTAL SUBMIT" serta pada kolom persentase capaian di tabel PPL, PML, dan Ranking Kinerja Petugas agar progres draft petugas mendapatkan apresiasi.
  - **Pembaruan Ekspor Excel**: Memperbarui format ekspor Excel Sheet Ranking Kinerja untuk menyertakan angka submit harian, pertambahan draft harian, dan durasi hari stagnan secara bersih tanpa teks membingungkan (`stagnan 0 hari`).

### 2026-08-17
- **Penyandingan Data Patokan Wilkerstat 2025 (KK & Usaha) dengan Hasil Pendataan SE2026 & Integrasi Anomali SLS**:
  - **Penyandingan Data & Indikator Komparasi Intuitif**:
    - **Data Keluarga (KK)**: Menyandingkan Muatan Keluarga Ditemukan + Baru SE2026 (`pk_ditemukan`) dengan data KK Wilkerstat 2025 (`muatan_kk`).
      - Jika `KK SE ≥ KK Wilkerstat`: Status **Aman / Lebih Baik** (`✅ +X.X%`).
      - Jika `KK SE < KK Wilkerstat`: Warning `⚠️ -X.X%` **hanya muncul jika penurunannya > 5%**. Jika selisih ≤ 5%, tetap dianggap wajar/aman (`✅ -X.X%`).
    - **Data Usaha (BKU + UK)**: Menyandingkan Total Usaha SE2026 (`BKU + UK`) dengan patokan Usaha Wilkerstat 2025 (`muatan_usaha`).
      - Jika `Usaha SE ≥ Usaha Wilkerstat`: Status **Aman / Lebih Baik (Optimal)** (`✅ ≥ Wilkerstat (+X.X%)`).
      - Jika `Usaha SE < Usaha Wilkerstat`: Warning `⚠️ < Wilkerstat (-X.X%)` **hanya muncul jika penurunannya > 5%**.
    - **Penyesuaian Bahasa**: Mengeliminasi istilah teknis "Deviasi" menjadi perbandingan langsung yang mudah dipahami oleh pengguna umum.
  - **Integrasi Penuh ke Modal Detail & Tindak Lanjut Anomali SLS (`#modalAnomaliDetail`)**:
    - Memperluas modal dialog menjadi `modal-xl` agar informasi perbandingan tertata lega dan mudah dianalisis.
    - Menampilkan tabel komparasi detail berisi: Hasil Pendataan SE2026 (Muatan Murni, BKU, UK, Total Usaha `BKU+UK`, Keluarga Ditemukan), Patokan Wilkerstat 2025 (KK & Usaha), serta kolom Indikator Komparasi & Warning Anomali.
    - Memperbaiki bug penggabungan string (`00`) pada kolom Total Usaha JavaScript di modal.
  - **Pembaruan Multi-Sheet Excel Export (`.xlsx`)**:
    - Menambahkan kolom pembanding Wilkerstat 2025 (KK & Usaha), Total Usaha SE (`BKU+UK`), persentase selisih KK, dan status perbandingan KK pada sheet PPL, PML, dan SLS.
  - **Perbaikan Metabase Embed JWT Validation (`DomainException: Provided key is too short`)**:
    - Menambahkan validasi panjang dan keberadaan `services.metabase.secret_key` pada `WilkerstatMetabase` dan `MetabaseEmbed`. Algoritma `HS256` pada `firebase/php-jwt` mensyaratkan secret key minimal 256-bit (32 karakter).
    - Mencegah error fatal 500 jika `METABASE_SECRET_KEY` pada `.env` belum diisi atau kurang dari 32 karakter dengan memberikan graceful fallback serta logging peringatan.
  - **Integrasi SIPETRA SSO (Single Sign-On OAuth2)**:
    - Memasang dependensi `laravel/socialite` dan membuat custom provider `SipetraSocialiteProvider` untuk protokol OAuth2 SIPETRA BPS Demak (`oauth/authorize`, `oauth/token`, `api/user`).
    - Menambahkan konfigurasi environment `.env` dan `config/services.php` dengan scopes `['identity_pegawai:read', 'employee:read', 'contact:read', 'roles:read']`.
    - Menambahkan migrasi database `2026_08_17_000001_add_sipetra_columns_to_users_table` untuk kolom `sipetra_id`, `sipetra_token`, `sipetra_refresh_token`, `nip`, `jabatan`, `avatar` dan relaksasi kolom `password` (nullable).
    - Menambahkan `SsoController` untuk alur autentikasi login terpusat (`/auth/sipetra/redirect` dan `/auth/sipetra/callback`).
    - Mengintegrasikan tombol login SSO SIPETRA dengan branding logo resmi BPS Demak pada form login Filament Admin Panel (`PanelsRenderHook::AUTH_LOGIN_FORM_AFTER`) dan form login Tablar (`/login`).

### 2026-08-18
- **Perbaikan Export Excel Multi-Sheet (`PengolahanExportService`)**:
  - Mengganti fungsi `range($startCol, $endCol)` pada method `applyBordersAndAutoWidth()` dengan `Coordinate::columnIndexFromString()` dan `Coordinate::stringFromColumnIndex()`.
  - Mengatasi error `Argument #2 ($end) must be a single byte, subsequent bytes are ignored` pada PHP 8 saat melakukan autosize kolom Excel lebih dari 26 kolom (seperti kolom `AA`, `AB` pada Sheet Alokasi SLS).
