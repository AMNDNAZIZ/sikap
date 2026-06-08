-- ========================================
-- CEK MASALAH SINKRONISASI TEMPLATE
-- ========================================

-- 1. CEK DOKUMEN "Ekonomi Sekitar Kita" (yang dibuat admin)
SELECT 
    id,
    tema,
    class_id,
    status,
    level_kelas,
    semester,
    fase,
    year_id,
    created_by,
    created_at,
    CASE 
        WHEN class_id IS NULL THEN '✅ Template (class_id NULL)'
        ELSE CONCAT('❌ Bukan Template (class_id = ', class_id, ')')
    END as check_class_id,
    CASE 
        WHEN status = 'completed' THEN '✅ Completed'
        ELSE CONCAT('❌ Status: ', status)
    END as check_status
FROM kokurikuler_documents
WHERE tema LIKE '%Ekonomi Sekitar Kita%'
ORDER BY created_at DESC;

-- 2. CEK DOKUMEN "Bhineka Tunggal Ika" (yang muncul di pencarian)
SELECT 
    id,
    tema,
    class_id,
    status,
    level_kelas,
    semester,
    fase,
    year_id,
    created_by,
    created_at,
    CASE 
        WHEN class_id IS NULL THEN '✅ Template (class_id NULL)'
        ELSE CONCAT('❌ Bukan Template (class_id = ', class_id, ')')
    END as check_class_id
FROM kokurikuler_documents
WHERE tema LIKE '%Bhineka Tunggal Ika%'
ORDER BY created_at DESC;

-- 3. CEK TAHUN AJARAN AKTIF
SELECT 
    id,
    year,
    is_active,
    CASE 
        WHEN is_active = 1 THEN '✅ Aktif'
        ELSE '❌ Tidak Aktif'
    END as status
FROM academic_years
ORDER BY is_active DESC, id DESC
LIMIT 5;

-- 4. CEK KELAS WALI KELAS YANG LOGIN
-- (Untuk tahu level kelas berapa)
SELECT 
    t.id as teacher_id,
    t.name as teacher_name,
    c.id as class_id,
    c.name as class_name,
    c.level as class_level,
    u.id as user_id,
    u.username
FROM teachers t
LEFT JOIN classes c ON c.teacher_id = t.id
LEFT JOIN users u ON u.related_id = t.id AND u.role_id = 3
WHERE c.level = 6; -- Level 6 karena dokumen untuk level 6

-- 5. CEK SEMUA TEMPLATE UNTUK LEVEL 6
SELECT 
    d.id,
    d.tema,
    d.class_id,
    d.status,
    d.level_kelas,
    d.semester,
    d.fase,
    d.year_id,
    u.fullname as creator,
    u.role_id,
    ay.year as tahun_ajaran,
    ay.is_active,
    CASE 
        WHEN d.class_id IS NULL AND d.status = 'completed' AND ay.is_active = 1 
        THEN '✅ SEHARUSNYA MUNCUL'
        WHEN d.class_id IS NOT NULL THEN '❌ Bukan Template (ada class_id)'
        WHEN d.status != 'completed' THEN '❌ Status bukan completed'
        WHEN ay.is_active != 1 THEN '❌ Tahun ajaran tidak aktif'
        ELSE '❓ Unknown'
    END as hasil_check
FROM kokurikuler_documents d
LEFT JOIN users u ON u.id = d.created_by
LEFT JOIN academic_years ay ON ay.id = d.year_id
WHERE d.level_kelas LIKE '%6%'
ORDER BY d.created_at DESC;

-- ========================================
-- HASIL YANG DIHARAPKAN:
-- ========================================
-- Dokumen "Ekonomi Sekitar Kita" harus:
-- - class_id = NULL
-- - status = completed
-- - year_id = tahun ajaran aktif
-- - level_kelas mengandung "6"
--
-- Jika salah satu tidak terpenuhi, dokumen tidak akan muncul!
-- ========================================
