# Implementation Plan: Standalone Executable (.EXE) Dashboard Anomali Geotag SE2026

## Overview
Memisahkan fitur Dashboard Deteksi Anomali Geotag & Klaster Titik SE2026 dari monolith Laravel `alfath` menjadi aplikasi desktop mandiri (`.exe`) yang portabel. Aplikasi ini ditujukan untuk dibagikan ke rekan-rekan BPS di kabupaten lain tanpa perlu melakukan instalasi web server (Apache/Nginx), PHP, Composer, ataupun database MySQL.

## Architecture Decisions
1. **Engine Pemrosesan**: 
   - Python Standalone Service (FastAPI/Bottle + PyInstaller) atau Client-Side Standalone Web App (WebView2 launcher).
   - Logika klasifikasi fraud (Fraud Rumah Tangga BTT vs Wajar Pasar BKU) dan kalkulasi spatial bounding box diisolasi dari database `fasih`.
2. **Fleksibilitas Input Data (CSV & GeoJSON)**:
   - **Metode 1 (GUI Drag & Drop)**: Dropzone langsung di antarmuka aplikasi untuk upload CSV SQL Lab dan GeoJSON SLS kabupaten setempat.
   - **Metode 2 (Folder Berdampingan `data/`)**: Auto-load file CSV dan GeoJSON jika diletakkan di folder `data/` tepat di sebelah file `.exe`.
3. **Penyimpanan Sesi Lokal**:
   - Data yang di-upload disimpan ke cache lokal (Local Storage / SQLite lokal) agar pengguna tidak perlu upload ulang setiap kali membuka aplikasi.
4. **UI & Interaktivitas Peta**:
   - Menggunakan Leaflet.js dengan basemap Satelit & OSM, MarkerCluster, custom badges tingkat keparahan, poligon SLS terdampak anomali, tabel DataTables interaktif, dan link Google Maps Satelit.

## Task List

### Phase 1: Standalone Core Engine & Data Processor
- [ ] **Task 1**: Buat engine parser CSV dan Spatial Filter mandiri
  - Mengadopsi logika dari `Se2026ClusterAnomalyService.php`
  - Parse CSV query SQL Lab: auto-detect header (`pencacah_email`, `cluster_size`, `center_lat`, `center_lon`, `point_lat`, `point_lon`, `kode_bang_label`, dll.)
  - Filter spatial poligon GeoJSON SLS kabupaten agar hanya memuat SLS dengan anomali fraud
  - Agregasi statistik metrik keparahan, ranking petugas, dan sebaran kecamatan

### Phase 2: User Interface & Drag-and-Drop Loader
- [ ] **Task 2**: Buat antarmuka mandiri dengan sistem input file terpadu
  - Tampilan awal dengan Drag-and-Drop box untuk CSV SQL Lab & GeoJSON SLS
  - Loading bar / indikator progres saat memproses ribuan titik
  - Dashboard interaktif lengkap dengan peta Leaflet (Satelit + Poligon SLS + Titik Klaster)
  - KPI metric cards & DataTables interaktif (Ranking Petugas & Detail Klaster Anomali)
  - Fitur ekspor hasil filter ke CSV

### Phase 3: Packaging & Executable (.EXE) Build
- [ ] **Task 3**: Build & bundle aplikasi menjadi `.exe` portabel
  - Kompilasi menjadi executable Windows `.exe` siap pakai (zero-config)
  - Uji coba portabilitas di folder terpisah tanpa dependensi PHP/Laravel
  - Siapkan template folder distribusi (`AnomaliGeotagSE2026.exe`, folder `data/`, dan `PANDUAN.txt`)

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Format kolom CSV di kabupaten lain sedikit berbeda | High | Implementasikan auto-detect dan fallback column mapping cerdas |
| Ukuran file GeoJSON SLS kabupaten besar (>30MB) | Medium | Lakukan spatial indexing (bounding box filter) sebelum parsing detail poligon |
| Komputer pengguna di kabupaten lain lambat | Low | Proses komputasi berat dilakukan secara asinkron dengan loading indicator |
