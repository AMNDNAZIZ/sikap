-- ========================================
-- SCRIPT CEK DATA SUB DIMENSI
-- ========================================

-- 1. Cek struktur tabel rubrik
DESCRIBE kokurikuler_rubrik;

-- 2. Cek data rubrik untuk dokumen ID 12 (ganti sesuai ID dokumen Anda)
SELECT 
    id,
    document_id,
    dimensi_profil,
    sub_dimensi,
    aspek_dinilai,
    urutan
FROM kokurikuler_rubrik 
WHERE document_id = 12
ORDER BY urutan;

-- 3. Cek kegiatan_detail di dokumen
SELECT 
    id,
    tema,
    jenis_kokurikuler,
    kegiatan_detail
FROM kokurikuler_documents 
WHERE id = 12;

-- 4. Cek semua rubrik yang sub_dimensi-nya NULL atau kosong
SELECT 
    COUNT(*) as total_rubrik_tanpa_subdimensi
FROM kokurikuler_rubrik 
WHERE sub_dimensi IS NULL OR sub_dimensi = '';

-- 5. Cek rubrik per dokumen
SELECT 
    d.id,
    d.tema,
    d.jenis_kokurikuler,
    COUNT(r.id) as total_rubrik,
    SUM(CASE WHEN r.sub_dimensi IS NULL OR r.sub_dimensi = '' THEN 1 ELSE 0 END) as rubrik_tanpa_subdimensi
FROM kokurikuler_documents d
LEFT JOIN kokurikuler_rubrik r ON r.document_id = d.id
GROUP BY d.id
HAVING rubrik_tanpa_subdimensi > 0;

-- ========================================
-- JIKA RUBRIK TIDAK PUNYA SUB_DIMENSI:
-- ========================================

-- Backup dulu (PENTING!)
-- CREATE TABLE kokurikuler_rubrik_backup_20260416 AS SELECT * FROM kokurikuler_rubrik;

-- Hapus rubrik yang tidak punya sub_dimensi untuk dokumen tertentu
-- DELETE FROM kokurikuler_rubrik WHERE document_id = 12 AND (sub_dimensi IS NULL OR sub_dimensi = '');

-- Atau hapus semua rubrik untuk dokumen tertentu
-- DELETE FROM kokurikuler_rubrik WHERE document_id = 12;

-- Setelah dihapus, buka halaman view dokumen untuk auto-generate ulang:
-- http://localhost:8080/siakad/admin/kokurikuler/view/12
