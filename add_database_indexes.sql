-- ============================================================================
-- DATABASE INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================
-- Generated: 2026-02-25
-- Purpose: Add indexes to frequently queried columns for better performance
-- Estimated Impact: 30-50% query performance improvement
-- ============================================================================

-- Check if running on correct database
SELECT DATABASE() as current_database;

-- ============================================================================
-- STUDENTS TABLE
-- ============================================================================

-- Index for NIS lookups (login, search)
ALTER TABLE students 
ADD INDEX IF NOT EXISTS idx_nis (nis);

-- Index for class-based queries
ALTER TABLE students 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for status filtering (active/inactive/graduated)
ALTER TABLE students 
ADD INDEX IF NOT EXISTS idx_status (status);

-- Composite index for class + status queries
ALTER TABLE students 
ADD INDEX IF NOT EXISTS idx_class_status (class_id, status);

-- ============================================================================
-- USERS TABLE
-- ============================================================================

-- Index for username lookups (login)
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_username (username);

-- Index for email lookups
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_email (email);

-- Index for role-based queries
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_role_id (role_id);

-- Composite index for role + active status
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_role_active (role_id, is_active);

-- ============================================================================
-- TEACHERS TABLE
-- ============================================================================

-- Index for NIP lookups
ALTER TABLE teachers 
ADD INDEX IF NOT EXISTS idx_nip (nip);

-- Index for name searches
ALTER TABLE teachers 
ADD INDEX IF NOT EXISTS idx_name (name);

-- Index for status filtering
ALTER TABLE teachers 
ADD INDEX IF NOT EXISTS idx_status (status);

-- ============================================================================
-- ATTENDANCE TABLE
-- ============================================================================

-- Index for date-based queries (most common)
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_date (date);

-- Index for student attendance history
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_student_id (student_id);

-- Index for class attendance
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Composite index for date + student (attendance reports)
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_date_student (date, student_id);

-- Composite index for date + class (class attendance)
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_date_class (date, class_id);

-- ============================================================================
-- TEACHING ASSIGNMENTS TABLE
-- ============================================================================

-- Index for teacher's assignments
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_teacher_id (teacher_id);

-- Index for class assignments
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for subject assignments
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_subject_id (subject_id);

-- Composite index for teacher + class (most common query)
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_teacher_class (teacher_id, class_id);

-- Composite index for class + subject
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_class_subject (class_id, subject_id);

-- Index for academic year filtering
ALTER TABLE teaching_assignments 
ADD INDEX IF NOT EXISTS idx_academic_year_id (academic_year_id);

-- ============================================================================
-- GRADES TABLE
-- ============================================================================

-- Index for student grades
ALTER TABLE grades 
ADD INDEX IF NOT EXISTS idx_student_id (student_id);

-- Index for subject grades
ALTER TABLE grades 
ADD INDEX IF NOT EXISTS idx_subject_id (subject_id);

-- Index for semester filtering
ALTER TABLE grades 
ADD INDEX IF NOT EXISTS idx_semester (semester);

-- Composite index for student + semester (report cards)
ALTER TABLE grades 
ADD INDEX IF NOT EXISTS idx_student_semester (student_id, semester);

-- Composite index for student + subject
ALTER TABLE grades 
ADD INDEX IF NOT EXISTS idx_student_subject (student_id, subject_id);

-- ============================================================================
-- ASSESSMENTS TABLE
-- ============================================================================

-- Index for student assessments
ALTER TABLE assessments 
ADD INDEX IF NOT EXISTS idx_student_id (student_id);

-- Index for class assessments
ALTER TABLE assessments 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for subject assessments
ALTER TABLE assessments 
ADD INDEX IF NOT EXISTS idx_subject_id (subject_id);

-- Index for assessment type filtering
ALTER TABLE assessments 
ADD INDEX IF NOT EXISTS idx_type (type);

-- Composite index for class + subject + type
ALTER TABLE assessments 
ADD INDEX IF NOT EXISTS idx_class_subject_type (class_id, subject_id, type);

-- ============================================================================
-- CBT SESSIONS TABLE
-- ============================================================================

-- Index for student CBT sessions
ALTER TABLE cbt_sessions 
ADD INDEX IF NOT EXISTS idx_student_id (student_id);

-- Index for test sessions
ALTER TABLE cbt_sessions 
ADD INDEX IF NOT EXISTS idx_test_id (test_id);

-- Index for status filtering (ongoing/completed)
ALTER TABLE cbt_sessions 
ADD INDEX IF NOT EXISTS idx_status (status);

-- Composite index for student + test
ALTER TABLE cbt_sessions 
ADD INDEX IF NOT EXISTS idx_student_test (student_id, test_id);

-- Index for start time (for session management)
ALTER TABLE cbt_sessions 
ADD INDEX IF NOT EXISTS idx_start_time (start_time);

-- ============================================================================
-- CBT ANSWERS TABLE
-- ============================================================================

-- Index for session answers
ALTER TABLE cbt_answers 
ADD INDEX IF NOT EXISTS idx_session_id (session_id);

-- Index for question answers
ALTER TABLE cbt_answers 
ADD INDEX IF NOT EXISTS idx_question_id (question_id);

-- Composite index for session + question (unique answer lookup)
ALTER TABLE cbt_answers 
ADD INDEX IF NOT EXISTS idx_session_question (session_id, question_id);

-- ============================================================================
-- AGENDAS TABLE
-- ============================================================================

-- Index for date-based queries
ALTER TABLE agendas 
ADD INDEX IF NOT EXISTS idx_date (date);

-- Index for class agendas
ALTER TABLE agendas 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for teacher agendas
ALTER TABLE agendas 
ADD INDEX IF NOT EXISTS idx_teacher_id (teacher_id);

-- Composite index for date + class
ALTER TABLE agendas 
ADD INDEX IF NOT EXISTS idx_date_class (date, class_id);

-- ============================================================================
-- ANNOUNCEMENTS TABLE
-- ============================================================================

-- Index for date-based queries
ALTER TABLE announcements 
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Index for author
ALTER TABLE announcements 
ADD INDEX IF NOT EXISTS idx_created_by (created_by);

-- Index for status filtering
ALTER TABLE announcements 
ADD INDEX IF NOT EXISTS idx_is_active (is_active);

-- ============================================================================
-- MATERIALS TABLE
-- ============================================================================

-- Index for class materials
ALTER TABLE materials 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for subject materials
ALTER TABLE materials 
ADD INDEX IF NOT EXISTS idx_subject_id (subject_id);

-- Index for teacher materials
ALTER TABLE materials 
ADD INDEX IF NOT EXISTS idx_teacher_id (teacher_id);

-- Composite index for class + subject
ALTER TABLE materials 
ADD INDEX IF NOT EXISTS idx_class_subject (class_id, subject_id);

-- ============================================================================
-- STUDENT NOTES TABLE
-- ============================================================================

-- Index for student notes
ALTER TABLE student_notes 
ADD INDEX IF NOT EXISTS idx_student_id (student_id);

-- Index for teacher notes
ALTER TABLE student_notes 
ADD INDEX IF NOT EXISTS idx_teacher_id (teacher_id);

-- Index for date filtering
ALTER TABLE student_notes 
ADD INDEX IF NOT EXISTS idx_date (date);

-- ============================================================================
-- EXAM SCHEDULES TABLE
-- ============================================================================

-- Index for date-based queries
ALTER TABLE exam_schedules 
ADD INDEX IF NOT EXISTS idx_exam_date (exam_date);

-- Index for class schedules
ALTER TABLE exam_schedules 
ADD INDEX IF NOT EXISTS idx_class_id (class_id);

-- Index for subject schedules
ALTER TABLE exam_schedules 
ADD INDEX IF NOT EXISTS idx_subject_id (subject_id);

-- Composite index for date + class
ALTER TABLE exam_schedules 
ADD INDEX IF NOT EXISTS idx_date_class (exam_date, class_id);

-- ============================================================================
-- CHAT MESSAGES TABLE (if exists)
-- ============================================================================

-- Index for room messages
ALTER TABLE chat_messages 
ADD INDEX IF NOT EXISTS idx_room_id (room_id);

-- Index for sender messages
ALTER TABLE chat_messages 
ADD INDEX IF NOT EXISTS idx_sender_id (sender_id);

-- Index for timestamp ordering
ALTER TABLE chat_messages 
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Composite index for room + timestamp (message history)
ALTER TABLE chat_messages 
ADD INDEX IF NOT EXISTS idx_room_time (room_id, created_at);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Show indexes for students table
SHOW INDEX FROM students;

-- Show indexes for users table
SHOW INDEX FROM users;

-- Show indexes for attendance table
SHOW INDEX FROM attendance;

-- Show indexes for teaching_assignments table
SHOW INDEX FROM teaching_assignments;

-- Show indexes for grades table
SHOW INDEX FROM grades;

-- ============================================================================
-- ANALYZE TABLES (Update statistics for query optimizer)
-- ============================================================================

ANALYZE TABLE students;
ANALYZE TABLE users;
ANALYZE TABLE teachers;
ANALYZE TABLE attendance;
ANALYZE TABLE teaching_assignments;
ANALYZE TABLE grades;
ANALYZE TABLE assessments;
ANALYZE TABLE cbt_sessions;
ANALYZE TABLE cbt_answers;
ANALYZE TABLE agendas;
ANALYZE TABLE announcements;
ANALYZE TABLE materials;
ANALYZE TABLE student_notes;
ANALYZE TABLE exam_schedules;

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================

SELECT 'Database indexes added successfully!' as status,
       'Run SHOW INDEX FROM table_name to verify' as next_step,
       'Expected improvement: 30-50% faster queries' as impact;

-- ============================================================================
-- NOTES:
-- ============================================================================
-- 1. Indexes improve SELECT performance but slightly slow down INSERT/UPDATE
-- 2. Monitor query performance after adding indexes
-- 3. Use EXPLAIN to analyze query execution plans
-- 4. Consider removing unused indexes if they don't improve performance
-- 5. Run ANALYZE TABLE periodically to update statistics
-- ============================================================================
