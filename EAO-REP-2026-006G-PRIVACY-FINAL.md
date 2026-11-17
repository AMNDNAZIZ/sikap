# EAO-REP-2026-006G-PRIVACY-FINAL
## Spesifikasi Tata Kelola Privasi Data: Koreksi Batasan Heuristik Zero PII, Keamanan Penyimpanan Respons, dan Penegakan Isolasi Akun

**Nomor Dokumen:** EAO-REP-2026-006G-PRIVACY-FINAL  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (AI Security Reviewer, System Architect, Developer, QA Engineer)  
**Dasar Mandat:** EAO-HO-006G (Butir B.4 — Data Privacy)  
**Status Dokumen:** FINAL SPECIFICATION — READ-ONLY DESIGN REFINEMENT  
**Batasan Kerja:** Desain & analisis murni; dilarang mengubah kode, database, dan 6 file terlindung lisensi.

---

## 1. Koreksi Formal Klaim Zero PII Scrubber: Batasan Heuristik Regex

Pada dokumen desain terdahulu, terdapat pernyataan optimis bahwa 4-Lapis Zero PII Scrubber menjamin anonimisasi data secara sempurna. Sesuai instruksi Human Owner, klaim tersebut dikoreksi secara ilmiah dan jujur:

### 1.1 Batasan Hakiki Pencocokan Pola Berbasis Regex (*Regex Limitations*):
1. **Kekuatan Regex:**  
   Penyaringan berbasis ekspresi reguler (*Regular Expressions*) bekerja sangat efektif dan berkinerja tinggi pada data yang memiliki format terstruktur deterministik:
   - Nomor Induk Kependudukan (NIK 16 digit).
   - Nomor Induk Siswa Nasional (NISN 10 digit).
   - Pola nomor telepon seluler Indonesia (`08xx`, `+628xx`).
   - Alamat surat elektronik (*email address*).
2. **Kelemahan Inheren Regex:**  
   Regex **TIDAK MEMILIKI PEMAHAMAN SEMANTIK BAHASA ALAMI (*Semantic Natural Language Understanding*)**. Jika seorang guru memasukkan nama siswa yang berupa kata umum dalam kamus bahasa Indonesia (misalnya: *"Budi"*, *"Mawar"*, *"Fajar"*, *"Putri"*) di tengah kalimat tanpa penanda label entitas, regex tidak dapat menjamin 100% deteksi tanpa menimbulkan kesalahan pemblokiran kata umum (*false positives*).
3. **Pernyataan Resmi Tata Kelola ESPATA:**  
   Zero PII Scrubber diklasifikasikan sebagai **Lapisan Pertahanan Heuristik Berlapis (*Defense-in-Depth Heuristic Scrubber*)**, bukan jaminan matematis mutlak. Guru dan staf sekolah tetap terikat oleh panduan operasional etika data akademik untuk tidak memasukkan rahasia pribadi siswa ke dalam sistem AI.

---

## 2. Larangan Mutlak Data Rahasia pada `response_payload`

Penyimpanan kolom `response_payload` pada tabel `ai_request_idempotency` tunduk pada batasan keamanan ketat:

### 2.1 Spesifikasi Isi Data yang DILARANG Disimpan:
1. **Dilarang Menyimpan API Key:** API Key mentah (baik milik Guru maupun Administrator) **DILARANG KERAS** disimpan ke dalam kolom payload respons maupun log telemetri.
2. **Dilarang Menyimpan Kredensial Jaringan:** Header `Authorization: Bearer ...`, cookie sesi HTTP, dan token autentikasi vendor dilarang masuk ke basis data.
3. **Dilarang Menyimpan Debug Trace:** Informasi stack trace internal dan parameter koneksi basis data dilarang dicatat ke dalam payload respons.

### 2.2 Data yang Sah Disimpan:
Hanya teks jawaban percakapan terformat (`content` / `message`) serta metadata ringkas (`model`, `usage_tokens`, `finish_reason`) yang diizinkan untuk disimpan demi kebutuhan *Safe Replay*.

---

## 3. Batas Ukuran Penyimpanan Maksimal (64 KB)

Untuk mencegah serangan *Denial of Service (DoS)* melalui pemborosan ruang disk basis data (*storage exhaustion*):
1. **Batas Aplikasi:** Panjang karakter respons dibatasi maksimal **65.536 byte (64 KB)**.
2. **Penanganan Defensif:** Jika respons LLM dari vendor melebihi 64 KB (indikasi loop halusinasi eksternal), teks dipotong secara aman pada batas 64 KB sebelum disimpan, dan kolom status mencatat penanda pemotongan (*truncated*).
3. **Tipe Kolom:** Kolom `response_payload` dideklarasikan bertipe `MEDIUMTEXT` dengan collation `utf8mb4_unicode_ci` untuk memastikan kompatibilitas penuh dengan karakter multibyte aksara Nusantara dan emoji.

---

## 4. Penegakan Isolasi Kepemilikan Respons Berdasarkan `user_id`

Setiap permintaan pembacaan respons (*Safe Replay*) diwajibkan melewati filter otorisasi identitas pengguna:

```sql
-- Kueri Safe Replay Terisolasi Pengguna
SELECT response_payload, status, created_at
FROM ai_request_idempotency
WHERE client_message_id = ?
  AND user_id = ? -- SYARAT MUTLAK: Hanya pemilik sesi login yang berhak membaca!
LIMIT 1;
```

### 4.1 Respon Pelanggaran Akses:
Jika `client_message_id` ditemukan di basis data namun kolom `user_id` tidak sesuai dengan sesi pengguna yang sedang aktif:
- Sistem langsung memblokir akses dengan kode status **`HTTP 403 Forbidden`**.
- Sistem mencatat log peringatan keamanan: `[SecurityAlert] IDOR attempt on client_message_id: {UUID}`.
- Jawaban milik akun lain tidak pernah bocor ke antarmuka pengguna yang berbeda.

---

## 5. Ringkasan Siklus Retensi Privasi Data
1. **Penghapusan Teks Respons:** Kolom `response_payload` di-set menjadi `NULL` secara otomatis setelah **7 hari kalender**.
2. **Pelestarian Identitas Tombstone:** Baris metadata identitas (`client_message_id`, `user_id`, `request_hash`, `quota_status`, `status`) dipertahankan secara **permanen** untuk menjamin sistem tidak akan pernah mengalami *replay amnesia*.
