
<p align="center">
  <img src="public/assets/logo_bps.png" width="120" alt="Logo BPS Demak" onerror="this.style.display='none'">
</p>

<h1 align="center">ALFATH — Aplikasi Fasih Monitoring Harian</h1>
<p align="center">
  <strong>Sistem Terpadu Monitoring Harian, Evaluasi Progres, & Perangkingan Kinerja Petugas Sensus Ekonomi 2026 (SE2026)</strong><br>
  <em>Badan Pusat Statistik (BPS) Kabupaten Demak</em>
</p>

---

## 📌 Tentang ALFATH

**ALFATH** (*Aplikasi Fasih Monitoring Harian*) adalah platform dashboard analitis dan sistem pendukung keputusan internal BPS Kabupaten Demak untuk mengawal kelancaran pelaksanaan **Sensus Ekonomi 2026 (SE2026)**. Sistem ini mengintegrasikan data operasional dari database FASIH, Wilkerstat 2025, dan survei statistik lainnya ke dalam visualisasi real-time yang informatif dan terstandarisasi.

### 🚀 Fitur Utama

1. **Dashboard Eksekutif Publik (`/dashboard-se2026`)**:
   - Visualisasi KPI makro: Total Beban Pendataan, Progres Submit Harian, Sebaran Progres per Kecamatan, dan Distribusi Temuan Usaha (BKU & UK).
2. **Dashboard Tabel Petugas & Agregasi Multi-Tab (`/dashboard-pengolahan`)**:
   - **Tab 1: Ranking Kinerja Petugas (PPL)**: Formula komposit 5 Pilar (Skor 0-100), Target Milestone 100% s.d. 25 Agustus, laju harian dinamis, dan sinyal peringatan stagnan.
   - **Tab 2: Ringkasan & Ranking PML (Pengawas)**: Formula 5 Pilar PML (Responsivitas Verifikasi, Progres Tim, Kualitas Usaha, Tim Health *No PPL Left Behind*, dan Resolusi Anomali) lengkap dengan transparansi metodologi.
   - **Tab 3: Alokasi & Progres Per SLS / Sub-SLS**: Deteksi anomali SLS berbasis pembanding Wilkerstat 2025 (KK & Usaha) dengan toleransi 5%.
   - **Tab 4: Data Petugas (PPL)**: Rincian beban dan hasil pendataan per pencacah.
3. **Sistem Deteksi & Approval Catatan Anomali SLS**:
   - Fitur klarifikasi lapangan oleh petugas dan panel approval admin Filament untuk verifikasi wilayah non-usaha / anomali.
4. **Engine Importer Python Fast-Bulk Processing**:
   - Ekstraksi dan upsert data Excel FASIH berukuran besar (>30.000 baris) dalam hitungan detik.
5. **Ekspor Excel Multi-Sheet Native (`.xlsx`)**:
   - Format profesional otomatis dengan banner header, formula Excel dinamis, formatting ribuan `#,##0`, dan persentase.
6. **Autentikasi SSO Terpusat (SIPETRA OAuth2)**:
   - Integrasi Single Sign-On resmi BPS Kabupaten Demak.

---

## 🛠️ Tech Stack & Arsitektur

- **Framework**: Laravel 12 (PHP 8.2+)
- **Admin Panel & Resources**: Filament v3 / v5 & Livewire 3
- **Frontend / UI**: Tablar Theme (Bootstrap 5), Chart.js, DataTables, FontAwesome
- **Data Layer & Importer**: MySQL / MariaDB (Multi-connection FASIH DB), Python (Pandas, PyMySQL, OpenPyXL)
- **Caching**: Laravel Cache with Atomic Versioning & Auto-Invalidation

---

## 🚀 Deployment

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

---

## 📜 Changelog

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
- **Penyesuaian Namespace Action Filament v5 (`SurveyResource`, `TeamResource`, `CategoryResource`)**:
  - Memperbarui import action tabel dari `Filament\Tables\Actions` menjadi `Filament\Actions` (`EditAction`, `BulkActionGroup`, `DeleteBulkAction`) sesuai standar Filament v5.

### 2026-08-19

- **Pembaruan Formula Perangkingan Kinerja Petugas SE2026 (`PetugasPerformanceRankingService.php`)**:
  - **Skema 3 Pilar Proporsional (100 Poin)**:
    - **Progress Score (Bobot 35 Poin)**: Menilai kedisiplinan dan pencapaian submit relatif terhadap *Dynamic Target* harian.
    - **Usaha Score (Bobot 35 Poin)**: Menghitung akumulasi temuan usaha (`BKU + UK`) untuk mengapresiasi petugas yang teliti mendata usaha keluarga maupun badan usaha formal di lapangan.
    - **Muatan Murni Score (Bobot 30 Poin)**: Menilai volume cakupan fisik wilayah (`KK + BKU`).
  - **Penyempurnaan Klasifikasi Kategori Kinerja**: Menyesuaikan batasan *Sangat Rajin* (Skor $\ge 80$), *Rajin* (Skor $\ge 65$), *Cukup / Standar*, *Malas*, dan *Sangat Malas* secara berkeadilan berbasis skor komposit 3 pilar.
  - **UI/UX Dashboard Breakdown Indikator**: Menampilkan breakdown sub-skor `P` (Progres), `U` (Usaha), dan `M` (Muatan Murni) di cell Skor Kinerja beserta pembaruan penjelasan metodologi pada kartu panduan Dashboard Pengolahan.

### 2026-08-20

- **Koreksi Pemetaan Kolom Parser Excel Usaha (`process_usaha_excel.py` & `ImportExcelUsaha.php`) & Re-import Data**:
  - **Identifikasi Pergeseran Kolom Sheet USAHA PERUSAHAAN**: Mengoreksi pemetaan kolom parser Excel FASIH di mana kolom kategori *UMKM (Non-UB)* berada pada indeks kolom 21 (Ditemukan), 23 (Tutup), 25 (Ganda), 27 (Tidak Ditemukan), 29 (Baru), dan 39 (Total Usaha BKU). Sebelumnya terjadi pergeseran sehingga data *Tidak Ditemukan* (seperti 186 pada SLS `3321070012004400`) tergeser ke status *Baru/Ditemukan*.
  - **Penggabungan Status UB + UMKM**: Memastikan perhitungan BKU menggabungkan status dari entitas *UB* (Usaha Besar) dan *UMKM* secara presisi.
  - **Sinkronisasi & Re-import 100% Data Snapshot 19 Agustus**: Menjalankan re-import seluruh 16.706 baris data Usaha Perusahaan dan 16.704 baris data Usaha Keluarga sehingga data di database `se2026_usaha_perusahaan` dan `se2026_usaha_keluarga` 100% identik dengan file Excel sumber FASIH.
- **Audit & Penyelarasan Parser Pemutakhiran Keluarga (`process_pemutakhiran_keluarga_excel.py` & `ImportExcelPemutakhiranKeluarga.php`)**:
  - **Penyelarasan Kolom Sheet KELUARGA**: Mengoreksi urutan kolom status ketidakberadaan keluarga (*Tidak Ditemukan* pada indeks 10 dan *Nonrespon* pada indeks 12).
  - **Verifikasi Agregasi Data 100% Cocok**: Memastikan data `se2026_pemutakhiran_keluarga` (Ditemukan: 264.540, Keluarga Baru: 26.737, Total: 291.277, Tidak Ditemukan: 41.644, Nonrespon: 427, Meninggal: 4.714, Tidak Eligible: 239) 100% tepat dan identik dengan file Excel sumber.
- **Sinkronisasi & Konsistensi Perhitungan Usaha Keluarga (Ditemukan + Baru)**:
  - **Verifikasi Import Excel FASIH**: Memastikan data sheet `USAHA KELUARGA` tersimpan lengkap ke tabel `se2026_usaha_keluarga` dengan rincian status *Ditemukan* (61.920), *Baru* (70.759), dan total *Usaha dalam Keluarga* (132.679).
  - **Penyelarasan Kueri Dashboard (`Se2026MonitoringService` & `PetugasPerformanceRankingService`)**: Memperbarui kueri subquery Usaha Keluarga pada Tab Alokasi SLS (`getSlsQuery`) dan kalkulasi anomali usaha SLS pada Ranking Service agar konsisten menjumlahkan `Ditemukan + Baru` (`uk_ditemukan = jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka + jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru`), selaras dengan Tab PPL dan Tab PML.
- **Pembaruan Target Laju 25 Agustus (100%), Formula Perangkingan Kualitas Probing Usaha (5 Pilar), & Perbaikan Kontras UI**:
  - **Pembaruan Target Laju Penyelesaian ke 25 Agustus 2026 (Target 100%)**:
    - Mengubah target milestone dari 95% s.d. 20 Agustus menjadi penyelesaian **100% s.d. 25 Agustus 2026**.
    - Menghitung sisa hari dinamis ke 25 Agustus dan kebutuhan submit harian `(Beban - Submit) / Sisa Hari`.
    - Menyesuaikan header kolom tabel dan file ekspor Excel menjadi **`Laju s.d. 25 Agt (Target 100%)`** serta menampilkan badge `✅ Selesai (100%)` untuk petugas yang telah tuntas.
  - **Peningkatan Formula Perangkingan Kinerja Petugas (5 Pilar Komposit - Skala 0 s.d. 100 Poin)**:
    - **Pilar 1 - Progres Submit (Bobot 30 Poin)**: Menilai ketepatan laju submit terhadap target harian dinamis.
    - **Pilar 2 - Kualitas Probing Usaha Total vs Wilkerstat Petugas (Bobot 25 Poin)**: Mengukur rasio Total Usaha SE (`BKU + UK`) terhadap target Usaha Wilkerstat 2025 agregat petugas (mendapat 25 poin penuh jika $\ge 100\%$).
    - **Pilar 3 - Ketelitian SLS Probing / SLS Optimal Rate (Bobot 20 Poin)**: Menghitung persentase SLS yang dipegang petugas yang jumlah usahanya telah mencapai/melebihi target Wilkerstat SLS (`Usaha SE SLS ≥ Wilkerstat SLS`), guna memastikan probing merata di seluruh SLS dan tidak menumpuk di satu SLS saja.
    - **Pilar 4 - Intensitas Spotting Usaha Keluarga / Rasio UK per KK (Bobot 10 Poin)**: Mengukur kejelian petugas menggali usaha keluarga saat mewawancarai KK (`UK Ditemukan / KK Ditemukan`), dengan skor maksimal 10 poin jika rasio $\ge 15\%$.
    - **Pilar 5 - Volume Muatan Murni (Bobot 15 Poin)**: Mengapresiasi beban wilayah kerja fisik yang berat (`KK + BKU`).
    - **Breakdown Tooltip & Sub-indikator**: Menampilkan rincian sub-skor pada cell Skor Kinerja (`P:.. | U:.. | SLS:.. | UK:.. | M:..`) dengan tooltip komprehensif.
  - **Peningkatan Halaman Approval Admin Anomali SLS (`Se2026AnomaliCatatanResource`)**:
    - **Filter by Kecamatan**: Menambahkan filter dropdown 14 kecamatan di Kabupaten Demak untuk memudahkan penelaahan catatan klarifikasi per wilayah kecamatan.
    - **Kolom Badge Kecamatan**: Menampilkan kolom nama kecamatan ber-badge biru pada tabel daftar approval.
    - **Penyajian Nama SLS & Badge Kode 16 Digit**: Menggabungkan nama SLS (bold) dengan badge kode SLS 16 digit (font mono) dalam satu cell, di-query via subquery `sipw` dan `se2026_pemutakhiran_keluarga` tanpa menimbulkan masalah N+1 query.
    - **Pelebaran Kolom Catatan Klarifikasi**: Memperlebar kolom Catatan Klarifikasi (`min-width: 340px`, `grow(true)`) agar catatan panjang dari petugas dapat dibaca dengan lega dan nyaman.
  - **Implementasi Caching Cerdas Dashboard Pengolahan (`Laravel Cache Remember + Atomic Versioning`)**:
    - Mengintegrasikan caching terversioning (`se2026_dash_v{version}_{hash}`) dengan TTL 2 jam untuk mereduksi beban database dan memangkas waktu loading dashboard menjadi instan (< 10 ms).
    - **Auto-Invalidation Terintegrasi**: Cache otomatis di-flush secara instan setiap kali ada proses import Excel baru (`ImportExcelUsaha`, `ImportExcelPemutakhiranKeluarga`), pengajuan catatan anomali, approval/rejection catatan SLS di admin Filament, maupun penghapusan data.
    - **Tombol Refresh Manual UI**: Menyediakan tombol `🔄 Refresh & Hitung Ulang Cache Real-time` (parameter `?fresh=1`) pada form filter dashboard.

### 2026-08-21

- **Implementasi Formula Perhitungan & Ranking Kinerja PML Terbaik (5 Pilar Kinerja) & Transparansi Metodologi di Dashboard Pengolahan**:
  - **Formula 5 Pilar Kinerja PML (Skala 0 s.d. 100 Poin) (`PetugasPerformanceRankingService.php`)**:
    - **Pilar 1 - Responsivitas & Verifikasi PML (Bobot 25 Poin)**: Menilai kecepatan dan kedisiplinan PML memeriksa submit dokumen PPL binaannya (`% Pengerjaan PML = (Approved + Rejected) / Total Submit PPL * 100%`). Mendapat 25 poin penuh jika `% Pengerjaan PML` $\ge 90\%$.
    - **Pilar 2 - Capaian Progres Tim Binaan vs Target Dinamis (Bobot 25 Poin)**: Menilai ketepatan laju submit tim binaan PML terhadap *Dynamic Target Harian* menuju 25 Agustus (on-track mendapat 20-25 poin).
    - **Pilar 3 - Kualitas Probing & Akurasi Usaha Binaan (Bobot 20 Poin)**: Menggabungkan rasio Total Usaha SE vs Wilkerstat Usaha (10 poin) dan *SLS Usaha Optimal Rate* binaan PML (10 poin) untuk menjaga data bebas dari undercounting usaha.
    - **Pilar 4 - Kesehatan & Pemerataan Tim / No PPL Left Behind (Bobot 15 Poin)**: Baseline 15 poin dengan penalti jika ada PPL binaan yang stagnant $\ge 2$ hari (-3 poin), berstatus Sangat Malas (-5 poin), atau Malas (-2 poin), guna mendorong pendampingan aktif ke anggota tim yang lambat.
    - **Pilar 5 - Manajemen & Resolusi Anomali Lapangan (Bobot 15 Poin)**: Mengukur ketuntasan tindak lanjut catatan anomali SLS binaan (10 poin) dan pengendalian persentase Bangunan Kosong/Lainnya $< 5\%$ (5 poin).
  - **Klasifikasi & Predikat PML**: Mengelompokkan PML ke dalam 4 predikat prestasi:
    - 🌟 **1. PML Teladan** (Skor $\ge 85.0$)
    - 🟢 **2. PML Aktif** (Skor $70.0 - 84.9$)
    - 🟡 **3. PML Cukup** (Skor $55.0 - 69.9$)
    - 🔴 **4. Perlu Evaluasi** (Skor $< 55.0$)
  - **Transparansi Metodologi & KPI PML di Tab PML Dashboard (`dashboard-pengolahan.blade.php`)**:
    - **KPI Summary Cards Tab PML**: Menampilkan 6 kartu ringkasan (Total PML, Teladan, Aktif, Cukup, Evaluasi, dan Rata-rata Skor).
    - **Accordion Transparansi Metodologi**: Menyediakan panel panduan interaktif yang menjabarkan secara gamblang definisi setiap metrik, rumus matematis, bobot poin, dan tabel predikat PML.
    - **Kolom Tabel PML Diperbarui**: Menyertakan kolom Peringkat (Rank #1 🏆), Skor Kinerja (0-100), Predikat Badge, sub-skor micro badges 5 pilar (`V:.. | P:.. | U:.. | T:.. | A:..`), serta Rekomendasi Tindakan PML. Default sorting otomatis mengurutkan berdasarkan peringkat PML.
  - **Pembaruan Ekspor Excel Agregasi & Ranking PML (`PengolahanExportService.php`)**:
    - Memperbarui Sheet PML (Sheet 3 pada export Semua Tab dan single sheet PML) agar memuat kolom Rank PML, Skor Kinerja, Predikat, Rekomendasi Tindakan, serta rincian nilai 5 pilar (Verifikasi, Progres Tim, Kualitas Usaha, Tim Health, Resolusi Anomali).

### 2026-09-03

- **Implementasi Fitur Dashboard Deteksi Anomali Geotag Petugas (Klaster Lat-Long SE2026)**:
  - **Service Layer Geospasial (`Se2026ClusterAnomalyService.php`)**:
    - Parsing dan penggabungan 13 file CSV query SQL Lab (`public/SE2026/*.csv`) dengan total **117.000 titik geotag** bertumpuk menjadi **734 titik klaster unik** pada **252 petugas pencacah (PPL)**.
    - Relasi otomatis ke database `fasih` (`master_petugas`, `monitoring_se2026`, `alokasi_pengawas`) untuk memunculkan nama asli pencacah, kecamatan tugas, dan nama PML penanggung jawab.
    - Klasifikasi tingkat keparahan: 🚨 Ekstrem (>100 titik: 2 klaster), ⚠️ Berat (51-100 titik: 22 klaster), 🟡 Sedang (21-50 titik: 161 klaster), dan 🔵 Ringan (10-20 titik: 549 klaster).
    - Mekanisme **Indexed File Caching** yang memangkas waktu load data dari 9,36 detik menjadi **8,46 milidetik**.
  - **Controller Ringkas & Ekspor Data (`GeotagAnomalyController.php`)**:
    - Mematuhi batas arsitektur Laravel (< 90 baris kode).
    - Mendukung filter dinamis (Kecamatan, Tingkat Keparahan, Pencarian) dan streaming ekspor CSV UTF-8 untuk klaster maupun ranking petugas.
  - **Visualisasi Geospasial Interaktif Leaflet.js (`dashboard-anomali-geotag.blade.php`)**:
    - Peta interaktif Kabupaten Demak dengan layer Carto Positron & OpenStreetMap, circle marker berskala radius proporsional dan warna sesuai tingkat keparahan.
    - Popup informatif lengkap dengan radius sebaran titik (meter), akurasi GPS, info PML, dan tombol **"Cek Satelit (Pasar/Rumah)"** ke Google Maps untuk membedakan antara klaster wajar di pasar tradisional vs anomali tidak door-to-door di rumah/warkop.
    - Panel daftar klaster di samping peta dengan interaksi klik yang otomatis memfokuskan peta (`flyTo`).
    - Tab Ranking Petugas Terindikasi (DataTables) dengan tombol langsung "Cek di Peta".
    - Tab Rekapitulasi Persebaran Klaster di 14 Kecamatan se-Kabupaten Demak.
  - **Integrasi Tombol Beranda (`home.blade.php`)**: Menambahkan tombol aksi cepat "Anomali Geotag SE2026" beraksen merah (`#dc2626`) pada bilah header halaman Beranda dan jajaran tombol aksi kartu hero banner utama.



