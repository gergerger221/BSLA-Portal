-- SIA High School Enrollment Management System Database Dump
-- Generated: 2026-08-29 13:35:22
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
('1', 'Super Administrator', 'admin', 'Institutional system administration and controls', '2026-08-25 06:44:16'),
('2', 'Academic Coordinator', 'coordinator', 'Curriculum, tracks, strands, and master section scheduling', '2026-08-25 06:44:16'),
('3', 'Registrar', 'registrar', 'Admission evaluation, credential verification, and section seating queue', '2026-08-25 06:44:16'),
('4', 'Treasury / Cashier', 'treasury', 'Tuition assessment, cashiering, and official receipt issuing', '2026-08-25 06:44:16'),
('5', 'School Records Custodian', 'records', 'DepEd SF1, SF5, SF9, SF10, honors, and DRS requests', '2026-08-25 06:44:16'),
('6', 'Teacher / Faculty', 'teacher', 'Class instruction, grade encoding, and attendance', '2026-08-25 06:44:16'),
('7', 'Enrolled Student', 'student', 'Student portal access, timetable, SOA, and event calendar', '2026-08-25 06:44:16'),
('8', 'Admission Applicant', 'applicant', 'Temporary application submission and status tracking', '2026-08-25 06:44:16');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `student_id`, `password`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
('1', '1', 'admin', 'admin@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:49:16'),
('2', '2', 'maria_coordinator', 'coordinator@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', '46adc2703942e9d2422608f7fd7380d989acea9b2fbdd2a860fd26fa18e5346d', '2026-08-25 06:44:17', '2026-08-25 06:49:18'),
('3', '3', 'maria_registrar', 'registrar@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('4', '4', 'maria_treasury', 'treasury@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('5', '5', 'maria_records', 'records@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('6', '6', 'prof_delacruz', 'delacruz@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('7', '6', 'prof_santos', 'santos@sia.edu.ph', NULL, '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('8', '7', '2026-JHS-0001', 'student1@sia.edu.ph', '2026-JHS-0001', '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('9', '7', '2026-SHS-0005', 'student2@sia.edu.ph', '2026-SHS-0005', '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('10', '7', 'student2026', 'demo.student@sia.edu.ph', 'STUD-2026-0001', '$2y$10$dGF5RXAkYWt5JK..GQOB6.IssNH2F1XhZMDdl7YikvM78HGtJC0iS', 'Active', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('11', '6', 'prof_tan', 'tan@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('12', '6', 'prof_reyes', 'reyes@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('13', '6', 'prof_luna', 'luna@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('14', '6', 'prof_silang', 'silang@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('15', '6', 'prof_aquino', 'aquino@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('16', '6', 'prof_sy', 'sy@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('17', '6', 'prof_gokongwei', 'gokongwei@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('18', '6', 'prof_turing', 'turing@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('19', '6', 'prof_lovelace', 'lovelace@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('20', '6', 'prof_daza', 'daza@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('21', '6', 'prof_gonzales', 'gonzales@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('22', '6', 'prof_pacquiao', 'pacquiao@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('23', '6', 'prof_diaz', 'diaz@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('24', '6', 'prof_obiena', 'obiena@sia.edu.ph', NULL, '$2y$10$INpzTMyEdUzYrTcitFDV9elAwvrWkrNRngZGcoQurjBXyvVcupHoW', 'Active', NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('25', '6', 'prof_mendoza', 'r.mendoza@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('26', '6', 'prof_ramos', 'c.ramos@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('27', '6', 'prof_valdez', 'a.valdez@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('28', '6', 'prof_castro', 'f.castro@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('29', '6', 'prof_bautista', 'c.bautista@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('30', '6', 'prof_villanueva', 'j.villanueva@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('31', '6', 'prof_navarro', 'k.navarro@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('32', '6', 'prof_garcia', 'r.garcia@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('33', '6', 'prof_morales', 'd.morales@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('34', '6', 'prof_alvarez', 'b.alvarez@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('35', '6', 'prof_salazar', 't.salazar@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('36', '6', 'prof_pascual', 'e.pascual@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('37', '6', 'prof_torres', 'g.torres@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('38', '6', 'prof_mercado', 'r.mercado@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('39', '6', 'prof_lim', 'l.lim@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('40', '6', 'prof_cruz', 'd.cruz@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('41', '6', 'prof_flores', 'm.flores@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('42', '6', 'prof_sison', 'h.sison@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('43', '6', 'prof_velasco', 'l.velasco@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('44', '6', 'prof_soriano', 'r.soriano@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('45', '6', 'prof_aguilar', 'r.aguilar@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('46', '6', 'prof_padilla', 'v.padilla@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('47', '6', 'prof_gutierrez', 'e.gutierrez@sia.edu.ph', NULL, '$2y$10$YNUadui8Is3YWt2/yKRk2OVGNjFxK/XySoQeuH.OtCHG3CvRu9r1a', 'Active', NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('48', '8', 'test_applicant', 'applicant@test.com', NULL, '$2y$10$r6xadvXt6BUHyUQuxp.VYeAhnn9aUjcFeF1pjiAruEpAsCNeU4d1i', 'Active', NULL, '2026-08-29 19:33:28', '2026-08-29 19:33:50'),
('49', '8', 'testuser_1788003234', 'workflow_tester_1788003234@sia.edu.ph', NULL, '$2y$10$SnZradBrzvQDqSg8Ym3qceLmfBytZvBa.4aQY1qB410MVlMoTtKxC', 'Active', NULL, '2026-08-29 19:33:54', '2026-08-29 19:33:54'),
('50', '8', 'testuser_1788003317', 'workflow_tester_1788003317@sia.edu.ph', NULL, '$2y$10$zm2/6isA9JUspduSB5msd.bCW6kP5pbI5KYqXOCL4Cc99wUXyDsFu', 'Active', NULL, '2026-08-29 19:35:17', '2026-08-29 19:35:17'),
('51', '7', '2026-SHS-9029', '2026shs9029@student.sia.edu.ph', '2026-SHS-9029', '$2y$10$nclcHlT/7RkRb6d4pZIwpuP/Drb3OD2rvPQnWebqCHc8XCSHkXceC', 'Active', NULL, '2026-08-29 19:35:18', '2026-08-29 19:35:18');

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT 'Male',
  `birthdate` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `contact_number`, `address`, `avatar_url`, `created_at`, `updated_at`) VALUES
('1', '1', 'System', 'Admin', 'Officer', NULL, 'Male', NULL, '09171110001', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('2', '2', 'Maria', 'Clara', 'Coordinator', NULL, 'Male', NULL, '09171110002', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('3', '3', 'Maria', 'Consuelo', 'Registrar', NULL, 'Male', NULL, '09171110003', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('4', '4', 'Maria', 'Theresa', 'Treasury', NULL, 'Male', NULL, '09171110004', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('5', '5', 'Maria', 'Lourdes', 'Records', NULL, 'Male', NULL, '09171110005', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('8', '8', 'Cristiano', 'Aveiro', 'Ronaldo', NULL, 'Male', NULL, '09171234567', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('9', '9', 'Julian', 'Alvarez', 'Alvares', NULL, 'Male', NULL, '09177654321', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('10', '10', 'Lynrd', 'Santos', 'Rosales', NULL, 'Male', NULL, '09179998888', NULL, NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('11', '6', 'Juan', NULL, 'Dela Cruz', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('12', '7', 'Maria Elena', NULL, 'Santos', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('13', '11', 'Albert', NULL, 'Tan', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('14', '12', 'Nicole', NULL, 'Reyes', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('15', '13', 'Antonio', NULL, 'Luna', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('16', '14', 'Gabriela', NULL, 'Silang', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('17', '15', 'Melchora', NULL, 'Aquino', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('18', '16', 'Warren', NULL, 'Sy', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('19', '17', 'Grace', NULL, 'Gokongwei', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('20', '18', 'Alan', NULL, 'Turing', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('21', '19', 'Ada', NULL, 'Lovelace', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('22', '20', 'Nora', NULL, 'Daza', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('23', '21', 'Gene', NULL, 'Gonzales', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('24', '22', 'Emmanuel', NULL, 'Pacquiao', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('25', '23', 'Hidilyn', NULL, 'Diaz', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('26', '24', 'Ernest', NULL, 'Obiena', NULL, 'Male', NULL, '09170000000', NULL, NULL, '2026-08-25 06:57:19', '2026-08-25 06:57:19'),
('27', '25', 'Ricardo', NULL, 'Mendoza', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('28', '26', 'Clarissa', NULL, 'Ramos', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('29', '27', 'Alyssa', NULL, 'Valdez', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('30', '28', 'Ferdinand', NULL, 'Castro', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('31', '29', 'Corazon', NULL, 'Bautista', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('32', '30', 'Joshua', NULL, 'Villanueva', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('33', '31', 'Katrina', NULL, 'Navarro', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('34', '32', 'Rosario', NULL, 'Garcia', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('35', '33', 'Danilo', NULL, 'Morales', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('36', '34', 'Benjamin', NULL, 'Alvarez', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('37', '35', 'Teresa', NULL, 'Salazar', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('38', '36', 'Emilio', NULL, 'Pascual', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('39', '37', 'Giselle', NULL, 'Torres', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('40', '38', 'Ramon', NULL, 'Mercado', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('41', '39', 'Lucille', NULL, 'Lim', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('42', '40', 'Dexter', NULL, 'Cruz', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('43', '41', 'Maricris', NULL, 'Flores', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('44', '42', 'Henrico', NULL, 'Sison', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('45', '43', 'Lourdes', NULL, 'Velasco', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('46', '44', 'Roderick', NULL, 'Soriano', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('47', '45', 'Rowena', NULL, 'Aguilar', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('48', '46', 'Vicente', NULL, 'Padilla', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('49', '47', 'Elena', NULL, 'Gutierrez', NULL, '', NULL, '09170000000', NULL, NULL, '2026-08-25 07:22:14', '2026-08-25 07:22:14'),
('50', '49', 'Maria', 'Clara', 'Ibarra', NULL, 'Male', NULL, '09181234567', NULL, NULL, '2026-08-29 19:33:54', '2026-08-29 19:33:54'),
('51', '50', 'Maria', 'Clara', 'Ibarra', NULL, 'Male', NULL, '09181234567', NULL, NULL, '2026-08-29 19:35:17', '2026-08-29 19:35:17'),
('52', '51', 'Maria', 'Clara', 'Ibarra', NULL, 'Male', NULL, '09181234567', NULL, NULL, '2026-08-29 19:35:18', '2026-08-29 19:35:18');

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
('1', '1', 'SYSTEM_INIT', 'DepEd Junior & Senior High School Institutional Security System and Audit Engine initialized', '127.0.0.1', '2026-08-24 06:44:17'),
('2', '10', 'APPLICANT_REGISTER', 'New applicant account registered: ADM-2026-0003 (demo.student@sia.edu.ph)', '127.0.0.1', '2026-08-25 00:44:17'),
('3', '1', 'ACTIVE_SCHOOL_YEAR_CHANGED', 'School Year 2026-2027 set as primary active academic year', '127.0.0.1', '2026-08-25 01:44:17'),
('4', '2', 'SECTION_CREATED', 'Created class section Grade 11 - STEM B (Room: Science Wing 502, Capacity: 45)', '127.0.0.1', '2026-08-25 02:44:17'),
('5', '2', 'CURRICULUM_LOCK_TOGGLED', 'School Year 2026-2027 curriculum was locked and published by maria_coordinator', '127.0.0.1', '2026-08-25 03:44:17'),
('6', '4', 'ONLINE_PAYMENT_VERIFIED', 'Verified E-Wallet GCash Downpayment OR-2026-000002 for Julian Alvarez (2026-SHS-0005)', '127.0.0.1', '2026-08-25 04:44:17'),
('7', '4', 'PAYMENT_PROCESSED', 'Processed Cash Downpayment OR-2026-000001 (PHP 15,200.00) for Student 2026-JHS-0001', '127.0.0.1', '2026-08-25 05:44:17'),
('8', '3', 'DOCUMENT_VERIFIED', 'Verified PSA Birth Certificate and SF9 Report Card for App #ADM-2026-0003', '127.0.0.1', '2026-08-25 06:14:17'),
('9', '3', 'APPROVE_AND_QUEUE', 'Approved App #ADM-2026-0003 (Lynrd Rosales), Assigned Section: Grade 11 - STEM A, Queue #Q-001', '127.0.0.1', '2026-08-25 06:19:17'),
('10', '1', 'LOGIN', 'Administrator admin authenticated from Super Admin Portal', '127.0.0.1', '2026-08-25 06:34:17');

DROP TABLE IF EXISTS `school_years`;
CREATE TABLE `school_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active_semester` varchar(50) DEFAULT '1st Semester',
  `is_active` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `curriculum_locked` tinyint(1) DEFAULT 0,
  `curriculum_declared_at` timestamp NULL DEFAULT NULL,
  `curriculum_declared_by` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_years` (`id`, `code`, `name`, `active_semester`, `is_active`, `is_locked`, `curriculum_locked`, `curriculum_declared_at`, `curriculum_declared_by`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
('1', '2026-2027', 'School Year 2026-2027', '1st Semester', '1', '0', '1', '2026-08-25 01:46:34', '2', '2026-08-15', '2027-05-30', '2026-08-25 06:44:16', '2026-08-25 07:46:34');

DROP TABLE IF EXISTS `tracks`;
CREATE TABLE `tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tracks` (`id`, `code`, `name`, `description`, `is_active`, `status`, `created_at`) VALUES
('1', 'ACAD', 'Academic Track', 'College preparatory academic strands', '1', 'Active', '2026-08-25 06:44:16'),
('2', 'TVL', 'Technical-Vocational-Livelihood Track', 'Practical job-ready technical skills specialization', '1', 'Active', '2026-08-25 06:44:16');

DROP TABLE IF EXISTS `strands`;
CREATE TABLE `strands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `track_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `strands` (`id`, `track_id`, `code`, `name`, `description`, `is_active`, `status`, `created_at`) VALUES
('1', '1', 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Advanced sciences, calculus, physics, and research', '1', 'Active', '2026-08-25 06:44:16'),
('2', '1', 'ABM', 'Accountancy, Business, and Management', 'Business finance, marketing, economics, and entrepreneurship', '1', 'Active', '2026-08-25 06:44:16'),
('3', '1', 'HUMSS', 'Humanities and Social Sciences', 'Literature, political science, creative writing, and sociology', '1', 'Active', '2026-08-25 06:44:16'),
('4', '1', 'GAS', 'General Academic Strand', 'Comprehensive multidisciplinary academic electives', '1', 'Active', '2026-08-25 06:44:16'),
('5', '2', 'TVL-ICT', 'Information and Communications Technology', 'Programming, web development, networking, and system servicing', '1', 'Active', '2026-08-25 06:44:16'),
('6', '2', 'TVL-HE', 'Home Economics', 'Commercial cooking, bread & pastry, and tourism hospitality', '1', 'Active', '2026-08-25 06:44:16');

DROP TABLE IF EXISTS `grade_levels`;
CREATE TABLE `grade_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `category` enum('JHS','SHS') NOT NULL,
  `sequence_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `grade_levels` (`id`, `name`, `code`, `category`, `sequence_order`, `created_at`) VALUES
('1', 'Grade 7', 'G7', 'JHS', '1', '2026-08-25 06:44:16'),
('2', 'Grade 8', 'G8', 'JHS', '2', '2026-08-25 06:44:16'),
('3', 'Grade 9', 'G9', 'JHS', '3', '2026-08-25 06:44:16'),
('4', 'Grade 10', 'G10', 'JHS', '4', '2026-08-25 06:44:16'),
('5', 'Grade 11', 'G11', 'SHS', '5', '2026-08-25 06:44:16'),
('6', 'Grade 12', 'G12', 'SHS', '6', '2026-08-25 06:44:16');

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level_id` int(11) NOT NULL,
  `strand_id` int(11) DEFAULT NULL,
  `prerequisite_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT 'Core',
  `classification` enum('Core','Applied','Specialized','Institutional') DEFAULT 'Core',
  `semester` varchar(30) DEFAULT '1st Semester',
  `lecture_hours` decimal(4,1) DEFAULT 4.0,
  `lab_hours` decimal(4,1) DEFAULT 0.0,
  `units` decimal(4,1) DEFAULT 4.0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subjects` (`id`, `grade_level_id`, `strand_id`, `prerequisite_id`, `code`, `name`, `title`, `category`, `classification`, `semester`, `lecture_hours`, `lab_hours`, `units`, `is_active`, `created_at`) VALUES
('1', '1', NULL, NULL, 'ENG-7', 'English 7 - Communication Arts', 'English 7 - Communication Arts', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('2', '1', NULL, NULL, 'MATH-7', 'Mathematics 7 - Elementary Algebra & Geometry', 'Mathematics 7 - Elementary Algebra & Geometry', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('3', '1', NULL, NULL, 'SCI-7', 'Science 7 - Integrated Science', 'Science 7 - Integrated Science', 'Core', 'Core', 'Full Year', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('4', '1', NULL, NULL, 'FIL-7', 'Filipino 7 - Ibong Adarna at Panitikang Rehiyunal', 'Filipino 7 - Ibong Adarna at Panitikang Rehiyunal', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('5', '1', NULL, NULL, 'AP-7', 'Araling Panlipunan 7 - Araling Asyano', 'Araling Panlipunan 7 - Araling Asyano', 'Core', 'Core', 'Full Year', '3.0', '0.0', '3.0', '1', '2026-08-25 06:44:17'),
('6', '1', NULL, NULL, 'ESP-7', 'Edukasyon sa Pagpapakatao 7 - Pagpapahalaga at Birtud', 'Edukasyon sa Pagpapakatao 7 - Pagpapahalaga at Birtud', 'Core', 'Core', 'Full Year', '2.0', '0.0', '2.0', '1', '2026-08-25 06:44:17'),
('7', '1', NULL, NULL, 'TLE-7', 'Technology and Livelihood Education 7 (Exploratory)', 'Technology and Livelihood Education 7 (Exploratory)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '3.0', '1', '2026-08-25 06:44:17'),
('8', '1', NULL, NULL, 'MAPEH-7', 'MAPEH 7 (Music, Arts, Physical Education, Health)', 'MAPEH 7 (Music, Arts, Physical Education, Health)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('9', '2', NULL, '1', 'ENG-8', 'English 8 - Afro-Asian Literature', 'English 8 - Afro-Asian Literature', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('10', '2', NULL, '2', 'MATH-8', 'Mathematics 8 - Intermediate Algebra & Logic', 'Mathematics 8 - Intermediate Algebra & Logic', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('11', '2', NULL, '3', 'SCI-8', 'Science 8 - Biology & Ecology', 'Science 8 - Biology & Ecology', 'Core', 'Core', 'Full Year', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('12', '2', NULL, '4', 'FIL-8', 'Filipino 8 - Florante at Laura at Panitikang Pambansa', 'Filipino 8 - Florante at Laura at Panitikang Pambansa', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('13', '2', NULL, '5', 'AP-8', 'Araling Panlipunan 8 - Kasaysayan ng Daigdig', 'Araling Panlipunan 8 - Kasaysayan ng Daigdig', 'Core', 'Core', 'Full Year', '3.0', '0.0', '3.0', '1', '2026-08-25 06:44:17'),
('14', '2', NULL, '6', 'ESP-8', 'Edukasyon sa Pagpapakatao 8 - Pakikipagkapwa-tao', 'Edukasyon sa Pagpapakatao 8 - Pakikipagkapwa-tao', 'Core', 'Core', 'Full Year', '2.0', '0.0', '2.0', '1', '2026-08-25 06:44:17'),
('15', '2', NULL, '7', 'TLE-8', 'Technology and Livelihood Education 8 (Exploratory TLE)', 'Technology and Livelihood Education 8 (Exploratory TLE)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '3.0', '1', '2026-08-25 06:44:17'),
('16', '2', NULL, '8', 'MAPEH-8', 'MAPEH 8 (Asian Music, Arts, PE, Health)', 'MAPEH 8 (Asian Music, Arts, PE, Health)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('17', '3', NULL, '9', 'ENG-9', 'English 9 - Anglo-American Literature', 'English 9 - Anglo-American Literature', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('18', '3', NULL, '10', 'MATH-9', 'Mathematics 9 - Advanced Algebra & Trigonometry', 'Mathematics 9 - Advanced Algebra & Trigonometry', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('19', '3', NULL, '11', 'SCI-9', 'Science 9 - Chemistry & Matter', 'Science 9 - Chemistry & Matter', 'Core', 'Core', 'Full Year', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('20', '3', NULL, '12', 'FIL-9', 'Filipino 9 - Noli Me Tangere at Panitikang Asyano', 'Filipino 9 - Noli Me Tangere at Panitikang Asyano', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('21', '3', NULL, '13', 'AP-9', 'Araling Panlipunan 9 - Ekonomiks', 'Araling Panlipunan 9 - Ekonomiks', 'Core', 'Core', 'Full Year', '3.0', '0.0', '3.0', '1', '2026-08-25 06:44:17'),
('22', '3', NULL, '14', 'ESP-9', 'Edukasyon sa Pagpapakatao 9 - Lipunang Sibil at Katarungan', 'Edukasyon sa Pagpapakatao 9 - Lipunang Sibil at Katarungan', 'Core', 'Core', 'Full Year', '2.0', '0.0', '2.0', '1', '2026-08-25 06:44:17'),
('23', '3', NULL, '15', 'TLE-9', 'Technology and Livelihood Education 9 (Specialized Tech)', 'Technology and Livelihood Education 9 (Specialized Tech)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '3.0', '1', '2026-08-25 06:44:17'),
('24', '3', NULL, '16', 'MAPEH-9', 'MAPEH 9 (Western Music, Arts, PE, Health)', 'MAPEH 9 (Western Music, Arts, PE, Health)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('25', '4', NULL, '17', 'ENG-10', 'English 10 - World Literature & Public Speaking', 'English 10 - World Literature & Public Speaking', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('26', '4', NULL, '18', 'MATH-10', 'Mathematics 10 - Sequences, Polynomials, Probability', 'Mathematics 10 - Sequences, Polynomials, Probability', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('27', '4', NULL, '19', 'SCI-10', 'Science 10 - Physics & Earth Space', 'Science 10 - Physics & Earth Space', 'Core', 'Core', 'Full Year', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('28', '4', NULL, '20', 'FIL-10', 'Filipino 10 - El Filibusterismo at Panitikang Pandaigdig', 'Filipino 10 - El Filibusterismo at Panitikang Pandaigdig', 'Core', 'Core', 'Full Year', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('29', '4', NULL, '21', 'AP-10', 'Araling Panlipunan 10 - Mga Kontemporaryong Isyu', 'Araling Panlipunan 10 - Mga Kontemporaryong Isyu', 'Core', 'Core', 'Full Year', '3.0', '0.0', '3.0', '1', '2026-08-25 06:44:17'),
('30', '4', NULL, '22', 'ESP-10', 'Edukasyon sa Pagpapakatao 10 - Moral na Pagpapasiya', 'Edukasyon sa Pagpapakatao 10 - Moral na Pagpapasiya', 'Core', 'Core', 'Full Year', '2.0', '0.0', '2.0', '1', '2026-08-25 06:44:17'),
('31', '4', NULL, '23', 'TLE-10', 'Technology and Livelihood Education 10 (NC Prep)', 'Technology and Livelihood Education 10 (NC Prep)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '3.0', '1', '2026-08-25 06:44:17'),
('32', '4', NULL, '24', 'MAPEH-10', 'MAPEH 10 (Contemporary Music, Arts, PE, Health)', 'MAPEH 10 (Contemporary Music, Arts, PE, Health)', 'Core', 'Core', 'Full Year', '2.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('33', '5', NULL, NULL, 'ORAL-COM', 'Oral Communication in Context', 'Oral Communication in Context', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('34', '5', NULL, NULL, 'KOM-PAN', 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('35', '5', NULL, NULL, 'GEN-MATH', 'General Mathematics', 'General Mathematics', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('36', '5', NULL, NULL, 'EARTH-LIFE', 'Earth and Life Science', 'Earth and Life Science', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('37', '5', NULL, NULL, 'PER-DEV', 'Personal Development / Pansariling Kaunlaran', 'Personal Development / Pansariling Kaunlaran', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('38', '5', NULL, NULL, 'PE-1', 'Physical Education and Health 1 (Exercise & Fitness)', 'Physical Education and Health 1 (Exercise & Fitness)', 'Core', 'Core', '1st Semester', '1.0', '1.0', '2.0', '1', '2026-08-25 06:44:17'),
('39', '5', NULL, NULL, 'EAPP', 'English for Academic and Professional Purposes', 'English for Academic and Professional Purposes', 'Applied', 'Applied', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('40', '5', NULL, '33', 'READ-WRITE', 'Reading and Writing Skills', 'Reading and Writing Skills', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('41', '5', NULL, '34', 'PAGBASA-PAGSUSURI', 'Pagbasa at Pagsusuri ng Iba\'t Ibang Teksto Tungo sa Pananaliksik', 'Pagbasa at Pagsusuri ng Iba\'t Ibang Teksto Tungo sa Pananaliksik', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('42', '5', NULL, '35', 'STAT-PROB', 'Statistics and Probability', 'Statistics and Probability', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('43', '5', NULL, '36', 'PHYS-SCI', 'Physical Science', 'Physical Science', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('44', '5', NULL, NULL, 'UCSP', 'Understanding Culture, Society, and Politics', 'Understanding Culture, Society, and Politics', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('45', '5', NULL, '38', 'PE-2', 'Physical Education and Health 2 (Individual & Dual Sports)', 'Physical Education and Health 2 (Individual & Dual Sports)', 'Core', 'Core', '2nd Semester', '1.0', '1.0', '2.0', '1', '2026-08-25 06:44:17'),
('46', '5', NULL, NULL, 'PRAC-RES1', 'Practical Research 1 (Qualitative Research)', 'Practical Research 1 (Qualitative Research)', 'Applied', 'Applied', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('47', '5', NULL, NULL, 'EMP-TECH', 'Empowerment Technologies (ICT for Professional Tracks)', 'Empowerment Technologies (ICT for Professional Tracks)', 'Applied', 'Applied', '2nd Semester', '2.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('48', '6', NULL, NULL, 'PHIL-PERSON', 'Introduction to the Philosophy of the Human Person', 'Introduction to the Philosophy of the Human Person', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('49', '6', NULL, NULL, '21ST-LIT', '21st Century Literature from the Philippines and the World', '21st Century Literature from the Philippines and the World', 'Core', 'Core', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('50', '6', NULL, NULL, 'MIL', 'Media and Information Literacy (MIL)', 'Media and Information Literacy (MIL)', 'Core', 'Core', '1st Semester', '3.0', '1.0', '4.0', '1', '2026-08-25 06:44:17'),
('51', '6', NULL, '45', 'PE-3', 'Physical Education and Health 3 (Dance & Rhythmic)', 'Physical Education and Health 3 (Dance & Rhythmic)', 'Core', 'Core', '1st Semester', '1.0', '1.0', '2.0', '1', '2026-08-25 06:44:17'),
('52', '6', NULL, '42', 'PRAC-RES2', 'Practical Research 2 (Quantitative Research)', 'Practical Research 2 (Quantitative Research)', 'Applied', 'Applied', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('53', '6', NULL, NULL, 'ENTREP', 'Entrepreneurship & Business Planning', 'Entrepreneurship & Business Planning', 'Applied', 'Applied', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('54', '6', NULL, '41', 'FIL-PILING', 'Filipino sa Piling Larang (Akademik/Tech-Voc)', 'Filipino sa Piling Larang (Akademik/Tech-Voc)', 'Applied', 'Applied', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('55', '6', NULL, NULL, 'CONTEMP-ARTS', 'Contemporary Philippine Arts from the Regions', 'Contemporary Philippine Arts from the Regions', 'Core', 'Core', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('56', '6', NULL, '51', 'PE-4', 'Physical Education and Health 4 (Recreational Activities)', 'Physical Education and Health 4 (Recreational Activities)', 'Core', 'Core', '2nd Semester', '1.0', '1.0', '2.0', '1', '2026-08-25 06:44:17'),
('57', '6', NULL, '52', '3IS', 'Inquiries, Investigations, and Immersion (3Is)', 'Inquiries, Investigations, and Immersion (3Is)', 'Applied', 'Applied', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('58', '5', '1', NULL, 'PRE-CALC', 'Pre-Calculus', 'Pre-Calculus', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('59', '5', '1', NULL, 'GEN-BIO1', 'General Biology 1', 'General Biology 1', 'Specialized', 'Specialized', '1st Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('60', '5', '1', '58', 'BASIC-CALC', 'Basic Calculus', 'Basic Calculus', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('61', '5', '1', '59', 'GEN-BIO2', 'General Biology 2', 'General Biology 2', 'Specialized', 'Specialized', '2nd Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('62', '6', '1', '60', 'GEN-PHYS1', 'General Physics 1', 'General Physics 1', 'Specialized', 'Specialized', '1st Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('63', '6', '1', NULL, 'GEN-CHEM1', 'General Chemistry 1', 'General Chemistry 1', 'Specialized', 'Specialized', '1st Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('64', '6', '1', '62', 'GEN-PHYS2', 'General Physics 2', 'General Physics 2', 'Specialized', 'Specialized', '2nd Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('65', '6', '1', '63', 'GEN-CHEM2', 'General Chemistry 2', 'General Chemistry 2', 'Specialized', 'Specialized', '2nd Semester', '3.0', '2.0', '4.0', '1', '2026-08-25 06:44:17'),
('66', '6', '1', '57', 'STEM-CAPSTONE', 'Research Capstone / STEM Culminating Activity', 'Research Capstone / STEM Culminating Activity', 'Specialized', 'Specialized', '2nd Semester', '1.0', '3.0', '4.0', '1', '2026-08-25 06:44:17'),
('67', '5', '2', NULL, 'BUS-MATH', 'Business Mathematics', 'Business Mathematics', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('68', '5', '2', NULL, 'ORG-MGMT', 'Organization and Management', 'Organization and Management', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('69', '5', '2', '67', 'FABM-1', 'Fundamentals of Accountancy, Business, and Management 1', 'Fundamentals of Accountancy, Business, and Management 1', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('70', '6', '2', '68', 'PRIN-MKTG', 'Principles of Marketing', 'Principles of Marketing', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('71', '6', '2', '69', 'FABM-2', 'Fundamentals of Accountancy, Business, and Management 2', 'Fundamentals of Accountancy, Business, and Management 2', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('72', '6', '2', NULL, 'APP-ECON', 'Applied Economics in Philippine Setting', 'Applied Economics in Philippine Setting', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('73', '6', '2', '71', 'BUS-FIN', 'Business Finance', 'Business Finance', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('74', '6', '2', NULL, 'BUS-ETHICS', 'Business Ethics and Social Responsibility', 'Business Ethics and Social Responsibility', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('75', '6', '2', '71', 'ABM-SIMULATION', 'Business Enterprise Simulation / ABM Immersion', 'Business Enterprise Simulation / ABM Immersion', 'Specialized', 'Specialized', '2nd Semester', '1.0', '3.0', '4.0', '1', '2026-08-25 06:44:17'),
('76', '5', '3', NULL, 'CREATIVE-WRITE', 'Creative Writing / Malikhaing Pagsulat', 'Creative Writing / Malikhaing Pagsulat', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('77', '5', '3', NULL, 'DISS', 'Disciplines and Ideas in the Social Sciences', 'Disciplines and Ideas in the Social Sciences', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('78', '5', '3', '76', 'CREATIVE-NONFIC', 'Creative Nonfiction: The Literary Essay', 'Creative Nonfiction: The Literary Essay', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('79', '5', '3', '77', 'DIASS', 'Disciplines and Ideas in the Applied Social Sciences', 'Disciplines and Ideas in the Applied Social Sciences', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('80', '6', '3', '77', 'PH-POLITICS', 'Philippine Politics and Governance', 'Philippine Politics and Governance', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('81', '6', '3', NULL, 'TRENDS-NETWORKS', 'Trends, Networks, and Critical Thinking in the 21st Century', 'Trends, Networks, and Critical Thinking in the 21st Century', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('82', '6', '3', '80', 'CESC', 'Community Engagement, Solidarity, and Citizenship', 'Community Engagement, Solidarity, and Citizenship', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('83', '6', '3', NULL, 'WORLD-RELIGIONS', 'Introduction to World Religions and Belief Systems', 'Introduction to World Religions and Belief Systems', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('84', '6', '3', '82', 'HUMSS-CULMINATING', 'HUMSS Culminating Activity & Advocacy Portfolio', 'HUMSS Culminating Activity & Advocacy Portfolio', 'Specialized', 'Specialized', '2nd Semester', '1.0', '3.0', '4.0', '1', '2026-08-25 06:44:17'),
('85', '5', '4', NULL, 'HUMANITIES-1', 'Humanities 1 (Creative Writing / Philippine Arts)', 'Humanities 1 (Creative Writing / Philippine Arts)', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('86', '5', '4', NULL, 'SOC-SCI-1', 'Social Science 1 (Philippine Politics / DISS)', 'Social Science 1 (Philippine Politics / DISS)', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('87', '5', '4', '85', 'HUMANITIES-2', 'Humanities 2 (World Religions / Literature)', 'Humanities 2 (World Religions / Literature)', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('88', '5', '4', NULL, 'GAS-ORG-MGMT', 'Organization and Management (GAS Applied)', 'Organization and Management (GAS Applied)', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('89', '6', '4', NULL, 'GAS-ELECTIVE-1', 'General Academic Elective 1 (Selected Track Focus)', 'General Academic Elective 1 (Selected Track Focus)', 'Specialized', 'Specialized', '1st Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('90', '6', '4', '89', 'GAS-ELECTIVE-2', 'General Academic Elective 2 (Advanced Focus)', 'General Academic Elective 2 (Advanced Focus)', 'Specialized', 'Specialized', '2nd Semester', '4.0', '0.0', '4.0', '1', '2026-08-25 06:44:17'),
('91', '6', '4', NULL, 'GAS-CULMINATING', 'GAS Culminating Activity / Academic Portfolio', 'GAS Culminating Activity / Academic Portfolio', 'Specialized', 'Specialized', '2nd Semester', '1.0', '3.0', '4.0', '1', '2026-08-25 06:44:17'),
('92', '5', '5', NULL, 'CSS-MOD1', 'Computer Systems Servicing NC II - Module 1 (Hardware & OS)', 'Computer Systems Servicing NC II - Module 1 (Hardware & OS)', 'Specialized', 'Specialized', '1st Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('93', '5', '5', NULL, 'TECH-DRAFTING', 'Technical Drafting & AutoCAD NC II', 'Technical Drafting & AutoCAD NC II', 'Specialized', 'Specialized', '1st Semester', '2.0', '2.0', '3.0', '1', '2026-08-25 06:44:17'),
('94', '5', '5', '92', 'CSS-MOD2', 'Computer Systems Servicing NC II - Module 2 (Computer Networks)', 'Computer Systems Servicing NC II - Module 2 (Computer Networks)', 'Specialized', 'Specialized', '2nd Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('95', '6', '5', '94', 'CSS-MOD3', 'Computer Systems Servicing NC II - Module 3 (Server & Cloud Setup)', 'Computer Systems Servicing NC II - Module 3 (Server & Cloud Setup)', 'Specialized', 'Specialized', '1st Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('96', '6', '5', NULL, 'WEB-DEV-FUND', 'Web Development & Database Foundations', 'Web Development & Database Foundations', 'Specialized', 'Specialized', '1st Semester', '2.0', '3.0', '4.0', '1', '2026-08-25 06:44:17'),
('97', '6', '5', '95', 'CSS-MOD4', 'Computer Systems Servicing NC II - Module 4 (Maintenance & Cyber)', 'Computer Systems Servicing NC II - Module 4 (Maintenance & Cyber)', 'Specialized', 'Specialized', '2nd Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('98', '6', '5', '95', 'TVL-ICT-IMMERSION', 'TVL-ICT Industry Work Immersion (80 Hours)', 'TVL-ICT Industry Work Immersion (80 Hours)', 'Specialized', 'Specialized', '2nd Semester', '1.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('99', '5', '6', NULL, 'BREAD-PASTRY', 'Bread and Pastry Production NC II', 'Bread and Pastry Production NC II', 'Specialized', 'Specialized', '1st Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('100', '5', '6', NULL, 'TOURISM-PROMO', 'Tourism Promotion & Front Office Services NC II', 'Tourism Promotion & Front Office Services NC II', 'Specialized', 'Specialized', '1st Semester', '3.0', '1.0', '4.0', '1', '2026-08-25 06:44:17'),
('101', '5', '6', '99', 'COOKERY-1', 'Cookery NC II - Part 1 (Food Safety & Cold Kitchen)', 'Cookery NC II - Part 1 (Food Safety & Cold Kitchen)', 'Specialized', 'Specialized', '2nd Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('102', '6', '6', '101', 'COOKERY-2', 'Cookery NC II - Part 2 (Hot Kitchen & Commercial Cooking)', 'Cookery NC II - Part 2 (Hot Kitchen & Commercial Cooking)', 'Specialized', 'Specialized', '1st Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('103', '6', '6', '102', 'FBS-NC2', 'Food and Beverage Services (FBS NC II)', 'Food and Beverage Services (FBS NC II)', 'Specialized', 'Specialized', '2nd Semester', '2.0', '4.0', '4.0', '1', '2026-08-25 06:44:17'),
('104', '6', '6', '102', 'TVL-HE-IMMERSION', 'TVL-HE Hospitality & Culinary Work Immersion', 'TVL-HE Hospitality & Culinary Work Immersion', 'Specialized', 'Specialized', '2nd Semester', '1.0', '4.0', '4.0', '1', '2026-08-25 06:44:17');

DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `strand_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `max_capacity` int(11) DEFAULT 45,
  `capacity` int(11) DEFAULT 45,
  `current_enrolled` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('Active','Full','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sections` (`id`, `school_year_id`, `grade_level_id`, `strand_id`, `name`, `room`, `adviser_id`, `max_capacity`, `capacity`, `current_enrolled`, `is_active`, `status`, `created_at`) VALUES
('1', '1', '1', NULL, 'Grade 7 - Emerald', 'Building A - Room 101', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('2', '1', '1', NULL, 'Grade 7 - Diamond', 'Building A - Room 102', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('3', '1', '1', NULL, 'Grade 7 - Crystal', 'Building A - Room 103', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('4', '1', '2', NULL, 'Grade 8 - Sapphire', 'Building A - Room 201', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('5', '1', '2', NULL, 'Grade 8 - Topaz', 'Building A - Room 202', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('6', '1', '3', NULL, 'Grade 9 - Ruby', 'Building A - Room 301', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('7', '1', '3', NULL, 'Grade 9 - Garnet', 'Building A - Room 302', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('8', '1', '4', NULL, 'Grade 10 - Pearl', 'Building A - Room 401', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('9', '1', '4', NULL, 'Grade 10 - Jade', 'Building A - Room 402', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('10', '1', '5', '1', 'Grade 11 - STEM A', 'Science Wing - Room 501', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('11', '1', '5', '1', 'Grade 11 - STEM B', 'Science Wing - Room 502', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('12', '1', '5', '2', 'Grade 11 - ABM A', 'Business Wing - Room 503', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('13', '1', '5', '2', 'Grade 11 - ABM B', 'Business Wing - Room 504', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('14', '1', '5', '3', 'Grade 11 - HUMSS A', 'Liberal Arts - Room 505', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('15', '1', '5', '3', 'Grade 11 - HUMSS B', 'Liberal Arts - Room 506', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('16', '1', '5', '4', 'Grade 11 - GAS A', 'Academic Hall - Room 507', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('17', '1', '5', '4', 'Grade 11 - GAS B', 'Academic Hall - Room 508', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('18', '1', '5', '5', 'Grade 11 - TVL-ICT A', 'Computer Lab 1', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('19', '1', '5', '5', 'Grade 11 - TVL-ICT B', 'Computer Lab 2', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('20', '1', '5', '6', 'Grade 11 - TVL-HE A', 'Culinary Lab 1', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('21', '1', '5', '6', 'Grade 11 - TVL-HE B', 'Culinary Lab 2', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('22', '1', '6', '1', 'Grade 12 - STEM A', 'Science Wing - Room 601', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('23', '1', '6', '1', 'Grade 12 - STEM B', 'Science Wing - Room 602', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('24', '1', '6', '2', 'Grade 12 - ABM A', 'Business Wing - Room 603', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('25', '1', '6', '2', 'Grade 12 - ABM B', 'Business Wing - Room 604', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('26', '1', '6', '3', 'Grade 12 - HUMSS A', 'Liberal Arts - Room 605', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('27', '1', '6', '3', 'Grade 12 - HUMSS B', 'Liberal Arts - Room 606', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('28', '1', '6', '4', 'Grade 12 - GAS A', 'Academic Hall - Room 607', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('29', '1', '6', '4', 'Grade 12 - GAS B', 'Academic Hall - Room 608', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('30', '1', '6', '5', 'Grade 12 - TVL-ICT A', 'Computer Lab 3', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('31', '1', '6', '5', 'Grade 12 - TVL-ICT B', 'Computer Lab 4', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('32', '1', '6', '6', 'Grade 12 - TVL-HE A', 'Culinary Lab 3', '6', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17'),
('33', '1', '6', '6', 'Grade 12 - TVL-HE B', 'Culinary Lab 4', '7', '45', '45', '0', '1', 'Active', '2026-08-25 06:44:17');

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_no` varchar(50) DEFAULT NULL,
  `student_no` varchar(50) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `school_year_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `track_id` int(11) DEFAULT NULL,
  `strand_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `semester` varchar(30) DEFAULT '1st Semester',
  `enrollment_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT 'Pending Payment',
  `approved_by` int(11) DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `enrollments` (`id`, `enrollment_no`, `student_no`, `student_id`, `application_id`, `school_year_id`, `grade_level_id`, `track_id`, `strand_id`, `section_id`, `lrn`, `semester`, `enrollment_date`, `status`, `approved_by`, `enrolled_at`, `created_at`) VALUES
('1', 'ENR-2026-0001', '2026-JHS-0001', '8', '1', '1', '1', NULL, NULL, '1', NULL, '1st Semester', NULL, 'Officially Enrolled', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('2', 'ENR-2026-0002', '2026-SHS-0005', '9', '2', '1', '5', NULL, '1', '6', NULL, '1st Semester', NULL, 'Officially Enrolled', NULL, '2026-08-25 06:44:17', '2026-08-25 06:44:17'),
('3', 'ENR-2026-0003', 'STUD-2026-0001', '10', '3', '1', '5', NULL, '1', '6', NULL, '1st Semester', NULL, 'Pending Payment', NULL, NULL, '2026-08-25 06:44:17'),
('4', 'ENR-2026-9448', '2026-SHS-9036', '49', '4', '1', '5', '2', '1', '1', '109911215678', '1st Semester', '2026-08-29', 'Pending Payment', NULL, NULL, '2026-08-29 19:33:54'),
('5', 'ENR-2026-9550', '2026-SHS-9029', '51', '5', '1', '5', '2', '1', '1', '109979181703', '1st Semester', '2026-08-29', 'Officially Enrolled', NULL, NULL, '2026-08-29 19:35:17');

DROP TABLE IF EXISTS `fee_structures`;
CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `fee_category_id` int(11) DEFAULT NULL,
  `strand_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_optional` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `fee_structures` (`id`, `school_year_id`, `grade_level_id`, `category_id`, `fee_category_id`, `strand_id`, `name`, `amount`, `is_optional`, `created_at`) VALUES
('1', '1', '1', '1', '1', NULL, 'Base Tuition (Grade 7)', '12500.00', '0', '2026-08-25 06:44:16'),
('2', '1', '1', '2', '2', NULL, 'Computer Science Lab', '1200.00', '0', '2026-08-25 06:44:16'),
('3', '1', '1', '3', '3', NULL, 'Library & Athletic Fee', '1500.00', '0', '2026-08-25 06:44:16'),
('4', '1', '2', '1', '1', NULL, 'Base Tuition (Grade 8)', '12500.00', '0', '2026-08-25 06:44:16'),
('5', '1', '2', '2', '2', NULL, 'Science Lab', '1200.00', '0', '2026-08-25 06:44:16'),
('6', '1', '2', '3', '3', NULL, 'Miscellaneous Fee', '1500.00', '0', '2026-08-25 06:44:16'),
('7', '1', '3', '1', '1', NULL, 'Base Tuition (Grade 9)', '13000.00', '0', '2026-08-25 06:44:16'),
('8', '1', '3', '2', '2', NULL, 'Computer Lab', '1200.00', '0', '2026-08-25 06:44:16'),
('9', '1', '3', '3', '3', NULL, 'Miscellaneous Fee', '1500.00', '0', '2026-08-25 06:44:16'),
('10', '1', '4', '1', '1', NULL, 'Base Tuition (Grade 10)', '13000.00', '0', '2026-08-25 06:44:16'),
('11', '1', '4', '2', '2', NULL, 'Science & Computer Lab', '1400.00', '0', '2026-08-25 06:44:16'),
('12', '1', '4', '3', '3', NULL, 'Graduation & Misc Fee', '1800.00', '0', '2026-08-25 06:44:16'),
('13', '1', '5', '1', '1', NULL, 'Base Tuition (Grade 11)', '22500.00', '0', '2026-08-25 06:44:16'),
('14', '1', '5', '2', '2', NULL, 'Specialized Lab Fee', '2500.00', '0', '2026-08-25 06:44:16'),
('15', '1', '5', '3', '3', NULL, 'LMS & Registration Fee', '2000.00', '0', '2026-08-25 06:44:16'),
('16', '1', '6', '1', '1', NULL, 'Base Tuition (Grade 12)', '22500.00', '0', '2026-08-25 06:44:16'),
('17', '1', '6', '2', '2', NULL, 'Specialized Lab & Immersion', '2500.00', '0', '2026-08-25 06:44:16'),
('18', '1', '6', '3', '3', NULL, 'Graduation, LMS & Misc Fee', '2500.00', '0', '2026-08-25 06:44:16');

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `or_number` varchar(50) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `payment_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `or_number` (`or_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payments` (`id`, `assessment_id`, `enrollment_id`, `or_number`, `amount_paid`, `payment_method`, `payment_date`, `reference_no`, `remarks`, `received_by`, `created_at`) VALUES
('1', '1', NULL, 'OR-2026-000001', '15200.00', 'Cash', NULL, NULL, NULL, '4', '2026-08-25 06:44:17'),
('2', '2', NULL, 'OR-2026-000002', '4500.00', 'GCash', NULL, NULL, NULL, '4', '2026-08-25 06:44:17'),
('3', '5', '5', 'OR-2026-719582', '3000.00', 'GCash', '2026-08-29', 'TXN-GCASH-VERIFIED-1788003317', 'Enrollment Initial Downpayment', '1', '2026-08-29 19:35:17');

DROP TABLE IF EXISTS `school_events`;
CREATE TABLE `school_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_category` varchar(50) DEFAULT 'Academic',
  `category` varchar(50) DEFAULT 'Academic',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `start_time` varchar(50) DEFAULT NULL,
  `end_time` varchar(50) DEFAULT NULL,
  `event_time` varchar(50) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `target_audience` varchar(100) DEFAULT 'All Students',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_events` (`id`, `school_year_id`, `created_by`, `title`, `description`, `event_category`, `category`, `start_date`, `end_date`, `event_date`, `start_time`, `end_time`, `event_time`, `location`, `target_audience`, `is_published`, `created_at`) VALUES
('1', '1', '2', 'Brigada Eskwela & School Readiness Week', 'Community clean-up, classroom preparation, and institutional safety inspection before opening of classes.', 'Institutional', 'Academic', '2026-08-17', '2026-08-21', NULL, '08:00:00', '16:00:00', NULL, 'Campus Grounds & All Facilities', 'All', '1', '2026-08-25 07:41:27'),
('2', '1', '2', 'Official Opening of Classes / Balik Eskwela S.Y. 2026-2027', 'First official day of classes, school orientation, student handbook distribution, and morning assembly.', 'Academic', 'Academic', '2026-08-24', '2026-08-24', NULL, '07:30:00', '15:00:00', NULL, 'Main Gymnasium & Respective Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('3', '1', '2', 'Pagdiriwang ng Buwan ng Wikang Pambansa 2026', 'Culminating program featuring Balagtasan, Sabayang Pagbigkas, Katutubong Sayaw, and Filipino literature exhibitions.', 'Cultural', 'Academic', '2026-08-28', '2026-08-28', NULL, '08:30:00', '16:30:00', NULL, 'Main Gymnasium', 'All', '1', '2026-08-25 07:41:27'),
('4', '1', '2', 'National Heroes Day (Araw ng mga Bayani)', 'Regular national holiday commemorating Philippine national heroes.', 'Holiday', 'Academic', '2026-08-31', '2026-08-31', NULL, NULL, NULL, NULL, 'Nationwide (No Classes)', 'All', '1', '2026-08-25 07:41:27'),
('5', '1', '2', 'National Literacy Day & DepEd Reading Month Kick-off', 'Interactive book fair, reading comprehension contests, and English/Filipino literacy workshop for Junior High.', 'Academic', 'Academic', '2026-09-08', '2026-09-08', NULL, '09:00:00', '15:00:00', NULL, 'School Learning Resource Center / Library', 'Junior High School', '1', '2026-08-25 07:41:27'),
('6', '1', '2', 'SHS Career Guidance & College Readiness Seminar', 'Career counseling, university admissions overview, CHED scholarship orientations, and industry speaker sessions.', 'Academic', 'Academic', '2026-09-18', '2026-09-18', NULL, '08:30:00', '16:00:00', NULL, 'Audio-Visual Center / Auditorium', 'Senior High School', '1', '2026-08-25 07:41:27'),
('7', '1', '2', 'Annual General Parents-Teachers Association (GPTA) Assembly', 'Election of GPTA Executive Board, school development plans presentation, and classroom officer elections.', 'Institutional', 'Academic', '2026-09-25', '2026-09-25', NULL, '13:00:00', '17:00:00', NULL, 'Main Gymnasium', 'Parents & Guardians', '1', '2026-08-25 07:41:27'),
('8', '1', '2', 'World Teachers\' Day & Faculty Appreciation Gala', 'Institutional tribute honoring faculty excellence, teaching awards, and student council performances.', 'Institutional', 'Academic', '2026-10-05', '2026-10-05', NULL, '08:00:00', '15:00:00', NULL, 'School Quadrangle & Auditorium', 'All', '1', '2026-08-25 07:41:27'),
('9', '1', '2', 'First Quarter Periodic Examinations (JHS & SHS)', 'Official Quarter 1 unified examination across all core, applied, and specialized subjects.', 'Academic', 'Academic', '2026-10-22', '2026-10-23', NULL, '07:30:00', '16:00:00', NULL, 'All Section Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('10', '1', '2', 'Mid-Year DepEd Semestral Break & In-Service Training (INSET)', 'Faculty pedagogy enhancement and curriculum review. Mid-year wellness break for students.', 'Academic', 'Academic', '2026-10-26', '2026-10-30', NULL, '08:00:00', '17:00:00', NULL, 'Faculty Conference Hall', 'Faculty & Staff', '1', '2026-08-25 07:41:27'),
('11', '1', '2', 'Resumption of Classes (Start of 2nd Quarter)', 'Classes resume for all Junior and Senior High School grade levels.', 'Academic', 'Academic', '2026-11-02', '2026-11-02', NULL, '07:30:00', '16:00:00', NULL, 'Campus Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('12', '1', '2', 'National Science & Tech Week / STEM Innovation Expo', 'Science investigatory project exhibits, robotics showdown, and mathematics quiz bee.', 'Academic', 'Academic', '2026-11-13', '2026-11-13', NULL, '08:00:00', '17:00:00', NULL, 'Science Wing & Computer Labs', 'All', '1', '2026-08-25 07:41:27'),
('13', '1', '2', '1st Quarter Report Card Distribution (Card Giving Day)', 'Distribution of SF9 Form 138 report cards and parent-teacher consultations.', 'Academic', 'Academic', '2026-11-20', '2026-11-20', NULL, '08:00:00', '12:00:00', NULL, 'Respective Classrooms', 'Parents & Guardians', '1', '2026-08-25 07:41:27'),
('14', '1', '2', 'Bonifacio Day (National Regular Holiday)', 'Commemoration of the birth of Gat Andres Bonifacio.', 'Holiday', 'Academic', '2026-11-30', '2026-11-30', NULL, NULL, NULL, NULL, 'Nationwide (No Classes)', 'All', '1', '2026-08-25 07:41:27'),
('15', '1', '2', 'Second Quarter Periodic Examinations (End of 1st Sem)', 'Official 2nd Quarter and 1st Semester Final Examinations for all Grade Levels.', 'Academic', 'Academic', '2026-12-10', '2026-12-11', NULL, '07:30:00', '16:00:00', NULL, 'All Section Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('16', '1', '2', 'Annual Christmas Festival & Community Year-End Gala', 'Choral competitions, parol-making exhibition, and institutional gift-giving drive.', 'Cultural', 'Academic', '2026-12-18', '2026-12-18', NULL, '09:00:00', '16:00:00', NULL, 'Main Gymnasium', 'All', '1', '2026-08-25 07:41:27'),
('17', '1', '2', 'DepEd Christmas & New Year Holiday Vacation Break', 'Official DepEd vacation break for all learners, faculty, and administrative personnel.', 'Holiday', 'Academic', '2026-12-19', '2027-01-03', NULL, NULL, NULL, NULL, 'Nationwide', 'All', '1', '2026-08-25 07:41:27'),
('18', '1', '2', 'Resumption of Classes & Official Start of 2nd Semester', 'New semester commences for Senior High School strands and Quarter 3 for Junior High.', 'Academic', 'Academic', '2027-01-04', '2027-01-04', NULL, '07:30:00', '16:00:00', NULL, 'Campus Grounds', 'All', '1', '2026-08-25 07:41:27'),
('19', '1', '2', '2nd Quarter Card Giving Day & Parent Consultation', 'Distribution of 1st Semester Final Ratings and remedial guidance consultations.', 'Academic', 'Academic', '2027-01-15', '2027-01-15', NULL, '08:00:00', '12:00:00', NULL, 'Respective Classrooms', 'Parents & Guardians', '1', '2026-08-25 07:41:27'),
('20', '1', '2', 'Annual Sports Festival & Institutional Intramurals 2027', 'Cheerdance exhibition, track & field events, basketball, volleyball, badminton, and e-sports tournaments.', 'Sports', 'Academic', '2027-01-22', '2027-01-23', NULL, '07:00:00', '18:00:00', NULL, 'Sports Complex & Quadrangle', 'All', '1', '2026-08-25 07:41:27'),
('21', '1', '2', 'National Arts Month Celebration & Creative Showcase', 'Visual arts gallery exhibition, theater presentations, musical recital, and culinary tasting booths.', 'Cultural', 'Academic', '2027-02-12', '2027-02-12', NULL, '09:00:00', '16:30:00', NULL, 'School Auditorium & Fine Arts Wing', 'All', '1', '2026-08-25 07:41:27'),
('22', '1', '2', 'EDSA People Power Revolution Anniversary', 'Special non-working holiday celebrating Philippine democracy.', 'Holiday', 'Academic', '2027-02-25', '2027-02-25', NULL, NULL, NULL, NULL, 'Nationwide (No Classes)', 'All', '1', '2026-08-25 07:41:27'),
('23', '1', '2', 'Third Quarter Periodic Examinations', 'Quarter 3 assessment for Junior High and 2nd Semester Midterm for Senior High.', 'Academic', 'Academic', '2027-03-11', '2027-03-12', NULL, '07:30:00', '16:00:00', NULL, 'All Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('24', '1', '2', 'SHS Research Congress & 3Is Capstone Defense', 'Public oral defense of Practical Research 2, 3Is innovations, and business feasibility projects before panel evaluators.', 'Academic', 'Academic', '2027-03-26', '2027-03-26', NULL, '08:00:00', '17:00:00', NULL, 'Audio-Visual Center & Conference Rooms', 'Senior High School', '1', '2026-08-25 07:41:27'),
('25', '1', '2', 'Araw ng Kagitingan (Day of Valor)', 'Regular national holiday commemorating the Fall of Bataan and heroism of Filipino soldiers.', 'Holiday', 'Academic', '2027-04-09', '2027-04-09', NULL, NULL, NULL, NULL, 'Nationwide', 'All', '1', '2026-08-25 07:41:27'),
('26', '1', '2', 'SHS TVL & Academic Track Work Immersion Culminating Conference', 'Presentation of industry internship portfolios, certificate of completion distribution, and partner company awards.', 'Academic', 'Academic', '2027-04-23', '2027-04-23', NULL, '08:30:00', '16:00:00', NULL, 'Main Auditorium', 'Senior High School', '1', '2026-08-25 07:41:27'),
('27', '1', '2', 'Fourth Quarter Final Examinations (Graduating & Non-Graduating)', 'Final academic examinations concluding the school year.', 'Academic', 'Academic', '2027-05-13', '2027-05-14', NULL, '07:30:00', '16:00:00', NULL, 'All Classrooms', 'All', '1', '2026-08-25 07:41:27'),
('28', '1', '2', 'Deliberation of Academic Honors & Special DepEd Awards', 'Academic Committee convening for graduation honors, leadership awards, and conduct rankings.', 'Institutional', 'Academic', '2027-05-21', '2027-05-21', NULL, '09:00:00', '15:00:00', NULL, 'Faculty Conference Hall', 'Faculty & Staff', '1', '2026-08-25 07:41:27'),
('29', '1', '2', 'Junior High School Moving Up Ceremony (Grade 10 Completers)', 'Solemn moving up exercises for Junior High School completers transitioning to Senior High.', 'Institutional', 'Academic', '2027-05-27', '2027-05-27', NULL, '08:00:00', '12:00:00', NULL, 'Main Gymnasium', 'Junior High School', '1', '2026-08-25 07:41:27'),
('30', '1', '2', 'Senior High School Commencement Exercises (Grade 12 Graduates)', 'Official graduation ceremony awarding High School Diplomas to Grade 12 STEM, ABM, HUMSS, GAS, TVL-ICT, and TVL-HE graduates.', 'Institutional', 'Academic', '2027-05-28', '2027-05-28', NULL, '14:00:00', '18:00:00', NULL, 'Main Gymnasium', 'Senior High School', '1', '2026-08-25 07:41:27'),
('31', '1', '2', 'Annual Institutional Recognition Day (Grades 7, 8, 9, 11)', 'Conferment of academic excellence medals and subject proficiency awards for non-graduating students.', 'Academic', 'Academic', '2027-05-29', '2027-05-29', NULL, '08:30:00', '12:30:00', NULL, 'Main Gymnasium', 'All', '1', '2026-08-25 07:41:27');

DROP TABLE IF EXISTS `document_requests`;
CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `copies` int(11) DEFAULT 1,
  `status` enum('Pending','Processing','Ready for Release','Released','Rejected') DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
