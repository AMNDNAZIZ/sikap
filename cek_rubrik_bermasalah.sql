-- ========================================
-- CEK RUBRIK YANG BERMASALAH
-- ========================================

-- 1. CEK SEMUA RUBRIK UNTUK DOKUMEN 14
SELECT 
    id,
    document_id,
    dimensi_profil,
    sub_dimensi,
    aspek_dinilai,
    urutan,
    created_at
FROM kokurikuler_rubrik
WHERE document_id = 14
ORDER BY urutan;

-- 2. CEK DETAIL RUBRIK YANG TIDAK MUNCUL SISWA
-- (Kreativitas - Karya)
SELECT * FROM kokurikuler_rubrik
WHERE document_id = 14
  AND dimensi_profil LIKE '%Kreativitas%'
  OR dimensi_profil LIKE '%Kreatifitas%';

-- 3. CEK DETAIL RUBRIK YANG MUNCUL SISWA
-- (Penalaran Kritis - Penyampaian argumentasi)
SELECT * FROM kokurikuler_rubrik
WHERE document_id = 14
  AND dimensi_profil LIKE '%Penalaran%';

-- 4. CEK APAKAH ADA PERBEDAAN STRUKTUR DATA
SELECT 
    id,
    dimensi_profil,
    sub_dimensi,
    LENGTH(dimensi_profil) as len_dimensi,
    LENGTH(sub_dimensi) as len_subdimensi,
    HEX(dimensi_profil) as hex_dimensi,
    HEX(sub_dimensi) as hex_subdimensi
FROM kokurikuler_rubrik
WHERE document_id = 14
ORDER BY urutan;
