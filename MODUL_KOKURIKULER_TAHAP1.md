# Modul Kokurikuler - Tahap 1: Dokumen Rencana

## Overview

Modul Kokurikuler adalah fitur untuk mengelola kegiatan kokurikuler sesuai Kurikulum Merdeka. Tahap 1 fokus pada pembuatan Dokumen Rencana.

## Hak Akses

- ✅ **Admin** (role_id = 1): Full access
- ✅ **Guru Kelas/Wali Kelas** (role_id = 3): Bisa membuat dan mengelola dokumen sendiri
- ❌ **Guru Mata Pelajaran** (tanpa wali kelas): Tidak bisa akses

## Fitur Utama

### 1. Daftar Dokumen (Index)
- Menampilkan semua dokumen rencana kokurikuler
- Filter otomatis berdasarkan role (guru hanya lihat miliknya)
- Status: Draft atau Completed
- Aksi: Lihat, Export PDF, Hapus

### 2. Buat Dokumen Baru (Create)

#### A. Input Manual (User Input)

**1. Memilih Fase**
- SD: Fase A, B, C
- SMP: Fase D
- SMA: Fase E, F
- Dropdown otomatis sesuai jenjang sekolah

**2. Memilih Level Kelas**
- SD: 1, 2, 3, 4, 5, 6
- SMP: 7, 8, 9
- SMA: 10, 11, 12
- Multiple select (bisa pilih lebih dari 1)

**3. Jumlah Pertemuan**
- Input number
- Minimal 1

**4. Menentukan DPL (Dimensi Profil Lulusan)**
- Select2 multiple
- Minimal 1, maksimal 3
- 8 Dimensi:
  1. Keimanan dan Ketakwaan Terhadap Tuhan YME
  2. Kewargaan (Citizenship)
  3. Penalaran Kritis (Critical Thinking)
  4. Kreativitas (Creativity)
  5. Kolaborasi (Collaboration)
  6. Kemandirian (Self-Regulation)
  7. Kesehatan (Wellbeing)
  8. Komunikasi (Communication)

**5. Membuat Tema**
- Input text
- Contoh: "Menjaga Lingkungan Sekolah"

**6. Bentuk Kegiatan**
- Dropdown dengan 3 pilihan:

**a) Kolaboratif Lintas Disiplin Ilmu**
- Pilih minimal 2 mata pelajaran
- Untuk setiap mata pelajaran, pilih Tujuan Pembelajaran (dari ATP)
- Dynamic form: tambah/kurangi mata pelajaran

**b) Melalui 7 KAIH (Kegiatan Anak Indonesia Hebat)**
- Select2 multiple (minimal 1)
- Pilihan:
  1. Bangun Pagi
  2. Beribadah
  3. Berolahraga
  4. Makan Sehat
  5. Belajar
  6. Bermasyarakat
  7. Tidur Lebih Awal

**c) Kegiatan Lainnya**
- Textarea panjang
- Untuk nilai-nilai/kekhasan sekolah/keunggulan/kebijakan daerah

#### B. Generate AI (Auto Generate)

Setelah input manual, klik tombol "Generate dengan AI" untuk menghasilkan:

**7. Tujuan Pembelajaran**
- Gabungan antara Kompetensi (8 dimensi) dan Konten (tema/kebiasaan/nilai)

**8. Praktik Pedagogis**
- Pembelajaran kolaboratif lintas disiplin
- Inquiry, project-based learning, problem-based learning

**9. Lingkungan Belajar**
- Ruang belajar yang luas (dalam/luar kelas, komunitas, digital)
- Aman, terbuka, inklusif

**10. Kemitraan Pembelajaran**
- Pilihan multiple:
  - Kepala Sekolah
  - Pendidik
  - Tenaga Kependidikan
  - Warga Sekolah Lain
  - Orang Tua
  - Komunitas
  - Dunia Industri
  - Tokoh Masyarakat
  - Instansi Lain
  - Media

**11. Pemanfaatan Teknologi Digital**
- Pilihan multiple:
  - Media Sosial
  - Tayangan Video
  - Internet

**12. Kegiatan Kokurikuler**
- Rincian kegiatan per pertemuan
- Format: Array of objects
  ```json
  [
    {
      "pertemuan": 1,
      "kegiatan": "Judul kegiatan",
      "deskripsi": "Deskripsi detail kegiatan"
    },
    ...
  ]
  ```

### 3. View/Detail Dokumen
- Menampilkan semua informasi dokumen
- Jika status draft, ada tombol "Generate AI"
- Jika status completed, ada tombol "Export PDF"

### 4. Export PDF
- Generate PDF sesuai template
- 2 template berbeda:
  - Template untuk Lintas Disiplin Ilmu
  - Template untuk 7 KAIH

## Database Schema

### Table: kokurikuler_documents

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| year_id | INT | FK to academic_years (auto-detect) |
| fase | VARCHAR(10) | A, B, C, D, E, F |
| level_kelas | VARCHAR(50) | Contoh: "1,2,3" atau "7,8,9" |
| jumlah_pertemuan | INT | Jumlah pertemuan |
| dimensi_profil | TEXT | JSON array (min 1, max 3) |
| tema | VARCHAR(255) | Tema kegiatan |
| bentuk_kegiatan | ENUM | lintas_disiplin, 7kaih, lainnya |
| kegiatan_detail | TEXT | JSON atau text |
| tujuan_pembelajaran | TEXT | AI Generated |
| praktik_pedagogis | TEXT | AI Generated |
| lingkungan_belajar | TEXT | AI Generated |
| kemitraan | TEXT | JSON array |
| teknologi_digital | TEXT | JSON array |
| kegiatan_kokurikuler | TEXT | AI Generated JSON |
| status | ENUM | draft, completed |
| created_by | INT | FK to users |
| created_at | DATETIME | |
| updated_at | DATETIME | |

## Files Created

### 1. Migration
- `app/Database/Migrations/2026-04-09-000001_CreateKokurikulerTables.php`

### 2. Model
- `app/Models/KokurikulerDocumentModel.php`

### 3. Controller
- `app/Controllers/Admin/Kokurikuler.php`

### 4. Views
- `app/Views/admin/kokurikuler/index.php` ✅
- `app/Views/admin/kokurikuler/create.php` (TODO - sangat panjang)
- `app/Views/admin/kokurikuler/view.php` (TODO)
- `app/Views/admin/kokurikuler/pdf_template.php` (TODO)

## Next Steps

### Immediate (Tahap 1)
1. ✅ Create migration
2. ✅ Create model
3. ✅ Create controller
4. ✅ Create index view
5. ⏳ Create form view (create.php) - SANGAT KOMPLEKS
6. ⏳ Create detail view (view.php)
7. ⏳ Create PDF template
8. ⏳ Add routes
9. ⏳ Add menu to sidebar
10. ⏳ Run migration
11. ⏳ Test functionality

### Future (Tahap 2 & 3)
- Tahap 2: Implementasi kegiatan
- Tahap 3: Evaluasi dan pelaporan

## Technical Notes

### Form Complexity
Form create sangat kompleks karena:
1. Dynamic fields berdasarkan pilihan
2. Multiple select dengan Select2
3. Nested forms (lintas disiplin dengan multiple subjects)
4. AJAX untuk load ATP berdasarkan subject
5. Validation yang kompleks

### AI Integration
- Menggunakan API AI yang sama dengan Modul Ajar
- Prompt engineering untuk generate konten kokurikuler
- Fallback jika AI gagal

### PDF Generation
- Menggunakan Dompdf
- 2 template berbeda berdasarkan bentuk_kegiatan
- Include logo sekolah dan kop surat

## Dependencies

- Select2 (untuk multiple select)
- jQuery (untuk dynamic form)
- DataTables (untuk tabel)
- Dompdf (untuk PDF)
- AI API (untuk generate konten)

## Security

- Role-based access control
- Ownership validation (guru hanya akses miliknya)
- CSRF protection
- Input validation
- SQL injection prevention (using Query Builder)

---

**Status**: Tahap 1 - In Progress
**Created**: April 9, 2026
**Next**: Create form view (sangat kompleks, butuh review)
