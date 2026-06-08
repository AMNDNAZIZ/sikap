-- Query untuk cek hasil restore
-- Jalankan di database untuk melihat bank soal yang baru di-restore

-- 1. Cek bank soal dengan ID 11 (dari log)
SELECT 
    id,
    code,
    subject_id,
    teacher_id,
    is_active,
    created_at,
    updated_at
FROM cbt_question_banks
WHERE id = 11;

-- 2. Cek semua bank soal dengan kode yang mengandung "RESTORED"
SELECT 
    id,
    code,
    subject_id,
    teacher_id,
    is_active,
    created_at,
    updated_at
FROM cbt_question_banks
WHERE code LIKE '%RESTORED%'
ORDER BY id DESC;

-- 3. Cek jumlah soal di bank soal ID 11
SELECT 
    COUNT(*) as total_soal,
    SUM(CASE WHEN question_type = 'pg' THEN 1 ELSE 0 END) as total_pg,
    SUM(CASE WHEN question_type = 'esai' THEN 1 ELSE 0 END) as total_esai
FROM cbt_questions
WHERE bank_id = 11;

-- 4. Cek teacher_id dari user yang sedang login
-- Ganti 'username_guru' dengan username yang sedang login
SELECT 
    u.id as user_id,
    u.username,
    u.role_id,
    u.related_id as teacher_id,
    t.name as teacher_name
FROM users u
LEFT JOIN teachers t ON t.id = u.related_id
WHERE u.username = 'username_guru';  -- GANTI dengan username yang login

-- 5. Cek apakah teacher_id di bank soal cocok dengan teacher_id user
-- Ganti 'username_guru' dengan username yang sedang login
SELECT 
    b.id,
    b.code,
    b.teacher_id as bank_teacher_id,
    u.related_id as user_teacher_id,
    CASE 
        WHEN b.teacher_id = u.related_id THEN 'MATCH ✓'
        ELSE 'NOT MATCH ✗'
    END as status
FROM cbt_question_banks b
CROSS JOIN users u
WHERE b.id = 11
AND u.username = 'username_guru';  -- GANTI dengan username yang login
