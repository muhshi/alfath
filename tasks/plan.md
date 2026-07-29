# Implementation Plan: Public Executive Dashboard Sensus Ekonomi 2026

## Overview
Membuat Public Executive Dashboard khusus untuk Sensus Ekonomi 2026 Kabupaten Demak pada aplikasi ALFATH. Dashboard diakses tanpa login melalui halaman publik (`/se2026`), didesain dengan tampilan visual modern (*Executive Presentation Theme*), serta menyajikan 8 aspek data makro utama tanpa menampilkan rincian operasional petugas/SLS individual.

## Architecture Decisions
- **Public Unauthenticated Access**: Menambahkan rute publik `/se2026` di `routes/web.php` agar dapat diakses langsung oleh tamu eksternal tanpa melintasi middleware `auth`.
- **Standalone Presentation View**: Blade view standalone (`resources/views/se2026.blade.php`) menggunakan styling Tabler/Tailwind modern + Chart.js untuk menjamin performa cepat, visualisasi elegan, dan layout full-screen.
- **Aggregated Query Logic**: Logic query ditempatkan di `ExecutiveDashboardController.php` dengan fallback penanganan data agar aman jika tabel 7 & 8 disunting hari ini.

## Task List

### Phase 1: Controller & Query Aggregation
- [ ] Task 1: Buat `ExecutiveDashboardController.php` dan logic kueri agregasi dari 8 tabel database.

### Phase 2: Routing & View Layout
- [ ] Task 2: Tambahkan route publik `/se2026` di `routes/web.php`.
- [ ] Task 3: Buat view `resources/views/se2026.blade.php` dengan layout Executive Presentation Mode (Header, KPI Cards, Modern Chart.js, Ranking Kecamatan).

### Phase 3: Verification & Polish
- [ ] Task 4: Uji sintaksis dan verifikasi tampilan dashboard publik di browser.

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Tabel 7 & 8 sedang diupdate oleh user hari ini | Medium | Menggunakan query defensive (`Schema::hasTable` & fallback `0` jika tabel belum siap/kosong) |
| Koneksi database MySQL remote lambat | Low | Gunakan agregasi query sederhana (`DB::table(...)` dengan caching singkat jika diperlukan) |
