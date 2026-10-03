-- =========================================================
-- SEED 10 STUDENTS FOR ONLINE PRODUCTION HOSTING
-- Password for all: password123
-- =========================================================

-- Users
INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.santos', 'juan.santos@bsla.edu.ph', '2026-JHS-0101', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.reyes', 'maria.reyes@bsla.edu.ph', '2026-JHS-0102', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.cruz', 'carlos.cruz@bsla.edu.ph', '2026-JHS-0103', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.bautista', 'ana.bautista@bsla.edu.ph', '2026-JHS-0104', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.garcia', 'mark.garcia@bsla.edu.ph', '2026-JHS-0105', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.mendoza', 'patricia.mendoza@bsla.edu.ph', '2026-JHS-0106', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.torres', 'gabriel.torres@bsla.edu.ph', '2026-JHS-0107', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.aquino', 'sophia.aquino@bsla.edu.ph', '2026-JHS-0108', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.navarro', 'miguel.navarro@bsla.edu.ph', '2026-SHS-0109', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)
VALUES (7, 'student.delacruz', 'elena.delacruz@bsla.edu.ph', '2026-SHS-0110', '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', 'Active', NOW())
ON DUPLICATE KEY UPDATE `password` = '$2y$10$fyrE3koFN9p3pVl2jg1tZOYofaPQzAYNrDTjcdhRnHCOu5959ImA6', `status` = 'Active';

-- User Profiles
INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Juan', 'Santos', 'Dela Cruz', 'Male', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.santos'
ON DUPLICATE KEY UPDATE `first_name` = 'Juan', `last_name` = 'Santos';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Maria', 'Reyes', 'Aquino', 'Female', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.reyes'
ON DUPLICATE KEY UPDATE `first_name` = 'Maria', `last_name` = 'Reyes';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Carlos', 'Cruz', 'Bautista', 'Male', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.cruz'
ON DUPLICATE KEY UPDATE `first_name` = 'Carlos', `last_name` = 'Cruz';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Ana', 'Bautista', 'Mendoza', 'Female', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.bautista'
ON DUPLICATE KEY UPDATE `first_name` = 'Ana', `last_name` = 'Bautista';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Mark', 'Garcia', 'Torres', 'Male', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.garcia'
ON DUPLICATE KEY UPDATE `first_name` = 'Mark', `last_name` = 'Garcia';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Patricia', 'Mendoza', 'Navarro', 'Female', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.mendoza'
ON DUPLICATE KEY UPDATE `first_name` = 'Patricia', `last_name` = 'Mendoza';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Gabriel', 'Torres', 'Flores', 'Male', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.torres'
ON DUPLICATE KEY UPDATE `first_name` = 'Gabriel', `last_name` = 'Torres';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Sophia', 'Aquino', 'Ramos', 'Female', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.aquino'
ON DUPLICATE KEY UPDATE `first_name` = 'Sophia', `last_name` = 'Aquino';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Miguel', 'Navarro', 'Castillo', 'Male', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.navarro'
ON DUPLICATE KEY UPDATE `first_name` = 'Miguel', `last_name` = 'Navarro';

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)
SELECT u.id, 'Elena', 'Dela Cruz', 'Villanueva', 'Female', '09171234567', 'Biringan City' FROM users u WHERE u.username = 'student.delacruz'
ON DUPLICATE KEY UPDATE `first_name` = 'Elena', `last_name` = 'Dela Cruz';

-- Enrollments
INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0101', '2026-JHS-0101', u.id, 1, 1, NULL, 2, '109876543201', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.santos' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0101');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0102', '2026-JHS-0102', u.id, 1, 1, NULL, 1, '109876543202', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.reyes' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0102');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0103', '2026-JHS-0103', u.id, 1, 2, NULL, 4, '109876543203', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.cruz' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0103');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0104', '2026-JHS-0104', u.id, 1, 2, NULL, 5, '109876543204', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.bautista' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0104');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0105', '2026-JHS-0105', u.id, 1, 3, NULL, 6, '109876543205', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.garcia' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0105');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0106', '2026-JHS-0106', u.id, 1, 3, NULL, 7, '109876543206', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.mendoza' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0106');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0107', '2026-JHS-0107', u.id, 1, 4, NULL, 8, '109876543207', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.torres' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0107');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0108', '2026-JHS-0108', u.id, 1, 4, NULL, 9, '109876543208', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.aquino' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-JHS-0108');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0109', '2026-SHS-0109', u.id, 1, 5, 1, 10, '109876543209', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.navarro' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-SHS-0109');

INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)
SELECT 'ENR-2026-0110', '2026-SHS-0110', u.id, 1, 6, 2, 24, '109876543210', 'Officially Enrolled', '1st Semester', NOW(), NOW()
FROM users u WHERE u.username = 'student.delacruz' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '2026-SHS-0110');

-- Enrollment Subjects
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 1, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 2, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 3, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 4, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 5, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 6, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 7, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 8, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0101';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 1, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 2, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 3, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 4, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 5, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 6, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 7, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 8, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0102';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 9, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 10, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 11, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 12, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 13, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 14, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 15, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 16, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0103';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 9, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 10, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 11, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 12, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 13, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 14, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 15, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 16, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0104';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 17, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 18, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 19, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 20, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 21, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 22, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 23, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 24, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0105';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 17, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 18, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 19, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 20, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 21, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 22, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 23, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 24, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0106';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 25, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 26, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 27, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 28, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 29, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 30, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 31, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 32, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0107';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 25, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 26, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 27, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 28, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 29, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 30, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 31, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 32, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-JHS-0108';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 33, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 34, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 35, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 36, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 37, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 38, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 39, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 40, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 41, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 42, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 43, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 44, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 45, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 46, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 47, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 58, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 59, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 60, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 61, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 67, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 68, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 69, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 76, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 77, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 78, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 79, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 85, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 86, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 87, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 88, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 92, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 93, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 94, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 99, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 100, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 101, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0109';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 48, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 49, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 50, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 51, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 52, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 53, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 54, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 55, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 56, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 57, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 62, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 63, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 64, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 65, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 66, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 70, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 71, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 72, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 73, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 74, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 75, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 80, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 81, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 82, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 83, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 84, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 89, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 90, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 91, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 95, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 96, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 97, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 98, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 102, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 103, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';
INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)
SELECT e.id, 104, 'Enrolled' FROM enrollments e WHERE e.student_no = '2026-SHS-0110';

-- Student Assessments
INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0101', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0101' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0102', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0102' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0103', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0103' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0104', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0104' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0105', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0105' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0106', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0106' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0107', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0107' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0108', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-JHS-0108' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0109', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-SHS-0109' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)
SELECT e.id, 1, 'ASS-2026-0110', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
FROM enrollments e WHERE e.student_no = '2026-SHS-0110' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);

