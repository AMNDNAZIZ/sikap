-- ========================================
-- CEK DATA SISWA UNTUK PENILAIAN
-- ========================================

-- 1. CEK DOKUMEN KOKURIKULER
-- Ganti 14 dengan ID dokumen yang sedang dibuka
SELECT 
    id,
    tema,
    class_id,
    year_id,
    semester,
    fase,
    level_kelas,
    status
FROM kokurikuler_documents
WHERE id = 14; -- GANTI DENGAN ID DOKUMEN ANDA

-- 2. CEK KELAS
-- Ganti dengan class_id dari query #1
SELECT 
    id,
    name,
    level,
    teacher_id
FROM classes
WHERE id = 5; -- GANTI dengan class_id dari dokumen

-- 3. CEK SISWA DI KELAS TERSEBUT (via student_records)
-- Ganti class_id dan year_id sesuai dokumen
SELECT 
    s.id,
    s.nis,
    s.name,
    sr.class_id,
    sr.academic_year_id,
    sr.status,
    c.name as class_name
FROM students s
JOIN student_records sr ON sr.student_id = s.id
JOIN classes c ON c.id = sr.class_id
WHERE sr.class_id = 5 -- GANTI dengan class_id dokumen
  AND sr.academic_year_id = 1 -- GANTI dengan year_id dokumen
ORDER BY s.name;

-- 4. CEK SISWA DENGAN STATUS AKTIF SAJA
SELECT 
    s.id,
    s.nis,
    s.name,
    sr.status,
    c.name as class_name
FROM students s
JOIN student_records sr ON sr.student_id = s.id
JOIN classes c ON c.id = sr.class_id
WHERE sr.class_id = 5 -- GANTI dengan class_id dokumen
  AND sr.academic_year_id = 1 -- GANTI dengan year_id dokumen
  AND sr.status = 'active'
ORDER BY s.name;

-- 5. CEK SEMUA STATUS SISWA DI KELAS
SELECT 
    sr.status,
    COUNT(*) as jumlah
FROM student_records sr
WHERE sr.class_id = 5 -- GANTI dengan class_id dokumen
  AND sr.academic_year_id = 1 -- GANTI dengan year_id dokumen
GROUP BY sr.status;

-- 6. CEK TAHUN AJARAN AKTIF
SELECT 
    id,
    year,
    is_active
FROM academic_years
ORDER BY is_active DESC, id DESC;

-- ========================================
-- TROUBLESHOOTING
-- ========================================

-- Jika tidak ada siswa ditemukan, cek:

-- A. Apakah ada siswa di kelas tersebut?
SELECT COUNT(*) as total_siswa
FROM student_records
WHERE class_id = 5; -- GANTI dengan class_id dokumen

-- B. Apakah ada siswa dengan status 'active'?
SELECT COUNT(*) as total_siswa_aktif
FROM student_records
WHERE class_id = 5 -- GANTI dengan class_id dokumen
  AND status = 'active';

-- C. Apakah year_id sesuai?
SELECT 
    sr.academic_year_id,
    ay.year,
    COUNT(*) as jumlah_siswa
FROM student_records sr
LEFT JOIN academic_years ay ON ay.id = sr.academic_year_id
WHERE sr.class_id = 5 -- GANTI dengan class_id dokumen
GROUP BY sr.academic_year_id;

-- ========================================
-- SOLUSI JIKA TIDAK ADA DATA
-- ========================================

-- Jika tidak ada siswa dengan status 'active', ubah status:
-- UPDATE student_records 
-- SET status = 'active' 
-- WHERE class_id = 5 AND academic_year_id = 1;

-- Jika siswa ada tapi year_id tidak sesuai, update dokumen:
-- UPDATE kokurikuler_documents 
-- SET year_id = (SELECT id FROM academic_years WHERE is_active = 1)
-- WHERE id = 14;
