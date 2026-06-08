-- ============================================
-- FIX: Bank Soal Tidak Muncul Setelah Restore
-- ============================================

-- STEP 1: CEK BANK SOAL HASIL RESTORE
-- --------------------------------------------
SELECT 
    id,
    code,
    subject_id,
    teacher_id,
    is_active,
    created_at
FROM cbt_question_banks
WHERE id = 11;

-- STEP 2: CEK USER YANG SEDANG LOGIN
-- --------------------------------------------
-- GANTI 'username_anda' dengan username yang sedang login
SELECT 
    u.id as user_id,
    u.username,
    u.role_id,
    u.related_type,
    u.related_id as teacher_id,
    t.name as teacher_name
FROM users u
LEFT JOIN teachers t ON t.id = u.related_id
WHERE u.username = 'username_anda';  -- GANTI INI

-- STEP 3: CEK APAKAH TEACHER_ID COCOK
-- --------------------------------------------
-- GANTI 'username_anda' dengan username yang sedang login
SELECT 
    'Bank Soal' as source,
    b.id,
    b.code,
    b.teacher_id,
    NULL as username
FROM cbt_question_banks b
WHERE b.id = 11

UNION ALL

SELECT 
    'User Login' as source,
    u.id,
    u.username,
    u.related_id as teacher_id,
    u.username
FROM users u
WHERE u.username = 'username_anda';  -- GANTI INI

-- STEP 4: FIX - UPDATE TEACHER_ID
-- --------------------------------------------
-- Jika teacher_id tidak cocok, jalankan query ini:
-- GANTI 'TEACHER_ID_YANG_BENAR' dengan teacher_id dari user yang login

-- UPDATE cbt_question_banks
-- SET teacher_id = TEACHER_ID_YANG_BENAR
-- WHERE id = 11;

-- Contoh: Jika teacher_id user adalah 5
-- UPDATE cbt_question_banks
-- SET teacher_id = 5
-- WHERE id = 11;

-- STEP 5: AKTIFKAN BANK SOAL (OPTIONAL)
-- --------------------------------------------
-- Aktifkan bank soal agar lebih mudah ditemukan

-- UPDATE cbt_question_banks
-- SET is_active = 1
-- WHERE id = 11;

-- STEP 6: VERIFIKASI HASIL
-- --------------------------------------------
-- Jalankan query ini untuk memastikan sudah benar
SELECT 
    b.id,
    b.code,
    b.teacher_id,
    b.is_active,
    t.name as teacher_name,
    (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id) as total_soal
FROM cbt_question_banks b
LEFT JOIN teachers t ON t.id = b.teacher_id
WHERE b.id = 11;
