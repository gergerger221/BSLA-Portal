<?php
// backend/database/setup_sia_database.php
// Complete Automated Schema Builder & Data Seeder for sia_highschool_db

require_once __DIR__ . '/../config/Database.php';

$pdo = App\Config\Database::getConnection();

echo "=== Initializing sia_highschool_db Database Schema ===\n";

$pdo->exec("
    DROP DATABASE IF EXISTS sia_highschool_db;
    CREATE DATABASE sia_highschool_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    USE sia_highschool_db;
    SET FOREIGN_KEY_CHECKS = 0;
");

// 1. Roles
$pdo->exec("
    DROP TABLE IF EXISTS roles;
    CREATE TABLE roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        description VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 2. Users
$pdo->exec("
    DROP TABLE IF EXISTS users;
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id INT NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        student_id VARCHAR(50) NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
        remember_token VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 3. User Profiles
$pdo->exec("
    DROP TABLE IF EXISTS user_profiles;
    CREATE TABLE user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NOT NULL,
        suffix VARCHAR(20) NULL,
        gender ENUM('Male', 'Female') DEFAULT 'Male',
        birthdate DATE NULL,
        contact_number VARCHAR(20) NULL,
        address TEXT NULL,
        avatar_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 4. School Years
$pdo->exec("
    DROP TABLE IF EXISTS school_years;
    CREATE TABLE school_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        active_semester VARCHAR(50) DEFAULT '1st Semester',
        is_active TINYINT(1) DEFAULT 0,
        is_locked TINYINT(1) DEFAULT 0,
        curriculum_locked TINYINT(1) DEFAULT 0,
        curriculum_declared_at TIMESTAMP NULL,
        curriculum_declared_by INT NULL,
        start_date DATE NULL,
        end_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 5. Grade Levels (JHS & SHS)
$pdo->exec("
    DROP TABLE IF EXISTS grade_levels;
    CREATE TABLE grade_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        code VARCHAR(20) NOT NULL UNIQUE,
        category ENUM('JHS', 'SHS') NOT NULL,
        sequence_order INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 6. Tracks
$pdo->exec("
    DROP TABLE IF EXISTS tracks;
    CREATE TABLE tracks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 7. Strands
$pdo->exec("
    DROP TABLE IF EXISTS strands;
    CREATE TABLE strands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        track_id INT NOT NULL,
        code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 8. Subjects
$pdo->exec("
    DROP TABLE IF EXISTS subjects;
    CREATE TABLE subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade_level_id INT NOT NULL,
        strand_id INT NULL,
        prerequisite_id INT NULL,
        code VARCHAR(50) NOT NULL,
        name VARCHAR(150) NOT NULL,
        title VARCHAR(150) NOT NULL,
        category VARCHAR(50) DEFAULT 'Core',
        classification ENUM('Core', 'Applied', 'Specialized', 'Institutional') DEFAULT 'Core',
        semester VARCHAR(30) DEFAULT '1st Semester',
        lecture_hours DECIMAL(4,1) DEFAULT 4.0,
        lab_hours DECIMAL(4,1) DEFAULT 0.0,
        units DECIMAL(4,1) DEFAULT 4.0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 9. Sections
$pdo->exec("
    DROP TABLE IF EXISTS sections;
    CREATE TABLE sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_year_id INT NOT NULL,
        grade_level_id INT NOT NULL,
        strand_id INT NULL,
        name VARCHAR(100) NOT NULL,
        room VARCHAR(50) NULL,
        adviser_id INT NULL,
        max_capacity INT DEFAULT 45,
        capacity INT DEFAULT 45,
        current_enrolled INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        status ENUM('Active', 'Full', 'Archived') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 10. Admission Applications
$pdo->exec("
    DROP TABLE IF EXISTS admission_applications;
    CREATE TABLE admission_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_no VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NULL,
        school_year_id INT NOT NULL,
        applicant_type ENUM('New Student', 'Transferee') DEFAULT 'New Student',
        lrn VARCHAR(20) NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NOT NULL,
        suffix VARCHAR(20) NULL,
        gender ENUM('Male', 'Female') NOT NULL DEFAULT 'Male',
        birthdate DATE NOT NULL DEFAULT '2010-01-01',
        dob DATE NULL,
        birthplace VARCHAR(150) NULL,
        pob VARCHAR(150) NULL,
        civil_status VARCHAR(50) DEFAULT 'Single',
        nationality VARCHAR(50) DEFAULT 'Filipino',
        religion VARCHAR(100) DEFAULT 'Roman Catholic',
        contact_number VARCHAR(20) NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(150) NOT NULL,
        address_street VARCHAR(255) NULL,
        address_barangay VARCHAR(100) NULL,
        address_city VARCHAR(100) NULL,
        address_province VARCHAR(100) NULL,
        address_zip VARCHAR(20) NULL,
        address TEXT NULL,
        guardian_name VARCHAR(150) NULL,
        guardian_relationship VARCHAR(50) NULL,
        guardian_contact VARCHAR(20) NULL,
        guardian_occupation VARCHAR(100) NULL,
        father_name VARCHAR(150) NULL,
        father_contact VARCHAR(20) NULL,
        mother_name VARCHAR(150) NULL,
        mother_contact VARCHAR(20) NULL,
        last_school_attended VARCHAR(150) NULL,
        last_school_type ENUM('Public', 'Private') DEFAULT 'Public',
        last_school_year VARCHAR(50) NULL,
        last_grade_completed VARCHAR(50) NULL,
        grade_level_id INT NULL,
        track_id INT NULL,
        strand_id INT NULL,
        voucher_status VARCHAR(50) DEFAULT 'None',
        voucher_type VARCHAR(50) NULL,
        gwa DECIMAL(5,2) NULL,
        student_no VARCHAR(50) NULL,
        assigned_section_id INT NULL,
        status VARCHAR(100) DEFAULT 'Draft',
        remarks TEXT NULL,
        rejection_reason TEXT NULL,
        reviewed_by INT NULL,
        reviewed_at TIMESTAMP NULL,
        submitted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 11. Admission Documents
$pdo->exec("
    DROP TABLE IF EXISTS admission_documents;
    CREATE TABLE admission_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        submission_mode VARCHAR(50) NOT NULL DEFAULT 'Digital Upload',
        target_date DATE NULL,
        promissory_note TEXT NULL,
        file_path VARCHAR(255) NULL DEFAULT '',
        original_filename VARCHAR(255) NULL DEFAULT '',
        file_name VARCHAR(255) NULL DEFAULT '',
        file_size INT NULL,
        file_type VARCHAR(50) NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        verification_notes TEXT NULL,
        remarks TEXT NULL,
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 12. Enrollments
$pdo->exec("
    DROP TABLE IF EXISTS enrollments;
    CREATE TABLE enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_no VARCHAR(50) NULL,
        student_no VARCHAR(50) NOT NULL,
        student_id INT NULL,
        application_id INT NULL,
        school_year_id INT NOT NULL,
        grade_level_id INT NOT NULL,
        track_id INT NULL,
        strand_id INT NULL,
        section_id INT NULL,
        lrn VARCHAR(20) NULL,
        semester VARCHAR(30) DEFAULT '1st Semester',
        enrollment_date DATE NULL,
        status VARCHAR(100) DEFAULT 'Pending Payment',
        approved_by INT NULL,
        enrolled_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 13. Enrollment Queues
$pdo->exec("
    DROP TABLE IF EXISTS enrollment_queues;
    CREATE TABLE enrollment_queues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        school_year_id INT NOT NULL DEFAULT 1,
        assigned_section_id INT NULL,
        queue_number VARCHAR(20) NOT NULL,
        status VARCHAR(50) DEFAULT 'Waiting Assessment',
        queue_status VARCHAR(50) DEFAULT 'Waiting Assessment',
        queued_by INT NULL,
        called_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_app_sy (application_id, school_year_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 14. Enrollment Subjects
$pdo->exec("
    DROP TABLE IF EXISTS enrollment_subjects;
    CREATE TABLE enrollment_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        subject_id INT NOT NULL,
        schedule_id INT NULL,
        teacher_id INT NULL,
        status ENUM('Enrolled', 'Dropped', 'Completed') DEFAULT 'Enrolled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 15. Fee Categories & Fee Structures
$pdo->exec("
    DROP TABLE IF EXISTS fee_categories;
    CREATE TABLE fee_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(50) NULL,
        description TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    DROP TABLE IF EXISTS fee_structures;
    CREATE TABLE fee_structures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_year_id INT NOT NULL,
        grade_level_id INT NOT NULL,
        category_id INT NULL,
        fee_category_id INT NULL,
        strand_id INT NULL,
        name VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        is_optional TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 16. Student Assessments
$pdo->exec("
    DROP TABLE IF EXISTS student_assessments;
    CREATE TABLE student_assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL UNIQUE,
        school_year_id INT NOT NULL DEFAULT 1,
        assessment_no VARCHAR(50) NULL,
        payment_ticket VARCHAR(50) NULL,
        walkin_ticket_no VARCHAR(50) NULL,
        walkin_scheduled_date DATE NULL,
        walkin_time_slot VARCHAR(100) NULL,
        walkin_location VARCHAR(150) NULL,
        payment_mode VARCHAR(50) NULL,
        payment_verification_status VARCHAR(50) NULL,
        total_tuition DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_miscellaneous DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_misc DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_laboratory DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_lab DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_other_fees DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_assessed DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        voucher_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        net_payable DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        minimum_downpayment DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
        downpayment DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
        total_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        remaining_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'Unpaid',
        assessed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 17. Online Payment Submissions (Awaiting Treasury Verification)
$pdo->exec("
    DROP TABLE IF EXISTS online_payment_submissions;
    CREATE TABLE online_payment_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        enrollment_id INT NOT NULL,
        application_id INT NOT NULL,
        payment_channel VARCHAR(50) NOT NULL DEFAULT 'GCash',
        amount_submitted DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
        reference_no VARCHAR(100) NOT NULL UNIQUE,
        account_name VARCHAR(150) NULL,
        account_number VARCHAR(50) NULL,
        receipt_file_path VARCHAR(255) NULL,
        receipt_original_name VARCHAR(255) NULL,
        status ENUM('Pending Verification', 'Verified', 'Rejected') DEFAULT 'Pending Verification',
        rejection_reason TEXT NULL,
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        or_number VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 18. Payments
$pdo->exec("
    DROP TABLE IF EXISTS payments;
    CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        enrollment_id INT NULL,
        or_number VARCHAR(50) NOT NULL UNIQUE,
        amount_paid DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'Cash',
        payment_date DATE NULL,
        reference_no VARCHAR(100) NULL,
        remarks TEXT NULL,
        received_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 18. Schedules
$pdo->exec("
    DROP TABLE IF EXISTS schedules;
    CREATE TABLE schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        subject_id INT NOT NULL,
        teacher_id INT NULL,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        time_start TIME NOT NULL,
        time_end TIME NOT NULL,
        room VARCHAR(50) NULL,
        semester VARCHAR(30) DEFAULT '1st Semester',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 19. School Events
$pdo->exec("
    DROP TABLE IF EXISTS school_events;
    CREATE TABLE school_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_year_id INT NOT NULL,
        created_by INT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL,
        event_category VARCHAR(50) DEFAULT 'Academic',
        category VARCHAR(50) DEFAULT 'Academic',
        start_date DATE NOT NULL,
        end_date DATE NULL,
        event_date DATE NULL,
        start_time VARCHAR(50) NULL,
        end_time VARCHAR(50) NULL,
        event_time VARCHAR(50) NULL,
        location VARCHAR(150) NULL,
        target_audience VARCHAR(100) DEFAULT 'All',
        is_published TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 20. Document Requests (DRS) & Scholastic Records
$pdo->exec("
    DROP TABLE IF EXISTS document_requests;
    CREATE TABLE document_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        purpose TEXT NOT NULL,
        copies INT DEFAULT 1,
        status ENUM('Pending', 'Processing', 'Ready for Release', 'Released', 'Rejected') DEFAULT 'Pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        released_at TIMESTAMP NULL,
        remarks TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    DROP TABLE IF EXISTS scholastic_records;
    CREATE TABLE scholastic_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        school_year_id INT NOT NULL,
        grade_level_id INT NOT NULL,
        strand_id INT NULL,
        section_id INT NULL,
        general_average DECIMAL(5,2) NULL,
        core_values_json JSON NULL,
        status ENUM('Promoted', 'Retained', 'Conditional') DEFAULT 'Promoted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 21. Audit Logs
$pdo->exec("
    DROP TABLE IF EXISTS audit_logs;
    CREATE TABLE audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT NULL,
        ip_address VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "=== Schema Created Successfully! Seeding Default Data... ===\n";

// ─── SEED ROLES ───
$pdo->exec("
    INSERT INTO roles (id, name, slug, description) VALUES
    (1, 'Super Administrator', 'admin', 'Institutional system administration and controls'),
    (2, 'Academic Coordinator', 'coordinator', 'Curriculum, tracks, strands, and master section scheduling'),
    (3, 'Registrar', 'registrar', 'Admission evaluation, credential verification, and section seating queue'),
    (4, 'Treasury / Cashier', 'treasury', 'Tuition assessment, cashiering, and official receipt issuing'),
    (5, 'School Records Custodian', 'records', 'DepEd SF1, SF5, SF9, SF10, honors, and DRS requests'),
    (6, 'Teacher / Faculty', 'teacher', 'Class instruction, grade encoding, and attendance'),
    (7, 'Enrolled Student', 'student', 'Student portal access, timetable, SOA, and event calendar'),
    (8, 'Admission Applicant', 'applicant', 'Temporary application submission and status tracking');
");

// ─── SEED SCHOOL YEARS ───
$pdo->exec("
    INSERT INTO school_years (id, code, name, is_active, is_locked, start_date, end_date) VALUES
    (1, '2026-2027', 'School Year 2026-2027', 1, 0, '2026-08-15', '2027-05-30');
");

// ─── SEED GRADE LEVELS ───
$pdo->exec("
    INSERT INTO grade_levels (id, name, code, category, sequence_order) VALUES
    (1, 'Grade 7', 'G7', 'JHS', 1),
    (2, 'Grade 8', 'G8', 'JHS', 2),
    (3, 'Grade 9', 'G9', 'JHS', 3),
    (4, 'Grade 10', 'G10', 'JHS', 4),
    (5, 'Grade 11', 'G11', 'SHS', 5),
    (6, 'Grade 12', 'G12', 'SHS', 6);
");

// ─── SEED TRACKS & STRANDS ───
$pdo->exec("
    INSERT INTO tracks (id, code, name, description) VALUES
    (1, 'ACAD', 'Academic Track', 'College preparatory academic strands'),
    (2, 'TVL', 'Technical-Vocational-Livelihood Track', 'Practical job-ready technical skills specialization');

    INSERT INTO strands (id, track_id, code, name, description) VALUES
    (1, 1, 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Advanced sciences, calculus, physics, and research'),
    (2, 1, 'ABM', 'Accountancy, Business, and Management', 'Business finance, marketing, economics, and entrepreneurship'),
    (3, 1, 'HUMSS', 'Humanities and Social Sciences', 'Literature, political science, creative writing, and sociology'),
    (4, 1, 'GAS', 'General Academic Strand', 'Comprehensive multidisciplinary academic electives'),
    (5, 2, 'TVL-ICT', 'Information and Communications Technology', 'Programming, web development, networking, and system servicing'),
    (6, 2, 'TVL-HE', 'Home Economics', 'Commercial cooking, bread & pastry, and tourism hospitality');
");

// ─── SEED FEE CATEGORIES & STRUCTURES ───
$pdo->exec("
    INSERT INTO fee_categories (id, code, name, description) VALUES
    (1, 'TUI', 'Tuition Fees', 'Standard base academic instructional fee'),
    (2, 'LAB', 'Laboratory Fees', 'Computer, Science, and Technical lab fees'),
    (3, 'MISC', 'Miscellaneous Fees', 'Library, medical, athletics, LMS, registration, and ID card fees');

    -- JHS Fees (Grades 7-10)
    INSERT INTO fee_structures (school_year_id, grade_level_id, category_id, fee_category_id, name, amount) VALUES
    (1, 1, 1, 1, 'Base Tuition (Grade 7)', 12500.00),
    (1, 1, 2, 2, 'Computer Science Lab', 1200.00),
    (1, 1, 3, 3, 'Library & Athletic Fee', 1500.00),
    (1, 2, 1, 1, 'Base Tuition (Grade 8)', 12500.00),
    (1, 2, 2, 2, 'Science Lab', 1200.00),
    (1, 2, 3, 3, 'Miscellaneous Fee', 1500.00),
    (1, 3, 1, 1, 'Base Tuition (Grade 9)', 13000.00),
    (1, 3, 2, 2, 'Computer Lab', 1200.00),
    (1, 3, 3, 3, 'Miscellaneous Fee', 1500.00),
    (1, 4, 1, 1, 'Base Tuition (Grade 10)', 13000.00),
    (1, 4, 2, 2, 'Science & Computer Lab', 1400.00),
    (1, 4, 3, 3, 'Graduation & Misc Fee', 1800.00);

    -- SHS Fees (Grades 11-12)
    INSERT INTO fee_structures (school_year_id, grade_level_id, category_id, fee_category_id, name, amount) VALUES
    (1, 5, 1, 1, 'Base Tuition (Grade 11)', 22500.00),
    (1, 5, 2, 2, 'Specialized Lab Fee', 2500.00),
    (1, 5, 3, 3, 'LMS & Registration Fee', 2000.00),
    (1, 6, 1, 1, 'Base Tuition (Grade 12)', 22500.00),
    (1, 6, 2, 2, 'Specialized Lab & Immersion', 2500.00),
    (1, 6, 3, 3, 'Graduation, LMS & Misc Fee', 2500.00);
");

// ─── SEED STAFF & DEMO USERS ───
$passHash = password_hash('password123', PASSWORD_BCRYPT);

$pdo->exec("
    INSERT INTO users (id, role_id, username, email, password, status) VALUES
    (1, 1, 'admin', 'admin@sia.edu.ph', '{$passHash}', 'Active'),
    (2, 2, 'maria_coordinator', 'coordinator@sia.edu.ph', '{$passHash}', 'Active'),
    (3, 3, 'maria_registrar', 'registrar@sia.edu.ph', '{$passHash}', 'Active'),
    (4, 4, 'maria_treasury', 'treasury@sia.edu.ph', '{$passHash}', 'Active'),
    (5, 5, 'maria_records', 'records@sia.edu.ph', '{$passHash}', 'Active'),
    (6, 6, 'prof_delacruz', 'delacruz@sia.edu.ph', '{$passHash}', 'Active'),
    (7, 6, 'prof_santos', 'santos@sia.edu.ph', '{$passHash}', 'Active'),
    (8, 7, '2026-JHS-0001', 'student1@sia.edu.ph', '{$passHash}', 'Active'),
    (9, 7, '2026-SHS-0005', 'student2@sia.edu.ph', '{$passHash}', 'Active'),
    (10, 7, 'student2026', 'demo.student@sia.edu.ph', '{$passHash}', 'Active'),
    (11, 6, 'prof_tan', 'tan@sia.edu.ph', '{$passHash}', 'Active'),
    (12, 6, 'prof_reyes', 'reyes@sia.edu.ph', '{$passHash}', 'Active'),
    (13, 6, 'prof_luna', 'luna@sia.edu.ph', '{$passHash}', 'Active'),
    (14, 6, 'prof_silang', 'silang@sia.edu.ph', '{$passHash}', 'Active'),
    (15, 6, 'prof_aquino', 'aquino@sia.edu.ph', '{$passHash}', 'Active'),
    (16, 6, 'prof_sy', 'sy@sia.edu.ph', '{$passHash}', 'Active'),
    (17, 6, 'prof_gokongwei', 'gokongwei@sia.edu.ph', '{$passHash}', 'Active'),
    (18, 6, 'prof_turing', 'turing@sia.edu.ph', '{$passHash}', 'Active'),
    (19, 6, 'prof_lovelace', 'lovelace@sia.edu.ph', '{$passHash}', 'Active'),
    (20, 6, 'prof_daza', 'daza@sia.edu.ph', '{$passHash}', 'Active'),
    (21, 6, 'prof_gonzales', 'gonzales@sia.edu.ph', '{$passHash}', 'Active'),
    (22, 6, 'prof_pacquiao', 'pacquiao@sia.edu.ph', '{$passHash}', 'Active'),
    (23, 6, 'prof_diaz', 'diaz@sia.edu.ph', '{$passHash}', 'Active'),
    (24, 6, 'prof_obiena', 'obiena@sia.edu.ph', '{$passHash}', 'Active'),
    (25, 6, 'prof_mendoza', 'r.mendoza@sia.edu.ph', '{$passHash}', 'Active'),
    (26, 6, 'prof_ramos', 'c.ramos@sia.edu.ph', '{$passHash}', 'Active'),
    (27, 6, 'prof_valdez', 'a.valdez@sia.edu.ph', '{$passHash}', 'Active'),
    (28, 6, 'prof_castro', 'f.castro@sia.edu.ph', '{$passHash}', 'Active'),
    (29, 6, 'prof_bautista', 'c.bautista@sia.edu.ph', '{$passHash}', 'Active'),
    (30, 6, 'prof_villanueva', 'j.villanueva@sia.edu.ph', '{$passHash}', 'Active'),
    (31, 6, 'prof_navarro', 'k.navarro@sia.edu.ph', '{$passHash}', 'Active'),
    (32, 6, 'prof_garcia', 'r.garcia@sia.edu.ph', '{$passHash}', 'Active'),
    (33, 6, 'prof_morales', 'd.morales@sia.edu.ph', '{$passHash}', 'Active'),
    (34, 6, 'prof_alvarez', 'b.alvarez@sia.edu.ph', '{$passHash}', 'Active'),
    (35, 6, 'prof_salazar', 't.salazar@sia.edu.ph', '{$passHash}', 'Active'),
    (36, 6, 'prof_pascual', 'e.pascual@sia.edu.ph', '{$passHash}', 'Active'),
    (37, 6, 'prof_torres', 'g.torres@sia.edu.ph', '{$passHash}', 'Active'),
    (38, 6, 'prof_mercado', 'r.mercado@sia.edu.ph', '{$passHash}', 'Active'),
    (39, 6, 'prof_lim', 'l.lim@sia.edu.ph', '{$passHash}', 'Active'),
    (40, 6, 'prof_cruz', 'd.cruz@sia.edu.ph', '{$passHash}', 'Active'),
    (41, 6, 'prof_flores', 'm.flores@sia.edu.ph', '{$passHash}', 'Active'),
    (42, 6, 'prof_sison', 'h.sison@sia.edu.ph', '{$passHash}', 'Active'),
    (43, 6, 'prof_velasco', 'l.velasco@sia.edu.ph', '{$passHash}', 'Active'),
    (44, 6, 'prof_soriano', 'r.soriano@sia.edu.ph', '{$passHash}', 'Active'),
    (45, 6, 'prof_aguilar', 'r.aguilar@sia.edu.ph', '{$passHash}', 'Active'),
    (46, 6, 'prof_padilla', 'v.padilla@sia.edu.ph', '{$passHash}', 'Active'),
    (47, 6, 'prof_gutierrez', 'e.gutierrez@sia.edu.ph', '{$passHash}', 'Active');

    INSERT INTO user_profiles (user_id, first_name, middle_name, last_name, contact_number) VALUES
    (1, 'System', 'Admin', 'Officer', '09171110001'),
    (2, 'Maria', 'Clara', 'Coordinator', '09171110002'),
    (3, 'Maria', 'Consuelo', 'Registrar', '09171110003'),
    (4, 'Maria', 'Theresa', 'Treasury', '09171110004'),
    (5, 'Maria', 'Lourdes', 'Records', '09171110005'),
    (6, 'Juan', 'Protacio', 'Dela Cruz', '09171110006'),
    (7, 'Maria', 'Elena', 'Santos', '09171110007'),
    (8, 'Cristiano', 'Aveiro', 'Ronaldo', '09171234567'),
    (9, 'Julian', 'Alvarez', 'Alvares', '09177654321'),
    (10, 'Lynrd', 'Santos', 'Rosales', '09179998888'),
    (11, 'Albert', 'Tan', 'Tan', '09171110011'),
    (12, 'Nicole', 'Reyes', 'Reyes', '09171110012'),
    (13, 'Antonio', 'Luna', 'Luna', '09171110013'),
    (14, 'Gabriela', 'Silang', 'Silang', '09171110014'),
    (15, 'Melchora', 'Aquino', 'Aquino', '09171110015'),
    (16, 'Warren', 'Sy', 'Sy', '09171110016'),
    (17, 'Grace', 'Gokongwei', 'Gokongwei', '09171110017'),
    (18, 'Alan', 'Turing', 'Turing', '09171110018'),
    (19, 'Ada', 'Lovelace', 'Lovelace', '09171110019'),
    (20, 'Nora', 'Daza', 'Daza', '09171110020'),
    (21, 'Gene', 'Gonzales', 'Gonzales', '09171110021'),
    (22, 'Emmanuel', 'Pacquiao', 'Pacquiao', '09171110022'),
    (23, 'Hidilyn', 'Diaz', 'Diaz', '09171110023'),
    (24, 'Ernest', 'Obiena', 'Obiena', '09171110024'),
    (25, 'Ricardo', 'Mendoza', 'Mendoza', '09171110025'),
    (26, 'Clarissa', 'Ramos', 'Ramos', '09171110026'),
    (27, 'Alyssa', 'Valdez', 'Valdez', '09171110027'),
    (28, 'Ferdinand', 'Castro', 'Castro', '09171110028'),
    (29, 'Corazon', 'Bautista', 'Bautista', '09171110029'),
    (30, 'Joshua', 'Villanueva', 'Villanueva', '09171110030'),
    (31, 'Katrina', 'Navarro', 'Navarro', '09171110031'),
    (32, 'Rosario', 'Garcia', 'Garcia', '09171110032'),
    (33, 'Danilo', 'Morales', 'Morales', '09171110033'),
    (34, 'Benjamin', 'Alvarez', 'Alvarez', '09171110034'),
    (35, 'Teresa', 'Salazar', 'Salazar', '09171110035'),
    (36, 'Emilio', 'Pascual', 'Pascual', '09171110036'),
    (37, 'Giselle', 'Torres', 'Torres', '09171110037'),
    (38, 'Ramon', 'Mercado', 'Mercado', '09171110038'),
    (39, 'Lucille', 'Lim', 'Lim', '09171110039'),
    (40, 'Dexter', 'Cruz', 'Cruz', '09171110040'),
    (41, 'Maricris', 'Flores', 'Flores', '09171110041'),
    (42, 'Henrico', 'Sison', 'Sison', '09171110042'),
    (43, 'Lourdes', 'Velasco', 'Velasco', '09171110043'),
    (44, 'Roderick', 'Soriano', 'Soriano', '09171110044'),
    (45, 'Rowena', 'Aguilar', 'Aguilar', '09171110045'),
    (46, 'Vicente', 'Padilla', 'Padilla', '09171110046'),
    (47, 'Elena', 'Gutierrez', 'Gutierrez', '09171110047');

");

// Update student_id on users table
$pdo->exec("
    UPDATE users SET student_id = '2026-JHS-0001' WHERE id = 8;
    UPDATE users SET student_id = '2026-SHS-0005' WHERE id = 9;
    UPDATE users SET student_id = 'STUD-2026-0001' WHERE id = 10;
");

// ─── SEED CLASS SECTIONS (At least 2 sections per grade & strand) ───
$pdo->exec("
    INSERT INTO sections (id, school_year_id, grade_level_id, strand_id, name, room, adviser_id, capacity) VALUES
    -- Grade 7 (JHS)
    (1, 1, 1, NULL, 'Grade 7 - Emerald', 'Building A - Room 101', 6, 45),
    (2, 1, 1, NULL, 'Grade 7 - Diamond', 'Building A - Room 102', 7, 45),
    (3, 1, 1, NULL, 'Grade 7 - Crystal', 'Building A - Room 103', 6, 45),
    
    -- Grade 8 (JHS)
    (4, 1, 2, NULL, 'Grade 8 - Sapphire', 'Building A - Room 201', 6, 45),
    (5, 1, 2, NULL, 'Grade 8 - Topaz', 'Building A - Room 202', 7, 45),
    
    -- Grade 9 (JHS)
    (6, 1, 3, NULL, 'Grade 9 - Ruby', 'Building A - Room 301', 7, 45),
    (7, 1, 3, NULL, 'Grade 9 - Garnet', 'Building A - Room 302', 6, 45),
    
    -- Grade 10 (JHS)
    (8, 1, 4, NULL, 'Grade 10 - Pearl', 'Building A - Room 401', 6, 45),
    (9, 1, 4, NULL, 'Grade 10 - Jade', 'Building A - Room 402', 7, 45),
    
    -- Grade 11 STEM (Strand 1)
    (10, 1, 5, 1, 'Grade 11 - STEM A', 'Science Wing - Room 501', 6, 45),
    (11, 1, 5, 1, 'Grade 11 - STEM B', 'Science Wing - Room 502', 7, 45),
    
    -- Grade 11 ABM (Strand 2)
    (12, 1, 5, 2, 'Grade 11 - ABM A', 'Business Wing - Room 503', 7, 45),
    (13, 1, 5, 2, 'Grade 11 - ABM B', 'Business Wing - Room 504', 6, 45),
    
    -- Grade 11 HUMSS (Strand 3)
    (14, 1, 5, 3, 'Grade 11 - HUMSS A', 'Liberal Arts - Room 505', 6, 45),
    (15, 1, 5, 3, 'Grade 11 - HUMSS B', 'Liberal Arts - Room 506', 7, 45),
    
    -- Grade 11 GAS (Strand 4)
    (16, 1, 5, 4, 'Grade 11 - GAS A', 'Academic Hall - Room 507', 6, 45),
    (17, 1, 5, 4, 'Grade 11 - GAS B', 'Academic Hall - Room 508', 7, 45),
    
    -- Grade 11 TVL-ICT (Strand 5)
    (18, 1, 5, 5, 'Grade 11 - TVL-ICT A', 'Computer Lab 1', 7, 45),
    (19, 1, 5, 5, 'Grade 11 - TVL-ICT B', 'Computer Lab 2', 6, 45),
    
    -- Grade 11 TVL-HE (Strand 6)
    (20, 1, 5, 6, 'Grade 11 - TVL-HE A', 'Culinary Lab 1', 6, 45),
    (21, 1, 5, 6, 'Grade 11 - TVL-HE B', 'Culinary Lab 2', 7, 45),
    
    -- Grade 12 STEM (Strand 1)
    (22, 1, 6, 1, 'Grade 12 - STEM A', 'Science Wing - Room 601', 6, 45),
    (23, 1, 6, 1, 'Grade 12 - STEM B', 'Science Wing - Room 602', 7, 45),
    
    -- Grade 12 ABM (Strand 2)
    (24, 1, 6, 2, 'Grade 12 - ABM A', 'Business Wing - Room 603', 7, 45),
    (25, 1, 6, 2, 'Grade 12 - ABM B', 'Business Wing - Room 604', 6, 45),
    
    -- Grade 12 HUMSS (Strand 3)
    (26, 1, 6, 3, 'Grade 12 - HUMSS A', 'Liberal Arts - Room 605', 6, 45),
    (27, 1, 6, 3, 'Grade 12 - HUMSS B', 'Liberal Arts - Room 606', 7, 45),
    
    -- Grade 12 GAS (Strand 4)
    (28, 1, 6, 4, 'Grade 12 - GAS A', 'Academic Hall - Room 607', 6, 45),
    (29, 1, 6, 4, 'Grade 12 - GAS B', 'Academic Hall - Room 608', 7, 45),
    
    -- Grade 12 TVL-ICT (Strand 5)
    (30, 1, 6, 5, 'Grade 12 - TVL-ICT A', 'Computer Lab 3', 7, 45),
    (31, 1, 6, 5, 'Grade 12 - TVL-ICT B', 'Computer Lab 4', 6, 45),
    
    -- Grade 12 TVL-HE (Strand 6)
    (32, 1, 6, 6, 'Grade 12 - TVL-HE A', 'Culinary Lab 3', 6, 45),
    (33, 1, 6, 6, 'Grade 12 - TVL-HE B', 'Culinary Lab 4', 7, 45);
");

// ─── SEED COMPREHENSIVE DEPED K-12 & MATATAG SUBJECTS & PREREQUISITES ───
$subjectsSeed = [
    // JHS Grade 7
    ['code' => 'ENG-7', 'title' => 'English 7 - Communication Arts', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'MATH-7', 'title' => 'Mathematics 7 - Elementary Algebra & Geometry', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'SCI-7', 'title' => 'Science 7 - Integrated Science', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'FIL-7', 'title' => 'Filipino 7 - Ibong Adarna at Panitikang Rehiyunal', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'AP-7', 'title' => 'Araling Panlipunan 7 - Araling Asyano', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 0.0, 'units' => 3.0, 'prereq_code' => null],
    ['code' => 'ESP-7', 'title' => 'Edukasyon sa Pagpapakatao 7 - Pagpapahalaga at Birtud', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 0.0, 'units' => 2.0, 'prereq_code' => null],
    ['code' => 'TLE-7', 'title' => 'Technology and Livelihood Education 7 (Exploratory)', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 3.0, 'prereq_code' => null],
    ['code' => 'MAPEH-7', 'title' => 'MAPEH 7 (Music, Arts, Physical Education, Health)', 'grade_level_id' => 1, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => null],

    // JHS Grade 8
    ['code' => 'ENG-8', 'title' => 'English 8 - Afro-Asian Literature', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'ENG-7'],
    ['code' => 'MATH-8', 'title' => 'Mathematics 8 - Intermediate Algebra & Logic', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'MATH-7'],
    ['code' => 'SCI-8', 'title' => 'Science 8 - Biology & Ecology', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'SCI-7'],
    ['code' => 'FIL-8', 'title' => 'Filipino 8 - Florante at Laura at Panitikang Pambansa', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'FIL-7'],
    ['code' => 'AP-8', 'title' => 'Araling Panlipunan 8 - Kasaysayan ng Daigdig', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 0.0, 'units' => 3.0, 'prereq_code' => 'AP-7'],
    ['code' => 'ESP-8', 'title' => 'Edukasyon sa Pagpapakatao 8 - Pakikipagkapwa-tao', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 0.0, 'units' => 2.0, 'prereq_code' => 'ESP-7'],
    ['code' => 'TLE-8', 'title' => 'Technology and Livelihood Education 8 (Exploratory TLE)', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 3.0, 'prereq_code' => 'TLE-7'],
    ['code' => 'MAPEH-8', 'title' => 'MAPEH 8 (Asian Music, Arts, PE, Health)', 'grade_level_id' => 2, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'MAPEH-7'],

    // JHS Grade 9
    ['code' => 'ENG-9', 'title' => 'English 9 - Anglo-American Literature', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'ENG-8'],
    ['code' => 'MATH-9', 'title' => 'Mathematics 9 - Advanced Algebra & Trigonometry', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'MATH-8'],
    ['code' => 'SCI-9', 'title' => 'Science 9 - Chemistry & Matter', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'SCI-8'],
    ['code' => 'FIL-9', 'title' => 'Filipino 9 - Noli Me Tangere at Panitikang Asyano', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'FIL-8'],
    ['code' => 'AP-9', 'title' => 'Araling Panlipunan 9 - Ekonomiks', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 0.0, 'units' => 3.0, 'prereq_code' => 'AP-8'],
    ['code' => 'ESP-9', 'title' => 'Edukasyon sa Pagpapakatao 9 - Lipunang Sibil at Katarungan', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 0.0, 'units' => 2.0, 'prereq_code' => 'ESP-8'],
    ['code' => 'TLE-9', 'title' => 'Technology and Livelihood Education 9 (Specialized Tech)', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 3.0, 'prereq_code' => 'TLE-8'],
    ['code' => 'MAPEH-9', 'title' => 'MAPEH 9 (Western Music, Arts, PE, Health)', 'grade_level_id' => 3, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'MAPEH-8'],

    // JHS Grade 10
    ['code' => 'ENG-10', 'title' => 'English 10 - World Literature & Public Speaking', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'ENG-9'],
    ['code' => 'MATH-10', 'title' => 'Mathematics 10 - Sequences, Polynomials, Probability', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'MATH-9'],
    ['code' => 'SCI-10', 'title' => 'Science 10 - Physics & Earth Space', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'SCI-9'],
    ['code' => 'FIL-10', 'title' => 'Filipino 10 - El Filibusterismo at Panitikang Pandaigdig', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'FIL-9'],
    ['code' => 'AP-10', 'title' => 'Araling Panlipunan 10 - Mga Kontemporaryong Isyu', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 3.0, 'lab' => 0.0, 'units' => 3.0, 'prereq_code' => 'AP-9'],
    ['code' => 'ESP-10', 'title' => 'Edukasyon sa Pagpapakatao 10 - Moral na Pagpapasiya', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 0.0, 'units' => 2.0, 'prereq_code' => 'ESP-9'],
    ['code' => 'TLE-10', 'title' => 'Technology and Livelihood Education 10 (NC Prep)', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 3.0, 'prereq_code' => 'TLE-9'],
    ['code' => 'MAPEH-10', 'title' => 'MAPEH 10 (Contemporary Music, Arts, PE, Health)', 'grade_level_id' => 4, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => 'Full Year', 'lec' => 2.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'MAPEH-9'],

    // SHS Core & Applied Grade 11
    ['code' => 'ORAL-COM', 'title' => 'Oral Communication in Context', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'KOM-PAN', 'title' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'GEN-MATH', 'title' => 'General Mathematics', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'EARTH-LIFE', 'title' => 'Earth and Life Science', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'PER-DEV', 'title' => 'Personal Development / Pansariling Kaunlaran', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'PE-1', 'title' => 'Physical Education and Health 1 (Exercise & Fitness)', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 1.0, 'lab' => 1.0, 'units' => 2.0, 'prereq_code' => null],
    ['code' => 'EAPP', 'title' => 'English for Academic and Professional Purposes', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'READ-WRITE', 'title' => 'Reading and Writing Skills', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'ORAL-COM'],
    ['code' => 'PAGBASA-PAGSUSURI', 'title' => 'Pagbasa at Pagsusuri ng Iba\'t Ibang Teksto Tungo sa Pananaliksik', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'KOM-PAN'],
    ['code' => 'STAT-PROB', 'title' => 'Statistics and Probability', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'GEN-MATH'],
    ['code' => 'PHYS-SCI', 'title' => 'Physical Science', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'EARTH-LIFE'],
    ['code' => 'UCSP', 'title' => 'Understanding Culture, Society, and Politics', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'PE-2', 'title' => 'Physical Education and Health 2 (Individual & Dual Sports)', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 1.0, 'units' => 2.0, 'prereq_code' => 'PE-1'],
    ['code' => 'PRAC-RES1', 'title' => 'Practical Research 1 (Qualitative Research)', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'EMP-TECH', 'title' => 'Empowerment Technologies (ICT for Professional Tracks)', 'grade_level_id' => 5, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '2nd Semester', 'lec' => 2.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => null],

    // SHS Core & Applied Grade 12
    ['code' => 'PHIL-PERSON', 'title' => 'Introduction to the Philosophy of the Human Person', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => '21ST-LIT', 'title' => '21st Century Literature from the Philippines and the World', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'MIL', 'title' => 'Media and Information Literacy (MIL)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 3.0, 'lab' => 1.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'PE-3', 'title' => 'Physical Education and Health 3 (Dance & Rhythmic)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '1st Semester', 'lec' => 1.0, 'lab' => 1.0, 'units' => 2.0, 'prereq_code' => 'PE-2'],
    ['code' => 'PRAC-RES2', 'title' => 'Practical Research 2 (Quantitative Research)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'STAT-PROB'],
    ['code' => 'ENTREP', 'title' => 'Entrepreneurship & Business Planning', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'FIL-PILING', 'title' => 'Filipino sa Piling Larang (Akademik/Tech-Voc)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'PAGBASA-PAGSUSURI'],
    ['code' => 'CONTEMP-ARTS', 'title' => 'Contemporary Philippine Arts from the Regions', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'PE-4', 'title' => 'Physical Education and Health 4 (Recreational Activities)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Core', 'classification' => 'Core', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 1.0, 'units' => 2.0, 'prereq_code' => 'PE-3'],
    ['code' => '3IS', 'title' => 'Inquiries, Investigations, and Immersion (3Is)', 'grade_level_id' => 6, 'strand_id' => null, 'category' => 'Applied', 'classification' => 'Applied', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'PRAC-RES2'],

    // STEM Specialized
    ['code' => 'PRE-CALC', 'title' => 'Pre-Calculus', 'grade_level_id' => 5, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'GEN-BIO1', 'title' => 'General Biology 1', 'grade_level_id' => 5, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'BASIC-CALC', 'title' => 'Basic Calculus', 'grade_level_id' => 5, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'PRE-CALC'],
    ['code' => 'GEN-BIO2', 'title' => 'General Biology 2', 'grade_level_id' => 5, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'GEN-BIO1'],
    ['code' => 'GEN-PHYS1', 'title' => 'General Physics 1', 'grade_level_id' => 6, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'BASIC-CALC'],
    ['code' => 'GEN-CHEM1', 'title' => 'General Chemistry 1', 'grade_level_id' => 6, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'GEN-PHYS2', 'title' => 'General Physics 2', 'grade_level_id' => 6, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'GEN-PHYS1'],
    ['code' => 'GEN-CHEM2', 'title' => 'General Chemistry 2', 'grade_level_id' => 6, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 3.0, 'lab' => 2.0, 'units' => 4.0, 'prereq_code' => 'GEN-CHEM1'],
    ['code' => 'STEM-CAPSTONE', 'title' => 'Research Capstone / STEM Culminating Activity', 'grade_level_id' => 6, 'strand_id' => 1, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 3.0, 'units' => 4.0, 'prereq_code' => '3IS'],

    // ABM Specialized
    ['code' => 'BUS-MATH', 'title' => 'Business Mathematics', 'grade_level_id' => 5, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'ORG-MGMT', 'title' => 'Organization and Management', 'grade_level_id' => 5, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'FABM-1', 'title' => 'Fundamentals of Accountancy, Business, and Management 1', 'grade_level_id' => 5, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'BUS-MATH'],
    ['code' => 'PRIN-MKTG', 'title' => 'Principles of Marketing', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'ORG-MGMT'],
    ['code' => 'FABM-2', 'title' => 'Fundamentals of Accountancy, Business, and Management 2', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'FABM-1'],
    ['code' => 'APP-ECON', 'title' => 'Applied Economics in Philippine Setting', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'BUS-FIN', 'title' => 'Business Finance', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'FABM-2'],
    ['code' => 'BUS-ETHICS', 'title' => 'Business Ethics and Social Responsibility', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'ABM-SIMULATION', 'title' => 'Business Enterprise Simulation / ABM Immersion', 'grade_level_id' => 6, 'strand_id' => 2, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 3.0, 'units' => 4.0, 'prereq_code' => 'FABM-2'],

    // HUMSS Specialized
    ['code' => 'CREATIVE-WRITE', 'title' => 'Creative Writing / Malikhaing Pagsulat', 'grade_level_id' => 5, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'DISS', 'title' => 'Disciplines and Ideas in the Social Sciences', 'grade_level_id' => 5, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'CREATIVE-NONFIC', 'title' => 'Creative Nonfiction: The Literary Essay', 'grade_level_id' => 5, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'CREATIVE-WRITE'],
    ['code' => 'DIASS', 'title' => 'Disciplines and Ideas in the Applied Social Sciences', 'grade_level_id' => 5, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'DISS'],
    ['code' => 'PH-POLITICS', 'title' => 'Philippine Politics and Governance', 'grade_level_id' => 6, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'DISS'],
    ['code' => 'TRENDS-NETWORKS', 'title' => 'Trends, Networks, and Critical Thinking in the 21st Century', 'grade_level_id' => 6, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'CESC', 'title' => 'Community Engagement, Solidarity, and Citizenship', 'grade_level_id' => 6, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'PH-POLITICS'],
    ['code' => 'WORLD-RELIGIONS', 'title' => 'Introduction to World Religions and Belief Systems', 'grade_level_id' => 6, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'HUMSS-CULMINATING', 'title' => 'HUMSS Culminating Activity & Advocacy Portfolio', 'grade_level_id' => 6, 'strand_id' => 3, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 3.0, 'units' => 4.0, 'prereq_code' => 'CESC'],

    // GAS Specialized
    ['code' => 'HUMANITIES-1', 'title' => 'Humanities 1 (Creative Writing / Philippine Arts)', 'grade_level_id' => 5, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'SOC-SCI-1', 'title' => 'Social Science 1 (Philippine Politics / DISS)', 'grade_level_id' => 5, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'HUMANITIES-2', 'title' => 'Humanities 2 (World Religions / Literature)', 'grade_level_id' => 5, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'HUMANITIES-1'],
    ['code' => 'GAS-ORG-MGMT', 'title' => 'Organization and Management (GAS Applied)', 'grade_level_id' => 5, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'GAS-ELECTIVE-1', 'title' => 'General Academic Elective 1 (Selected Track Focus)', 'grade_level_id' => 6, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'GAS-ELECTIVE-2', 'title' => 'General Academic Elective 2 (Advanced Focus)', 'grade_level_id' => 6, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 4.0, 'lab' => 0.0, 'units' => 4.0, 'prereq_code' => 'GAS-ELECTIVE-1'],
    ['code' => 'GAS-CULMINATING', 'title' => 'GAS Culminating Activity / Academic Portfolio', 'grade_level_id' => 6, 'strand_id' => 4, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 3.0, 'units' => 4.0, 'prereq_code' => null],

    // TVL-ICT Specialized
    ['code' => 'CSS-MOD1', 'title' => 'Computer Systems Servicing NC II - Module 1 (Hardware & OS)', 'grade_level_id' => 5, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'TECH-DRAFTING', 'title' => 'Technical Drafting & AutoCAD NC II', 'grade_level_id' => 5, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 2.0, 'units' => 3.0, 'prereq_code' => null],
    ['code' => 'CSS-MOD2', 'title' => 'Computer Systems Servicing NC II - Module 2 (Computer Networks)', 'grade_level_id' => 5, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'CSS-MOD1'],
    ['code' => 'CSS-MOD3', 'title' => 'Computer Systems Servicing NC II - Module 3 (Server & Cloud Setup)', 'grade_level_id' => 6, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'CSS-MOD2'],
    ['code' => 'WEB-DEV-FUND', 'title' => 'Web Development & Database Foundations', 'grade_level_id' => 6, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 3.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'CSS-MOD4', 'title' => 'Computer Systems Servicing NC II - Module 4 (Maintenance & Cyber)', 'grade_level_id' => 6, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'CSS-MOD3'],
    ['code' => 'TVL-ICT-IMMERSION', 'title' => 'TVL-ICT Industry Work Immersion (80 Hours)', 'grade_level_id' => 6, 'strand_id' => 5, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'CSS-MOD3'],

    // TVL-HE Specialized
    ['code' => 'BREAD-PASTRY', 'title' => 'Bread and Pastry Production NC II', 'grade_level_id' => 5, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'TOURISM-PROMO', 'title' => 'Tourism Promotion & Front Office Services NC II', 'grade_level_id' => 5, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 3.0, 'lab' => 1.0, 'units' => 4.0, 'prereq_code' => null],
    ['code' => 'COOKERY-1', 'title' => 'Cookery NC II - Part 1 (Food Safety & Cold Kitchen)', 'grade_level_id' => 5, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'BREAD-PASTRY'],
    ['code' => 'COOKERY-2', 'title' => 'Cookery NC II - Part 2 (Hot Kitchen & Commercial Cooking)', 'grade_level_id' => 6, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '1st Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'COOKERY-1'],
    ['code' => 'FBS-NC2', 'title' => 'Food and Beverage Services (FBS NC II)', 'grade_level_id' => 6, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 2.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'COOKERY-2'],
    ['code' => 'TVL-HE-IMMERSION', 'title' => 'TVL-HE Hospitality & Culinary Work Immersion', 'grade_level_id' => 6, 'strand_id' => 6, 'category' => 'Specialized', 'classification' => 'Specialized', 'semester' => '2nd Semester', 'lec' => 1.0, 'lab' => 4.0, 'units' => 4.0, 'prereq_code' => 'COOKERY-2'],
];

$stmtSub = $pdo->prepare("
    INSERT INTO subjects (grade_level_id, strand_id, code, name, title, category, classification, semester, lecture_hours, lab_hours, units, is_active)
    VALUES (:gid, :sid, :code, :title, :title2, :cat, :class, :sem, :lec, :lab, :units, 1)
    ON DUPLICATE KEY UPDATE
        grade_level_id = VALUES(grade_level_id),
        strand_id = VALUES(strand_id),
        name = VALUES(name),
        title = VALUES(title),
        category = VALUES(category),
        classification = VALUES(classification),
        semester = VALUES(semester),
        lecture_hours = VALUES(lecture_hours),
        lab_hours = VALUES(lab_hours),
        units = VALUES(units),
        is_active = 1
");

foreach ($subjectsSeed as $s) {
    $stmtSub->execute([
        'gid'   => $s['grade_level_id'],
        'sid'   => $s['strand_id'],
        'code'  => $s['code'],
        'title' => $s['title'],
        'title2'=> $s['title'],
        'cat'   => $s['category'],
        'class' => $s['classification'],
        'sem'   => $s['semester'],
        'lec'   => $s['lec'],
        'lab'   => $s['lab'],
        'units' => $s['units']
    ]);
}

$allSubsMap = $pdo->query("SELECT code, id FROM subjects")->fetchAll(PDO::FETCH_KEY_PAIR);
$updatePrereqStmt = $pdo->prepare("UPDATE subjects SET prerequisite_id = :prereq_id WHERE code = :code");

foreach ($subjectsSeed as $s) {
    if (!empty($s['prereq_code']) && isset($allSubsMap[$s['prereq_code']])) {
        $updatePrereqStmt->execute([
            'prereq_id' => $allSubsMap[$s['prereq_code']],
            'code'      => $s['code']
        ]);
    }
}

// ─── SEED SAMPLE ADMISSIONS & ENROLLMENTS ───
$pdo->exec("
    -- Sample Enrolled Student 1 (JHS)
    INSERT INTO admission_applications (
        id, application_no, user_id, school_year_id, grade_level_id, strand_id, student_no, lrn,
        first_name, middle_name, last_name, gender, birthdate, email, address, status
    ) VALUES (
        1, 'ADM-2026-0001', 8, 1, 1, NULL, '2026-JHS-0001', '102938475611',
        'Cristiano', 'Aveiro', 'Ronaldo', 'Male', '2012-02-05', 'student1@sia.edu.ph', '123 Stadium Ave, Manila', 'Enrolled'
    );

    INSERT INTO enrollments (
        id, enrollment_no, application_id, student_id, school_year_id, grade_level_id, strand_id, section_id,
        student_no, semester, status, enrolled_at
    ) VALUES (
        1, 'ENR-2026-0001', 1, 8, 1, 1, NULL, 1,
        '2026-JHS-0001', '1st Semester', 'Officially Enrolled', NOW()
    );

    INSERT INTO student_assessments (
        id, enrollment_id, total_tuition, total_lab, total_misc, voucher_discount,
        total_assessed, amount_paid, remaining_balance, status
    ) VALUES (
        1, 1, 12500.00, 1200.00, 1500.00, 0.00,
        15200.00, 15200.00, 0.00, 'Paid'
    );

    INSERT INTO payments (assessment_id, or_number, amount_paid, payment_method, received_by) VALUES
    (1, 'OR-2026-000001', 15200.00, 'Cash', 4);

    -- Sample Enrolled Student 2 (SHS STEM)
    INSERT INTO admission_applications (
        id, application_no, user_id, school_year_id, grade_level_id, strand_id, student_no, lrn,
        first_name, middle_name, last_name, gender, birthdate, email, address, voucher_status, status
    ) VALUES (
        2, 'ADM-2026-0002', 9, 1, 5, 1, '2026-SHS-0005', '102938475622',
        'Julian', 'Alvarez', 'Alvares', 'Male', '2008-01-31', 'student2@sia.edu.ph', '456 Champion St, Quezon City', 'Public JHS Completer (100% Voucher)', 'Enrolled'
    );

    INSERT INTO enrollments (
        id, enrollment_no, application_id, student_id, school_year_id, grade_level_id, strand_id, section_id,
        student_no, semester, status, enrolled_at
    ) VALUES (
        2, 'ENR-2026-0002', 2, 9, 1, 5, 1, 6,
        '2026-SHS-0005', '1st Semester', 'Officially Enrolled', NOW()
    );

    INSERT INTO student_assessments (
        id, enrollment_id, total_tuition, total_lab, total_misc, voucher_discount,
        total_assessed, amount_paid, remaining_balance, status
    ) VALUES (
        2, 2, 22500.00, 2500.00, 2000.00, 22500.00,
        4500.00, 4500.00, 0.00, 'Paid'
    );

    INSERT INTO payments (assessment_id, or_number, amount_paid, payment_method, received_by) VALUES
    (2, 'OR-2026-000002', 4500.00, 'GCash', 4);

    -- Sample Enrollee 3 in Queue for Demonstration (Lynrd Rosales)
    INSERT INTO admission_applications (
        id, application_no, user_id, school_year_id, grade_level_id, strand_id, student_no, lrn,
        first_name, middle_name, last_name, gender, birthdate, email, address, voucher_status, status
    ) VALUES (
        3, 'ADM-2026-0003', 10, 1, 5, 1, 'STUD-2026-0001', '102938475633',
        'Lynrd', 'Santos', 'Rosales', 'Male', '2008-05-15', 'demo.student@sia.edu.ph', '789 Mabini St, Manila', 'Public JHS Completer (100% Voucher)', 'Approved for Enrollment'
    );

    INSERT INTO enrollment_queues (application_id, assigned_section_id, queue_number, queue_status) VALUES
    (3, 6, 'Q-001', 'Waiting');

    INSERT INTO enrollments (
        id, enrollment_no, application_id, student_id, school_year_id, grade_level_id, strand_id, section_id,
        student_no, semester, status
    ) VALUES (
        3, 'ENR-2026-0003', 3, 10, 1, 5, 1, 6,
        'STUD-2026-0001', '1st Semester', 'Pending Payment'
    );

    INSERT INTO student_assessments (
        id, enrollment_id, total_tuition, total_lab, total_misc, voucher_discount,
        total_assessed, amount_paid, remaining_balance, status
    ) VALUES (
        3, 3, 22500.00, 2500.00, 2000.00, 22500.00,
        4500.00, 0.00, 4500.00, 'Unpaid'
    );
");

// ─── SEED 100% CONFLICT-FREE DUAL-SEMESTER SCHEDULES FOR ALL 33 SECTIONS ───
$sectionsDb = $pdo->query("SELECT id, grade_level_id, strand_id, room FROM sections ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$subjectsDb = $pdo->query("SELECT id, code, category, classification, grade_level_id, strand_id, semester FROM subjects WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
$teachersDb = $pdo->query("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug IN ('teacher', 'coordinator') AND u.status = 'Active'")->fetchAll(PDO::FETCH_COLUMN);

$periodsTbl = [
    1 => ['start' => '07:30:00', 'end' => '08:30:00'],
    2 => ['start' => '08:30:00', 'end' => '09:30:00'],
    3 => ['start' => '09:50:00', 'end' => '10:50:00'],
    4 => ['start' => '10:50:00', 'end' => '11:50:00'],
    5 => ['start' => '12:50:00', 'end' => '13:50:00'],
    6 => ['start' => '13:50:00', 'end' => '14:50:00'],
    7 => ['start' => '14:50:00', 'end' => '15:50:00']
];
$daysTbl = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

$insSched = $pdo->prepare("
    INSERT INTO schedules (section_id, subject_id, teacher_id, day_of_week, start_time, end_time, time_start, time_end, room, semester, is_active, created_at)
    VALUES (:sec_id, :sub_id, :t_id, :day, :t_start, :t_end, :t_start2, :t_end2, :room, :sem, 1, NOW())
");

$jhsTOcc = [];
$jhsROcc = [];

foreach (['1st Semester', '2nd Semester'] as $semIndex => $sem) {
    $tOcc = ($sem === '2nd Semester') ? $jhsTOcc : [];
    $sOcc = [];
    $rOcc = ($sem === '2nd Semester') ? $jhsROcc : [];

    foreach ($sectionsDb as $secIndex => $sc) {
        $sId = (int)$sc['id'];
        $gId = (int)$sc['grade_level_id'];
        $stId = $sc['strand_id'] ? (int)$sc['strand_id'] : null;
        $rm = trim($sc['room']);
        $isJHS = ($gId <= 4);

        if ($isJHS && $sem === '2nd Semester') {
            continue;
        }

        $secSubs = array_values(array_filter($subjectsDb, function($s) use ($gId, $stId, $sem, $isJHS) {
            if ((int)$s['grade_level_id'] !== $gId) return false;
            if ($stId !== null) {
                if ($s['strand_id'] !== null && (int)$s['strand_id'] !== $stId) return false;
            } else {
                if ($s['strand_id'] !== null) return false;
            }
            if ($isJHS) {
                return ($s['semester'] === 'Full Year' || $s['semester'] === '1st Semester');
            }
            return ($s['semester'] === $sem);
        }));

        $numSubs = count($secSubs);
        if ($numSubs === 0) continue;

        $targetSemLabel = $isJHS ? 'Full Year' : $sem;

        for ($dIdx = 0; $dIdx < count($daysTbl); $dIdx++) {
            $d = $daysTbl[$dIdx];
            $dailyCount = min(6, $numSubs);
            $offset = ($secIndex * 2 + $dIdx + $semIndex * 3) % $numSubs;
            $usedToday = [];

            for ($p = 1; $p <= $dailyCount; $p++) {
                $pTime = $periodsTbl[$p];
                $selSub = null;
                for ($att = 0; $att < $numSubs; $att++) {
                    $cand = $secSubs[($offset + $p - 1 + $att) % $numSubs];
                    if (!isset($usedToday[$cand['id']])) {
                        $selSub = $cand;
                        break;
                    }
                }
                if (!$selSub) continue;

                $codeU = strtoupper($selSub['code']);
                if (strpos($codeU, 'MATH') !== false || strpos($codeU, 'CALC') !== false || strpos($codeU, 'STAT') !== false) {
                    $tPool = [6, 25, 26, 16, 11, 2, 40];
                } elseif (strpos($codeU, 'SCI') !== false || strpos($codeU, 'PHYS') !== false || strpos($codeU, 'CHEM') !== false || strpos($codeU, 'BIO') !== false || strpos($codeU, 'EARTH') !== false) {
                    $tPool = [7, 27, 28, 29, 6, 18, 11];
                } elseif (strpos($codeU, 'ENG') !== false || strpos($codeU, 'ORAL') !== false || strpos($codeU, 'READ') !== false || strpos($codeU, 'LIT') !== false || strpos($codeU, 'EAPP') !== false) {
                    $tPool = [11, 30, 31, 14, 12, 13, 2];
                } elseif (strpos($codeU, 'FIL') !== false || strpos($codeU, 'KOM') !== false || strpos($codeU, 'PAGBASA') !== false) {
                    $tPool = [12, 32, 33, 14, 13, 11, 15];
                } elseif (strpos($codeU, 'AP-') !== false || strpos($codeU, 'ECON') !== false || strpos($codeU, 'POLITIC') !== false || strpos($codeU, 'DISS') !== false) {
                    $tPool = [13, 34, 35, 14, 2, 12, 17];
                } elseif (strpos($codeU, 'ESP') !== false || strpos($codeU, 'PHIL') !== false || strpos($codeU, 'UCSP') !== false) {
                    $tPool = [14, 36, 37, 13, 15, 12, 2];
                } elseif (strpos($codeU, 'MAPEH') !== false || strpos($codeU, 'PE-') !== false) {
                    $tPool = [15, 44, 45, 22, 23, 24, 14];
                } elseif (strpos($codeU, 'FABM') !== false || strpos($codeU, 'BUS-') !== false || strpos($codeU, 'MKTG') !== false || strpos($codeU, 'ORG-MGMT') !== false) {
                    $tPool = [16, 17, 38, 39, 6, 13, 18];
                } elseif (strpos($codeU, 'CSS-') !== false || strpos($codeU, 'DRAFTING') !== false || strpos($codeU, 'WEB-') !== false || strpos($codeU, 'EMP-TECH') !== false || strpos($codeU, 'TLE') !== false) {
                    $tPool = [18, 19, 40, 41, 15, 14, 11];
                } elseif (strpos($codeU, 'BREAD') !== false || strpos($codeU, 'COOKERY') !== false || strpos($codeU, 'FBS') !== false) {
                    $tPool = [20, 21, 42, 43, 17, 15, 12];
                } else {
                    $tPool = [2, 46, 47, 11, 7, 6, 13];
                }

                $rotPool = array_merge(
                    array_slice($tPool, $secIndex % count($tPool)),
                    array_slice($tPool, 0, $secIndex % count($tPool))
                );

                $selT = null;
                foreach ($rotPool as $tId) {
                    if (empty($tOcc[$tId][$d][$p])) {
                        $selT = $tId;
                        break;
                    }
                }
                if (!$selT) {
                    foreach ($teachersDb as $fId) {
                        if (empty($tOcc[$fId][$d][$p])) {
                            $selT = $fId;
                            break;
                        }
                    }
                }
                if (!$selT) continue;
                if (!empty($sOcc[$sId][$d][$p]) || !empty($rOcc[$rm][$d][$p])) continue;

                $usedToday[$selSub['id']] = true;
                $sOcc[$sId][$d][$p] = true;
                $rOcc[$rm][$d][$p] = true;
                $tOcc[$selT][$d][$p] = true;

                if ($isJHS) {
                    $jhsTOcc[$selT][$d][$p] = true;
                    $jhsROcc[$rm][$d][$p] = true;
                }

                $insSched->execute([
                    'sec_id'   => $sId,
                    'sub_id'   => $selSub['id'],
                    't_id'     => $selT,
                    'day'      => $d,
                    't_start'  => $pTime['start'],
                    't_end'    => $pTime['end'],
                    't_start2' => $pTime['start'],
                    't_end2'   => $pTime['end'],
                    'room'     => $rm,
                    'sem'      => $targetSemLabel
                ]);
            }
        }
    }
}

// ─── SEED SAMPLE ADMISSION DOCUMENTS ───
$pdo->exec("
    INSERT INTO admission_documents (application_id, document_type, file_path, original_filename, file_name, file_size, status, verification_notes, verified_by, verified_at) VALUES
    (3, 'PSA Birth Certificate', 'uploads/psa_sample.pdf', 'psa_sample.pdf', 'psa_sample.pdf', 125400, 'Verified', 'Original PSA verified clear.', 3, NOW()),
    (3, 'SF9 / Form 138 (Report Card)', 'uploads/form138_sample.pdf', 'form138_sample.pdf', 'form138_sample.pdf', 240100, 'Verified', 'GWA 91.50 JHS Completer verified.', 3, NOW()),
    (3, 'Certificate of Good Moral', 'uploads/good_moral_sample.pdf', 'good_moral_sample.pdf', 'good_moral_sample.pdf', 98000, 'Verified', 'Signed by Principal.', 3, NOW()),
    (3, '2x2 ID Photo', 'uploads/photo_sample.jpg', 'photo_sample.jpg', 'photo_sample.jpg', 65000, 'Verified', 'Compliant photo.', 3, NOW());
");

// ─── SEED SCHOOL EVENTS CALENDAR ───
$pdo->exec("
    INSERT INTO school_events (school_year_id, title, description, event_category, start_date, end_date, start_time, end_time, location, target_audience, is_published, created_by) VALUES
    (1, 'Brigada Eskwela & School Readiness Week', 'Community clean-up, classroom preparation, and institutional safety inspection before opening of classes.', 'Institutional', '2026-08-17', '2026-08-21', '08:00:00', '16:00:00', 'Campus Grounds & All Facilities', 'All', 1, 2),
    (1, 'Official Opening of Classes / Balik Eskwela S.Y. 2026-2027', 'First official day of classes, school orientation, student handbook distribution, and morning assembly.', 'Academic', '2026-08-24', '2026-08-24', '07:30:00', '15:00:00', 'Main Gymnasium & Respective Classrooms', 'All', 1, 2),
    (1, 'Pagdiriwang ng Buwan ng Wikang Pambansa 2026', 'Culminating program featuring Balagtasan, Sabayang Pagbigkas, Katutubong Sayaw, and Filipino literature exhibitions.', 'Cultural', '2026-08-28', '2026-08-28', '08:30:00', '16:30:00', 'Main Gymnasium', 'All', 1, 2),
    (1, 'National Heroes Day (Araw ng mga Bayani)', 'Regular national holiday commemorating Philippine national heroes.', 'Holiday', '2026-08-31', '2026-08-31', NULL, NULL, 'Nationwide (No Classes)', 'All', 1, 2),
    (1, 'National Literacy Day & DepEd Reading Month Kick-off', 'Interactive book fair, reading comprehension contests, and English/Filipino literacy workshop for Junior High.', 'Academic', '2026-09-08', '2026-09-08', '09:00:00', '15:00:00', 'School Learning Resource Center / Library', 'Junior High School', 1, 2),
    (1, 'SHS Career Guidance & College Readiness Seminar', 'Career counseling, university admissions overview, CHED scholarship orientations, and industry speaker sessions.', 'Academic', '2026-09-18', '2026-09-18', '08:30:00', '16:00:00', 'Audio-Visual Center / Auditorium', 'Senior High School', 1, 2),
    (1, 'Annual General Parents-Teachers Association (GPTA) Assembly', 'Election of GPTA Executive Board, school development plans presentation, and classroom officer elections.', 'Institutional', '2026-09-25', '2026-09-25', '13:00:00', '17:00:00', 'Main Gymnasium', 'Parents & Guardians', 1, 2),
    (1, 'World Teachers\' Day & Faculty Appreciation Gala', 'Institutional tribute honoring faculty excellence, teaching awards, and student council performances.', 'Institutional', '2026-10-05', '2026-10-05', '08:00:00', '15:00:00', 'School Quadrangle & Auditorium', 'All', 1, 2),
    (1, 'First Quarter Periodic Examinations (JHS & SHS)', 'Official Quarter 1 unified examination across all core, applied, and specialized subjects.', 'Academic', '2026-10-22', '2026-10-23', '07:30:00', '16:00:00', 'All Section Classrooms', 'All', 1, 2),
    (1, 'Mid-Year DepEd Semestral Break & In-Service Training (INSET)', 'Faculty pedagogy enhancement and curriculum review. Mid-year wellness break for students.', 'Academic', '2026-10-26', '2026-10-30', '08:00:00', '17:00:00', 'Faculty Conference Hall', 'Faculty & Staff', 1, 2),
    (1, 'Resumption of Classes (Start of 2nd Quarter)', 'Classes resume for all Junior and Senior High School grade levels.', 'Academic', '2026-11-02', '2026-11-02', '07:30:00', '16:00:00', 'Campus Classrooms', 'All', 1, 2),
    (1, 'National Science & Tech Week / STEM Innovation Expo', 'Science investigatory project exhibits, robotics showdown, and mathematics quiz bee.', 'Academic', '2026-11-13', '2026-11-13', '08:00:00', '17:00:00', 'Science Wing & Computer Labs', 'All', 1, 2),
    (1, '1st Quarter Report Card Distribution (Card Giving Day)', 'Distribution of SF9 Form 138 report cards and parent-teacher consultations.', 'Academic', '2026-11-20', '2026-11-20', '08:00:00', '12:00:00', 'Respective Classrooms', 'Parents & Guardians', 1, 2),
    (1, 'Bonifacio Day (National Regular Holiday)', 'Commemoration of the birth of Gat Andres Bonifacio.', 'Holiday', '2026-11-30', '2026-11-30', NULL, NULL, 'Nationwide (No Classes)', 'All', 1, 2),
    (1, 'Second Quarter Periodic Examinations (End of 1st Sem)', 'Official 2nd Quarter and 1st Semester Final Examinations for all Grade Levels.', 'Academic', '2026-12-10', '2026-12-11', '07:30:00', '16:00:00', 'All Section Classrooms', 'All', 1, 2),
    (1, 'Annual Christmas Festival & Community Year-End Gala', 'Choral competitions, parol-making exhibition, and institutional gift-giving drive.', 'Cultural', '2026-12-18', '2026-12-18', '09:00:00', '16:00:00', 'Main Gymnasium', 'All', 1, 2),
    (1, 'DepEd Christmas & New Year Holiday Vacation Break', 'Official DepEd vacation break for all learners, faculty, and administrative personnel.', 'Holiday', '2026-12-19', '2027-01-03', NULL, NULL, 'Nationwide', 'All', 1, 2),
    (1, 'Resumption of Classes & Official Start of 2nd Semester', 'New semester commences for Senior High School strands and Quarter 3 for Junior High.', 'Academic', '2027-01-04', '2027-01-04', '07:30:00', '16:00:00', 'Campus Grounds', 'All', 1, 2),
    (1, '2nd Quarter Card Giving Day & Parent Consultation', 'Distribution of 1st Semester Final Ratings and remedial guidance consultations.', 'Academic', '2027-01-15', '2027-01-15', '08:00:00', '12:00:00', 'Respective Classrooms', 'Parents & Guardians', 1, 2),
    (1, 'Annual Sports Festival & Institutional Intramurals 2027', 'Cheerdance exhibition, track & field events, basketball, volleyball, badminton, and e-sports tournaments.', 'Sports', '2027-01-22', '2027-01-23', '07:00:00', '18:00:00', 'Sports Complex & Quadrangle', 'All', 1, 2),
    (1, 'National Arts Month Celebration & Creative Showcase', 'Visual arts gallery exhibition, theater presentations, musical recital, and culinary tasting booths.', 'Cultural', '2027-02-12', '2027-02-12', '09:00:00', '16:30:00', 'School Auditorium & Fine Arts Wing', 'All', 1, 2),
    (1, 'EDSA People Power Revolution Anniversary', 'Special non-working holiday celebrating Philippine democracy.', 'Holiday', '2027-02-25', '2027-02-25', NULL, NULL, 'Nationwide (No Classes)', 'All', 1, 2),
    (1, 'Third Quarter Periodic Examinations', 'Quarter 3 assessment for Junior High and 2nd Semester Midterm for Senior High.', 'Academic', '2027-03-11', '2027-03-12', '07:30:00', '16:00:00', 'All Classrooms', 'All', 1, 2),
    (1, 'SHS Research Congress & 3Is Capstone Defense', 'Public oral defense of Practical Research 2, 3Is innovations, and business feasibility projects before panel evaluators.', 'Academic', '2027-03-26', '2027-03-26', '08:00:00', '17:00:00', 'Audio-Visual Center & Conference Rooms', 'Senior High School', 1, 2),
    (1, 'Araw ng Kagitingan (Day of Valor)', 'Regular national holiday commemorating the Fall of Bataan and heroism of Filipino soldiers.', 'Holiday', '2027-04-09', '2027-04-09', NULL, NULL, 'Nationwide', 'All', 1, 2),
    (1, 'SHS TVL & Academic Track Work Immersion Culminating Conference', 'Presentation of industry internship portfolios, certificate of completion distribution, and partner company awards.', 'Academic', '2027-04-23', '2027-04-23', '08:30:00', '16:00:00', 'Main Auditorium', 'Senior High School', 1, 2),
    (1, 'Fourth Quarter Final Examinations (Graduating & Non-Graduating)', 'Final academic examinations concluding the school year.', 'Academic', '2027-05-13', '2027-05-14', '07:30:00', '16:00:00', 'All Classrooms', 'All', 1, 2),
    (1, 'Deliberation of Academic Honors & Special DepEd Awards', 'Academic Committee convening for graduation honors, leadership awards, and conduct rankings.', 'Institutional', '2027-05-21', '2027-05-21', '09:00:00', '15:00:00', 'Faculty Conference Hall', 'Faculty & Staff', 1, 2),
    (1, 'Junior High School Moving Up Ceremony (Grade 10 Completers)', 'Solemn moving up exercises for Junior High School completers transitioning to Senior High.', 'Institutional', '2027-05-27', '2027-05-27', '08:00:00', '12:00:00', 'Main Gymnasium', 'Junior High School', 1, 2),
    (1, 'Senior High School Commencement Exercises (Grade 12 Graduates)', 'Official graduation ceremony awarding High School Diplomas to Grade 12 STEM, ABM, HUMSS, GAS, TVL-ICT, and TVL-HE graduates.', 'Institutional', '2027-05-28', '2027-05-28', '14:00:00', '18:00:00', 'Main Gymnasium', 'Senior High School', 1, 2),
    (1, 'Annual Institutional Recognition Day (Grades 7, 8, 9, 11)', 'Conferment of academic excellence medals and subject proficiency awards for non-graduating students.', 'Academic', '2027-05-29', '2027-05-29', '08:30:00', '12:30:00', 'Main Gymnasium', 'All', 1, 2);
");

// ─── SEED AUDIT LOGS ───
$pdo->exec("
    INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) VALUES
    (1, 'SYSTEM_INIT', 'DepEd Junior & Senior High School Institutional Security System and Audit Engine initialized', '127.0.0.1', NOW() - INTERVAL 1 DAY),
    (10, 'APPLICANT_REGISTER', 'New applicant account registered: ADM-2026-0003 (demo.student@sia.edu.ph)', '127.0.0.1', NOW() - INTERVAL 6 HOUR),
    (1, 'ACTIVE_SCHOOL_YEAR_CHANGED', 'School Year 2026-2027 set as primary active academic year', '127.0.0.1', NOW() - INTERVAL 5 HOUR),
    (2, 'SECTION_CREATED', 'Created class section Grade 11 - STEM B (Room: Science Wing 502, Capacity: 45)', '127.0.0.1', NOW() - INTERVAL 4 HOUR),
    (2, 'CURRICULUM_LOCK_TOGGLED', 'School Year 2026-2027 curriculum was locked and published by maria_coordinator', '127.0.0.1', NOW() - INTERVAL 3 HOUR),
    (4, 'ONLINE_PAYMENT_VERIFIED', 'Verified E-Wallet GCash Downpayment OR-2026-000002 for Julian Alvarez (2026-SHS-0005)', '127.0.0.1', NOW() - INTERVAL 2 HOUR),
    (4, 'PAYMENT_PROCESSED', 'Processed Cash Downpayment OR-2026-000001 (PHP 15,200.00) for Student 2026-JHS-0001', '127.0.0.1', NOW() - INTERVAL 1 HOUR),
    (3, 'DOCUMENT_VERIFIED', 'Verified PSA Birth Certificate and SF9 Report Card for App #ADM-2026-0003', '127.0.0.1', NOW() - INTERVAL 30 MINUTE),
    (3, 'APPROVE_AND_QUEUE', 'Approved App #ADM-2026-0003 (Lynrd Rosales), Assigned Section: Grade 11 - STEM A, Queue #Q-001', '127.0.0.1', NOW() - INTERVAL 25 MINUTE),
    (1, 'LOGIN', 'Administrator admin authenticated from Super Admin Portal', '127.0.0.1', NOW() - INTERVAL 10 MINUTE);
");

echo "=== All Tables & Seed Data Installed Successfully! ===\n";

