# Implementation Plan: SE2026 Pemutakhiran Keluarga (`se2026_pemutakhiran_keluarga`)

## Overview
Menambahkan dukungan import Excel untuk file `Export_Progres_Pemutakhiran_Keluarga_Sub_Satuan_Lingkungan_Setempat_Sub-SLS.xlsx`, menyaring 16 digit `kode`, membuat migrasi tabel `se2026_pemutakhiran_keluarga`, Eloquent Model, Python importer, dan Artisan command.

## Architecture Decisions
- Penamaan tabel database: `se2026_pemutakhiran_keluarga` pada koneksi database `fasih`.
- Filter hanya memproses `kode` Sub-SLS 16 digit.
- Script Python performa tinggi (pandas + pymysql bulk upsert `ON DUPLICATE KEY UPDATE`) digunakan untuk mempercepat pemrosesan data Excel.

## Task List

### Phase 1: Database & Migrations
- [ ] Task 1: Buat migrasi Laravel untuk tabel `se2026_pemutakhiran_keluarga`

### Phase 2: Models & Import Engine
- [ ] Task 2: Buat model Eloquent `Se2026PemutakhiranKeluarga`
- [ ] Task 3: Buat Python script `process_pemutakhiran_keluarga_excel.py` untuk parse Excel dan bulk upsert 16 digit kode SLS
- [ ] Task 4: Buat Artisan command `ImportExcelPemutakhiranKeluarga.php` / `ProcessPemutakhiranKeluargaExcelCommand.php`

### Phase 3: Verification & Documentation
- [ ] Task 5: Uji coba import Excel `Export_Progres_Pemutakhiran_Keluarga_Sub_Satuan_Lingkungan_Setempat_Sub-SLS.xlsx` dan verifikasi data di DB
- [ ] Task 6: Update `README.md` Changelog, Git Commit & Push sesuai workflow project.
