-- ============================================
-- CEK STRUKTUR TABEL USERS & DATA USER
-- ============================================

-- 1. CEK STRUKTUR TABEL USERS
-- --------------------------------------------
DESCRIBE users;

-- Atau gunakan ini jika DESCRIBE tidak work:
SHOW COLUMNS FROM users;

-- 2. CEK DATA USER YANG SEDANG LOGIN
-- --------------------------------------------
-- GANTI 'username_anda' dengan username yang sedang login
SELECT 
    id,
    username,
    role_id,
    related_type,
    related_id,
    teacher_id,  -- mungkin tidak ada field ini
    fullname
FROM users
WHERE username = 'username_anda';  -- GANTI INI

-- 3. CEK SEMUA FIELD YANG ADA DI TABEL USERS
-- --------------------------------------------
SELECT * FROM users WHERE username = 'username_anda' LIMIT 1;  -- GANTI username_anda

-- 4. CEK RELASI USER DENGAN TEACHER
-- --------------------------------------------
-- GANTI 'username_anda' dengan username yang sedang login
SELECT 
    u.id as user_id,
    u.username,
    u.role_id,
    u.related_type,
    u.related_id,
    t.id as teacher_id_from_table,
    t.name as teacher_name,
    t.nip
FROM users u
LEFT JOIN teachers t ON (
    (u.related_type = 'teacher' AND u.related_id = t.id)
    OR u.teacher_id = t.id
)
WHERE u.username = 'username_anda';  -- GANTI INI

-- 5. CEK APAKAH ADA FIELD teacher_id DI TABEL USERS
-- --------------------------------------------
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'users'
AND COLUMN_NAME IN ('teacher_id', 'related_id', 'related_type');

-- 6. SOLUSI: UPDATE BANK SOAL DENGAN TEACHER_ID YANG BENAR
-- --------------------------------------------
-- Setelah tahu teacher_id yang benar dari query di atas, jalankan:

-- UPDATE cbt_question_banks
-- SET teacher_id = TEACHER_ID_YANG_BENAR
-- WHERE id = 13;  -- atau ID bank soal yang baru di-restore

-- Contoh: Jika teacher_id adalah 5
-- UPDATE cbt_question_banks
-- SET teacher_id = 5
-- WHERE id = 13;
