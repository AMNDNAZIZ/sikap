# WORKFLOW.md — Protokol Alur Kerja Pengembangan EAO
**Standar Operasional Alur Kerja dari Instruksi Pemilik Proyek hingga Rilis Fitur**

---

## 1. Ikhtisar Alur Kerja 6 Tahap
Setiap tugas pengembangan, penambahan fitur, atau perbaikan bug pada aplikasi ESPATA wajib melalui siklus 6 tahap terkontrol:

```
[Tahap 1: Instruksi Pemilik] 
           │
           ▼
[Tahap 2: Investigasi Faktual] (AI Product Analyst & AI System Architect)
           │
           ▼
[Tahap 3: Perancangan Solusi & Rencana Kerja] (AI Project Manager)
           │
     ┌─────┴────────────────────────┐
     │ Evaluasi Pemilik Proyek      │ <── GATE: Menunggu Persetujuan Manusia
     └─────┬────────────────────────┘
           │ (Disetujui)
           ▼
[Tahap 4: Implementasi Bertahap] (AI Developer)
           │
           ▼
[Tahap 5: Pengujian & Audit Integritas] (AI QA Engineer & AI Security Reviewer)
           │
           ▼
[Tahap 6: Laporan Hasil & Serah Terima] (AI Project Manager)
```

---

## 2. Rincian Pelaksanaan per Tahap

### Tahap 1: Penerimaan Instruksi & Mandat
- **Penanggung Jawab:** AI Project Manager.
- **Masukan (Input):** Arahan, permintaan fitur, atau laporan masalah dari Pemilik Proyek.
- **Aktivitas:**
  1. Mencatat tujuan fungsional yang diinginkan pengguna.
  2. Mengidentifikasi apakah instruksi terkait dengan proyek percontohan prioritas (**Asis AI ESPATA**) atau modul lainnya.
  3. Memastikan pemahaman instruksi tanpa membuat spekulasi awal.
- **Keluaran (Output):** Catatan ringkas mandat tugas.

### Tahap 2: Investigasi Faktual & Analisis Repositori
- **Penanggung Jawab:** AI Product Analyst & AI System Architect.
- **Aktivitas:**
  1. *Product Analyst:* Menganalisis kebutuhan pengguna akhir (Guru, Kepala Sekolah, Siswa, Admin) serta kesesuaian dengan alur kerja pendidikan nyata.
  2. *System Architect:* Memeriksa kode program aktual (Controller, Model, Service, View, Routes, dan Database) untuk memastikan kondisi eksisting.
  3. Memeriksa keterkaitan antar-modul dan potensi dampak regresi.
- **Keluaran (Output):** Dokumen analisis faktual dan spesifikasi kebutuhan fitur.

### Tahap 3: Perancangan Solusi & Rencana Kerja
- **Penanggung Jawab:** AI Project Manager (didukung System Architect).
- **Aktivitas:**
  1. Menyusun langkah kerja implementasi terstruktur (daftar file yang akan ditambah/diubah).
  2. Menentukan tingkat risiko:
     - **Rendah:** Perbaikan teks antarmuka, penyesuaian gaya CSS, perbaikan dokumentasi.
     - **Sedang:** Penambahan fungsi controller baru, view baru, penambahan method service.
     - **Tinggi:** Perubahan skema basis data (migration), perombakan logika autentikasi/RBAC, modifikasi arsitektur AI Copilot/CBT, konfigurasi sistem.
  3. Memaparkan rencana kepada Pemilik Proyek dalam bahasa Indonesia yang mudah dipahami.
- **Keluaran (Output):** Proposal Rencana Kerja.
- **GATEWAY WAJIB:** Menunggu persetujuan tertulis dari Pemilik Proyek sebelum menyentuh berkas kode.

### Tahap 4: Implementasi Terkendali
- **Penanggung Jawab:** AI Developer.
- **Aktivitas:**
  1. Menulis kode sesuai rancangan yang telah disetujui.
  2. Menerapkan standar penulisan kode bersih CodeIgniter 4 (PSR-4, sanitasi input, escaping output via `esc()`).
  3. Memastikan pelepasan session lock (`session_write_close()`) pada controller AI/CBT.
  4. Menjamin tidak menyentuh 6 berkas lisensi terlindungi.
- **Keluaran (Output):** Berkas kode program yang telah diperbarui/dibuat.

### Tahap 5: Pengujian & Audit Integritas
- **Penanggung Jawab:** AI QA Engineer & AI Security Reviewer.
- **Aktivitas:**
  1. *Linter Check:* Menjalankan `php -l [berkas]` untuk memastikan tidak ada kesalahan sintaksis.
  2. *Security Guard Check:* Menjalankan verifikasi lisensi `SecurityGuard::verifyCriticalFiles()` untuk memastikan status tetap `SEC_PASS`.
  3. *Functional Testing:* Menguji fungsi pada role yang berhak dan memastikan role yang tidak berhak diblokir dengan benar.
  4. *Browser & UI Testing:* Memeriksa responsivitas tampilan mobile, tidak ada error JavaScript pada console, dan Speech Synthesis berjalan lancar.
- **Keluaran (Output):** Hasil pengujian dan checklist verifikasi keamanan.

### Tahap 6: Laporan Hasil & Serah Terima
- **Penanggung Jawab:** AI Project Manager.
- **Aktivitas:**
  1. Menyusun laporan akhir dalam format ringkas, ramah non-programmer, dan berbahasa Indonesia.
  2. Menjelaskan secara transparan apa saja yang telah dikerjakan, berkas yang diubah, dan cara mengujinya di localhost.
  3. Meminta konfirmasi peninjauan kepada Pemilik Proyek sebelum melangkah ke tugas berikutnya.
- **Keluaran (Output):** Laporan Penyelesaian Tugas.

---

## 3. Protokol Penanganan Darurat & Rollback

Jika selama implementasi ditemukan kesalahan fatal (misal: halaman menjadi blank atau `SecurityGuard` gagal):
1. **Hentikan Segera:** AI Developer dilarang melanjutkan modifikasi file lain.
2. **Rollback Bersih:** Kembalikan berkas yang baru diubah ke kondisi commit stabil terakhir menggunakan Git (`git checkout -- [file]`).
3. **Audit Ulang:** AI Security Reviewer mengonfirmasi sistem kembali berstatus normal (`SEC_PASS`).
4. **Lapor Transparan:** Melaporkan insiden teknis dan penyebabnya kepada Pemilik Proyek beserta usulan solusi alternatif.
