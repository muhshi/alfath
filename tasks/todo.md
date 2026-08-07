# Todo List

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
