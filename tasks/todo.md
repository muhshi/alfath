# Todo List

## Dashboard Deteksi Anomali Geotag Petugas SE2026
- [ ] **Task 1**: Buat Service `Se2026ClusterAnomalyService.php` untuk parsing 13 CSV, agregasi 736 klaster & 253 petugas, join master petugas & wilayah, klasifikasi severity, dan file caching
- [ ] **Task 2**: Buat Controller `GeotagAnomalyController.php` (< 100 baris) dengan endpoint dashboard, export laporan, dan detail titik klaster
- [ ] **Task 3**: Daftarkan rute `/dashboard-anomali-geotag` di `routes/web.php` dan tambahkan menu navigasi di `config/tablar.php`
- [ ] **Task 4**: Buat Blade View `dashboard-anomali-geotag.blade.php` lengkap dengan KPI cards, filter interaktif, peta Leaflet (center klaster, popup petugas & radius), tab ranking petugas, tab daftar klaster, dan rekap per kecamatan
- [ ] **Task 5**: Pengujian rute, akurasi data geospasial, filter severity/kecamatan, dan benchmark kecepatan loading
- [ ] **Task 6**: Update `README.md` Changelog, Git Commit & Push sesuai rule proyek

## Dashboard Tabel Pengolahan SE2026
- [x] **Task 1**: Buat Controller `PengolahanController.php` dengan query gabungan (Monitoring SE2026 + Usaha Perusahaan + Usaha Keluarga + Pemutakhiran Keluarga) yang mendukung Search, Filter, & Sorting
- [x] **Task 2**: Tambahkan rute `Route::get('/dashboard-pengolahan', ...)` di `routes/web.php`
- [x] **Task 3**: Buat Blade View `dashboard-pengolahan.blade.php` lengkap dengan kartu KPI, form search & filter, header column sorting (▲/▼), tabel responsive, dan pagination
- [x] **Task 4**: Pengujian rute `/dashboard-pengolahan`, fitur search/filter/sort, dan verifikasi akurasi data agregasi
- [x] **Task 5**: Update `README.md` Changelog, Git Commit & Push sesuai workflow project.

## Restrukturisasi FASIH & Perbaikan Upload Excel
- [x] Fix Filament v5 type declarations across resources
- [x] Update `ProcessUsahaExcelCommand.php` to use `import:usaha`
- [x] Clean up `FasihScraper.php` UI (remove log viewer & scraper actions, update menu name to "Import Excel Usaha")
- [x] Fix Excel file upload path resolution and Artisan command execution in `FasihScraper.php`
- [x] Verify Excel upload feature and test suite (`php artisan test`)
