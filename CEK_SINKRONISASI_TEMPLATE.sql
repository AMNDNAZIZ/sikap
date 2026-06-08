-- ========================================
-- SCRIPT CEK SINKRONISASI TEMPLATE
-- ========================================

-- 1. CEK DOKUMEN YANG DIBUAT ADMIN
-- Dokumen ini SEHARUSNYA muncul di pencarian wali kelas
SELECT 
    id,
    tema,
    class_id,
    status,
    level_kelas,
    year_id,
    semester,
    created_by,
    CASE 
        WHEN class_id IS NULL AND status = 'completed' THEN '✅ Template Valid'
        WHEN class_id IS NULL AND status != 'completed' THEN '⚠️ Belum Completed'
        WHEN class_id IS NOT NULL THEN '❌ Bukan Template (ada class_id)'
        ELSE '❓ Unknown'
    END as status_template
FROM kokurikuler_documents
WHERE created_by IN (SELECT id FROM users WHERE role_id = 1) -- Admin
ORDER BY created_at DESC;

-- 2. CEK TAHUN AJARAN AKTIF
-- Template harus punya year_id yang sama dengan tahun ajaran aktif
SELECT 
    id,
    year,
    is_active,
    CASE 
        WHEN is_active = 1 THEN '✅ Aktif'
        ELSE '❌ Tidak Aktif'
    END as status
FROM academic_years
ORDER BY is_active DESC, id DESC;

-- 3. CEK KELAS WALI KELAS
-- Untuk melihat level kelas yang diwalikan
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
ORDER BY t.name;

-- 4. CEK TEMPLATE YANG SEHARUSNYA MUNCUL UNTUK WALI KELAS TERTENTU
-- Ganti @teacher_id dengan ID teacher yang ingin dicek
SET @teacher_id = 1; -- GANTI INI
SET @class_level = (SELECT level FROM classes WHERE teacher_id = @teacher_id LIMIT 1);
SET @active_year_id = (SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1);

SELECT 
    d.id,
    d.tema,
    d.class_id,
    d.status,
    d.level_kelas,
    d.year_id,
    u.fullname as creator_name,
    CASE 
        WHEN d.class_id IS NULL THEN '✅ Template Umum'
        ELSE '❌ Bukan Template'
    END as tipe,
    CASE 
        WHEN d.status = 'completed' THEN '✅ Completed'
        ELSE '❌ Draft'
    END as status_dok,
    CASE 
        WHEN d.level_kelas LIKE CONCAT('%', @class_level, '%') THEN '✅ Level Sesuai'
        ELSE '❌ Level Tidak Sesuai'
    END as level_check,
    CASE 
        WHEN d.year_id = @active_year_id THEN '✅ Tahun Aktif'
        ELSE '❌ Tahun Tidak Aktif'
    END as year_check,
    CASE 
        WHEN d.class_id IS NULL 
         AND d.status = 'completed' 
         AND d.level_kelas LIKE CONCAT('%', @class_level, '%')
         AND d.year_id = @active_year_id
        THEN '✅ SEHARUSNYA MUNCUL'
        ELSE '❌ TIDAK MUNCUL'
    END as hasil_akhir
FROM kokurikuler_documents d
LEFT JOIN users u ON u.id = d.created_by
ORDER BY d.created_at DESC;

-- 5. CEK TEMPLATE YANG SUDAH DIGUNAKAN WALI KELAS
-- Ganti @teacher_id dengan ID teacher yang ingin dicek
SELECT 
    d.id,
    d.tema,
    d.parent_id,
    d.used_by_teacher_id,
    d.class_id,
    parent.tema as template_tema,
    c.name as class_name
FROM kokurikuler_documents d
LEFT JOIN kokurikuler_documents parent ON parent.id = d.parent_id
LEFT JOIN classes c ON c.id = d.class_id
WHERE d.used_by_teacher_id = @teacher_id
ORDER BY d.created_at DESC;

-- 6. SUMMARY: Berapa Template yang Tersedia vs Berapa yang Sudah Digunakan
SELECT 
    'Total Template Admin' as keterangan,
    COUNT(*) as jumlah
FROM kokurikuler_documents
WHERE class_id IS NULL 
  AND status = 'completed'
  AND year_id = @active_year_id

UNION ALL

SELECT 
    CONCAT('Template untuk Level ', @class_level) as keterangan,
    COUNT(*) as jumlah
FROM kokurikuler_documents
WHERE class_id IS NULL 
  AND status = 'completed'
  AND year_id = @active_year_id
  AND level_kelas LIKE CONCAT('%', @class_level, '%')

UNION ALL

SELECT 
    CONCAT('Sudah Digunakan oleh Teacher ID ', @teacher_id) as keterangan,
    COUNT(*) as jumlah
FROM kokurikuler_documents
WHERE used_by_teacher_id = @teacher_id
  AND parent_id IS NOT NULL;

-- ========================================
-- TROUBLESHOOTING
-- ========================================

-- Jika template admin TIDAK MUNCUL di pencarian wali kelas, cek:

-- A. Apakah class_id NULL?
SELECT id, tema, class_id 
FROM kokurikuler_documents 
WHERE id = 123; -- Ganti dengan ID dokumen admin
-- Jika class_id ada nilai → Ubah jadi NULL

-- B. Apakah status completed?
SELECT id, tema, status 
FROM kokurikuler_documents 
WHERE id = 123;
-- Jika status = 'draft' → Ubah jadi 'completed'

-- C. Apakah level_kelas sesuai?
SELECT id, tema, level_kelas 
FROM kokurikuler_documents 
WHERE id = 123;
-- Harus ada level yang sesuai dengan kelas wali kelas
-- Misal: level_kelas = "4,5,6" untuk wali kelas level 4

-- D. Apakah year_id sesuai dengan tahun ajaran aktif?
SELECT 
    d.id, 
    d.tema, 
    d.year_id,
    ay.year,
    ay.is_active
FROM kokurikuler_documents d
LEFT JOIN academic_years ay ON ay.id = d.year_id
WHERE d.id = 123;
-- year_id harus sama dengan tahun ajaran yang is_active = 1

-- ========================================
-- FIX MANUAL (Jika Diperlukan)
-- ========================================

-- Fix 1: Ubah class_id jadi NULL (jadikan template)
-- UPDATE kokurikuler_documents SET class_id = NULL WHERE id = 123;

-- Fix 2: Ubah status jadi completed
-- UPDATE kokurikuler_documents SET status = 'completed' WHERE id = 123;

-- Fix 3: Ubah level_kelas
-- UPDATE kokurikuler_documents SET level_kelas = '4,5,6' WHERE id = 123;

-- Fix 4: Ubah year_id ke tahun ajaran aktif
-- UPDATE kokurikuler_documents 
-- SET year_id = (SELECT id FROM academic_years WHERE is_active = 1)
-- WHERE id = 123;
