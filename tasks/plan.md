# Implementation Plan: Dashboard Deteksi Anomali Geotag Petugas SE2026

## Overview

Fitur dashboard analitik dan visualisasi geospasial interaktif untuk mendeteksi anomali petugas (pencacah/PPL) pada Sensus Ekonomi 2026 (SE2026) BPS Kabupaten Demak. Dashboard ini mengolah data klastering koordinat lat-long (117.000 titik geotag dalam 736 klaster dan 253 petugas) di mana titik geotag bertumpuk secara tidak wajar di satu lokasi sempit (< 15 meter) yang mengindikasikan petugas tidak melakukan geotag di lokasi usaha responden secara door-to-door melainkan dari satu titik statis (misal di rumah/warkop).

## Architecture Decisions

1. **Service Layer Pattern**: Seluruh parsing CSV, agregasi klaster, kalkulasi metrik keparahan, join dengan master petugas BPS (`master_petugas`, `monitoring_se2026`, `alokasi_pengawas`), dan caching diletakkan di `App\Services\Se2026ClusterAnomalyService`.
2. **Controller Ringkas**: `GeotagAnomalyController` dijaga sangat ringkas (< 100 baris) sesuai pedoman arsitektur Laravel dan rule proyek.
3. **High-Performance Caching**: Mengingat ukuran data 13 CSV mencapai 44 MB (117.000 baris), data agregasi klaster (736 klaster & 253 petugas) diparsing dan dicache (file cache / array cache) sehingga load time halaman tetap sangat cepat (< 100ms).
4. **Interactive Geospatial Map (Leaflet.js)**: Visualisasi peta interaktif dengan CircleMarker / Cluster markers bergradasi warna sesuai tingkat keparahan anomali, popup lengkap dengan info petugas, tombol zoom langsung ke klaster, dan tombol langsung ke Google Maps.
5. **Theme & Layout**: Terintegrasi ke layout aplikasi Tablar (`tablar::page`), lengkap dengan tabulasi:
   - Tab 1: Peta Geospasial & Panel Investigasi Klaster
   - Tab 2: Ranking Petugas Terindikasi Anomali (Tabel interaktif dengan sorting & filter)
   - Tab 3: Rekap Klaster per Kecamatan (14 Kecamatan Demak)
6. **Ekspor Laporan**: Fitur download laporan anomali (Excel/CSV) untuk diteruskan ke Pengawas Lapangan (PML) / Koseka.

---

## Task List

### Phase 1: Service & Data Processing Foundation

- [ ] **Task 1**: Buat `Se2026ClusterAnomalyService.php` di `app/Services/`
  - Parse dan gabungkan seluruh 13 file CSV di `public/SE2026/`
  - Ekstrak 736 unique klaster dan agregasi per petugas (253 enumerators)
  - Join dengan `master_petugas`, `monitoring_se2026`, dan nama kecamatan
  - Klasifikasikan tingkat anomali: Kritis (>100 titik), Tinggi (51-100 titik), Sedang (21-50 titik), Ringan (10-20 titik)
  - Implementasikan caching otomatis untuk performa maksimal
- [ ] **Task 2**: Buat `GeotagAnomalyController.php` di `app/Http/Controllers/`
  - Action `index(Request $request)`: menerima filter kecamatan, severity, search, dan tab
  - Action `export(Request $request)`: download file laporan CSV/Excel untuk tindak lanjut
  - Endpoint JSON `points(Request $request, $clusterId)` untuk lazy loading titik sampel jika dibutuhkan

### Checkpoint: Foundation

- [ ] Service unit/integration test via PHP script: verifikasi 736 klaster dan nama petugas terbaca dengan benar
- [ ] Eksekusi controller dan rute bebas error

### Phase 2: User Interface & Geospatial Visualization

- [ ] **Task 3**: Daftarkan rute di `routes/web.php` dan tambahkan menu navigasi di `config/tablar.php`
  - Route: `/dashboard-anomali-geotag` (`name('dashboard.anomali-geotag')`)
  - Submenu/Menu Navigasi di bawah Monitoring SE2026
- [ ] **Task 4**: Buat Blade View `resources/views/dashboard-anomali-geotag.blade.php`
  - KPI Stat Cards: Total Geotag Anomali, Total Klaster, Petugas Terindikasi, Klaster Terbesar, Rasio Anomali
  - Filter Bar: Kecamatan, Tingkat Risiko (Severity), Pencarian Nama/Email
  - Peta Interaktif Leaflet dengan:
    - Layer basemap OpenStreetMap / Carto Positron
    - Marker klaster berwarna sesuai risiko (Merah: >100 titik, Oranye: 51-100 titik, Kuning: 21-50 titik, Biru: 10-20 titik)
    - Custom Popup info: Nama Petugas, PML, Kecamatan, Titik bertumpuk, Akurasi GPS, Tombol Lihat di Google Maps
  - Tab 1: Peta & Detail Klaster Terpilih
  - Tab 2: Tabel Ranking Petugas Paling Banyak Titik Bertumpuk (dengan badge risiko & tombol fokus ke peta)
  - Tab 3: Rekapitulasi Persebaran Klaster per 14 Kecamatan di Kab. Demak
  - Tombol Ekspor Laporan Anomali

### Checkpoint: UI & Interactivity

- [ ] Peta Leaflet tampil mulus, responsif, dan marker dapat diklik
- [ ] Filter Kecamatan dan Risiko berfungsi memfilter peta dan tabel secara sinkron
- [ ] Klik baris petugas di tabel langsung menggerakkan peta (flyTo) ke klaster terkait

### Phase 3: Verification & Workflow Completion

- [ ] **Task 5**: Verifikasi performa halaman (< 500ms), keakuratan data titik klaster, dan testing browser/unit
- [ ] **Task 6**: Update `README.md` Changelog, Git Commit & Push sesuai rule proyek

---

## Risks and Mitigations

| Risk                                                                                         | Impact | Mitigation                                                                                                                                                                                        |
| -------------------------------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Parsing 13 file CSV (44 MB, 117.000 baris) memakan memori/CPU jika dijalankan setiap request | High   | Gunakan caching file/cache Laravel dengan key versioning. File CSV hanya diproses sekali lalu disimpan dalam cache terindeks                                                                      |
| Titik 117.000 terlalu berat jika dirender langsung semua di canvas Leaflet                   | High   | Tampilkan 736 pusat klaster (Cluster Centers) di peta dengan ukuran lingkaran proporsional terhadap`cluster_size`. Titik individual hanya ditampilkan saat klaster tertentu di-zoom atau diklik |
| Email pencacah di CSV ada yang belum terdaftar di`master_petugas`                          | Low    | Berikan fallback nama otomatis dari email (misal strip`@gmail.com` dan kapitalisasi) dan tandai statusnya                                                                                       |

## Keputusan Tambahan Berdasarkan Masukan Pengguna
- **Filter Tanggal / Batch Tarikan**: Diterapkan secara fleksibel. Dashboard mendukung filter file tarikan/batch sehingga jika di kemudian hari ada tarikan data klaster baru, sistem otomatis mengenali opsi tanggalnya.
- **Status Tindak Lanjut vs Karakteristik Lapangan (Pasar / Ruko)**:
  - Mengingat sisa waktu pendataan yang singkat serta adanya **Pasar Tradisional / Kawasan Pertokoan / Sentra Usaha** di mana titik koordinat usaha memang wajar berkumpul di satu lokasi rapat:
  - Kita **tidak perlu membuat tabel database baru yang rumit untuk status teguran**.
  - **Solusi Cerdas & Praktis**: Kami sediakan tombol **"Buka di Google Maps / Satelit"** di setiap klaster popup dan tabel, sehingga pengawas lapangan (PML/Koseka) dalam hitungan detik dapat langsung memvalidasi apakah lokasi klaster tersebut merupakan **Pasar Tradisional / Ruko Komersial** (Legal/Wajar) atau justru **Rumah Tinggal / Sawah / Kos-Kosan** (Indikasi Nakal).
  - Menambahkan catatan panduan di dashboard untuk mengedukasi pengawas agar memeriksa apakah klaster tersebut berada di area pasar sebelum memberikan teguran kepada petugas.
