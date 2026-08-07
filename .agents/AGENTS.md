# Project Rules

## Browser Verification
- Jangan melakukan pengecekan/verifikasi di browser (browser_subagent). Serahkan pengecekan visual/browser ke user.
- Fokus ke perubahan kode, query, dan logic saja.

## Laravel Architecture & Fat Controller Warning Rule
- **Threshold Check**: Ketika mengerjakan proyek Laravel, jika menemukan file Controller yang melebihi **300 baris** atau memiliki banyak tanggung jawab (query database berat, kalkulasi bisnis, ekspor file), SELALU berikan pemberitahuan/warning kepada developer:
  > ⚠️ **Warning Arsitektur Laravel**: Controller `[NamaController]` mencapai **[X] baris**. Disarankan untuk meremajakan kodingan menggunakan **Service Layer Pattern** (`app/Services/`) agar Controller tetap ringkas (~50-100 baris). Apakah ingin diterapkan sekarang?
- **Service Layer Refactoring**: Pisahkan kueri ke `DataService`, kalkulasi ke `BusinessService`, dan pembentukan file ke `ExportService`. Controller hanya bertindak sebagai coordinator HTTP.
