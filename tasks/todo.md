# Task List: Standalone (.EXE) Dashboard Anomali Geotag SE2026

## Phase 1: Standalone Core Engine & Data Processor
- [x] Task 1.1: Buat engine data processor mandiri untuk parsing CSV SQL Lab tanpa dependensi database Laravel (`tools/anomali-geotag-standalone/engine.py`)
- [x] Task 1.2: Implementasikan spatial bounding-box & raycasting filter untuk GeoJSON SLS (`tools/anomali-geotag-standalone/engine.py`)
- [x] Task 1.3: Hitung metrik agregasi, severity scoring, klasifikasi fraud (BTT vs BKU), dan ranking petugas

## Phase 2: User Interface & Drag-and-Drop Loader
- [x] Task 2.1: Buat antarmuka mandiri Flask & HTML dengan dropzone input CSV & GeoJSON (`app.py`, `templates/index.html`)
- [x] Task 2.2: Hubungkan peta Leaflet interaktif (Layer Satelit Esri/Carto/OSM, Poligon SLS, Cluster Markers, Radius Circle, Google Maps deep-link)
- [x] Task 2.3: Integrasikan KPI stat cards, filter interaktif (Kecamatan, Severity, Fraud Category, Search), dan DataTables
- [x] Task 2.4: Tambahkan fitur ekspor CSV (Klaster & Petugas) dan auto-load folder `data/`

## Phase 3: Packaging & Executable (.EXE) Build
- [x] Task 3.1: Konfigurasi packaging executable (.exe) portabel Windows via PyInstaller
- [x] Task 3.2: Uji coba auto-load data dari folder `data/` vs upload melalui GUI (Terverifikasi lewat unit test)
- [x] Task 3.3: Susun paket distribusi siap kirim (`dist/Aplikasi_Anomali_Geotag_SE2026/`) lengkap dengan EXE, folder data, dan panduan penggunaan
