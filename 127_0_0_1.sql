CREATE DATABASE IF NOT EXISTS `bhu_clearance_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bhu_clearance_db`;

-- Table structure for table `departments`
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `college` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `code`, `name`, `college`) VALUES
(1, 'CSE', 'Computer Science & Engineering', 'College of Informatics'),
(2, 'IT', 'Information Technology', 'College of Informatics'),
(3, 'ACC', 'Accounting & Finance', 'College of Business'),
(4, 'MGT', 'Management', 'College of Business'),
(5, 'ME', 'Mechanical Engineering', 'College of Engineering');

-- Table structure for table `students`
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `program` varchar(80) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `student_id`, `password_hash`, `full_name`, `gender`, `email`, `phone`, `program`, `year`, `department_id`) VALUES
(1, 'BHU/0001/16', '$2y$12$s.SE4C0LZXvBBPU5LE3.euxR1b6Nl96FT/kdK1kYvRpJ6jwnDVzb.', 'Sara Demisse', 'F', 'sara@bhu.edu.et', '0911000001', 'Regular', 4, 1),
(2, 'BHU/0002/16', '$2y$12$s.SE4C0LZXvBBPU5LE3.euxR1b6Nl96FT/kdK1kYvRpJ6jwnDVzb.', 'Yonas Tesfaye', 'M', 'yonas@bhu.edu.et', '0911000002', 'Regular', 4, 1);

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','library','cafeteria','dormitory','finance','sports','depthead','registrar') NOT NULL,
  `office` varchar(40) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `experience_years` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `office`, `department_id`) VALUES
(1, 'admin', '$2y$12$s.SE4C0LZXvBBPU5LE3.euxR1b6Nl96FT/kdK1kYvRpJ6jwnDVzb.', 'System Administrator', 'admin', NULL, NULL),
(2, 'library', '$2y$12$s.SE4C0LZXvBBPU5LE3.euxR1b6Nl96FT/kdK1kYvRpJ6jwnDVzb.', 'Abebe Bekele', 'library', 'library', NULL),
(12, 'finance', '$2y$10$FLEbbTfN3bH98WdKnXlgZ.9YD47t.dVK/Vf9O1NPEVmVLSt9mlFBO', 'abebe geleta', 'finance', 'finance', NULL);

-- Table structure for table `clearances`
CREATE TABLE IF NOT EXISTS `clearances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `office` enum('library','cafeteria','dormitory','finance','sports') NOT NULL,
  `status` enum('pending','cleared','hold') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_office` (`student_id`,`office`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `final_approval`
CREATE TABLE IF NOT EXISTS `final_approval` (
  `student_id` int(11) NOT NULL,
  `dept_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `dept_remarks` text DEFAULT NULL,
  `dept_approved_by` int(11) DEFAULT NULL,
  `dept_approved_at` datetime DEFAULT NULL,
  `registrar_status` enum('pending','approved') DEFAULT 'pending',
  `registrar_approved_by` int(11) DEFAULT NULL,
  `registrar_approved_at` datetime DEFAULT NULL,
  `certificate_code` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;