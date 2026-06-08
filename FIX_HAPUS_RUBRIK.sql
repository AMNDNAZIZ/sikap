-- ========================================
-- SOLUSI: Hapus Rubrik dan Regenerate
-- ========================================

-- STEP 1: Cek dulu dokumen ID berapa yang bermasalah
-- Ganti 12 dengan ID dokumen Anda
SELECT id, tema, jenis_kokurikuler 
FROM kokurikuler_documents 
WHERE id = 12;

-- STEP 2: Backup rubrik dulu (PENTING!)
CREATE TABLE IF NOT EXISTS kokurikuler_rubrik_backup_20260416 AS 
SELECT * FROM kokurikuler_rubrik;

-- STEP 3: Cek rubrik yang akan dihapus
SELECT id, document_id, dimensi_profil, sub_dimensi 
FROM kokurikuler_rubrik 
WHERE document_id = 12;

-- STEP 4: Hapus rubrik untuk dokumen tersebut
-- GANTI 12 dengan ID dokumen Anda
DELETE FROM kokurikuler_rubrik WHERE document_id = 12;

-- STEP 5: Verifikasi sudah terhapus
SELECT COUNT(*) as jumlah_rubrik 
FROM kokurikuler_rubrik 
WHERE document_id = 12;
-- Harusnya return 0

-- ========================================
-- SETELAH QUERY DIJALANKAN:
-- ========================================
-- Buka browser dan akses:
-- http://localhost:8080/siakad/admin/kokurikuler/view/12
-- (Ganti 12 dengan ID dokumen Anda)
--
-- Rubrik akan otomatis di-generate ulang dengan sub_dimensi
-- ========================================
