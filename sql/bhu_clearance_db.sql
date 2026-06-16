-- =====================================================================
-- BHU Clearance System - schema + sample data
-- Database: bhu_clearance_db
-- Default password for ALL sample accounts: "password"
-- =====================================================================

DROP DATABASE IF EXISTS bhu_clearance_db;
CREATE DATABASE bhu_clearance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bhu_clearance_db;

-- ---------------------------------------------------------------------
-- Departments
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL UNIQUE,
    name        VARCHAR(150) NOT NULL,
    college     VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

INSERT INTO departments (code, name, college) VALUES
('CSE', 'Computer Science & Engineering', 'College of Informatics'),
('IT',  'Information Technology',         'College of Informatics'),
('ACC', 'Accounting & Finance',           'College of Business'),
('MGT', 'Management',                     'College of Business'),
('ME',  'Mechanical Engineering',         'College of Engineering');

-- ---------------------------------------------------------------------
-- Staff / portal users (admin, office officers, dept heads, registrar)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    role          ENUM('admin','library','cafeteria','dormitory','finance','sports','depthead','registrar') NOT NULL,
    office        VARCHAR(40)  NULL,         -- mirrors role for office officers
    department_id INT          NULL,         -- only for depthead
    experience_years INT       NOT NULL DEFAULT 0,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- All sample passwords = "password" (bcrypt)
SET @P := '$2y$12$s.SE4C0LZXvBBPU5LE3.euxR1b6Nl96FT/kdK1kYvRpJ6jwnDVzb.';

INSERT INTO users (username, password_hash, full_name, role, office, department_id) VALUES
('admin',        @P, 'System Administrator',  'admin',     NULL,        NULL),
('library',      @P, 'Abebe Bekele',          'library',   'library',   NULL),
('cafeteria',    @P, 'Hawi Tadesse',          'cafeteria', 'cafeteria', NULL),
('dormitory',    @P, 'Chala Diriba',          'dormitory', 'dormitory', NULL),
('finance',      @P, 'Selamawit Girma',       'finance',   'finance',   NULL),
('sports',       @P, 'Dawit Lemma',           'sports',    'sports',    NULL),
('depthead_cse', @P, 'Dr. Mulugeta Hailu',    'depthead',  NULL,        1),
('depthead_acc', @P, 'Dr. Tigist Alemu',      'depthead',  NULL,        3),
('registrar',    @P, 'Mr. Solomon Worku',     'registrar', NULL,        NULL);

-- ---------------------------------------------------------------------
-- Students
-- ---------------------------------------------------------------------
CREATE TABLE students (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_id    VARCHAR(30)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    gender        ENUM('M','F') NOT NULL,
    email         VARCHAR(150),
    phone         VARCHAR(30),
    program       VARCHAR(80),                 -- e.g. Regular / Extension
    year          INT,
    department_id INT NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO students (student_id, password_hash, full_name, gender, email, phone, program, year, department_id) VALUES
('BHU/0001/16', @P, 'Sara Demisse',     'F', 'sara@bhu.edu.et',    '0911000001', 'Regular', 4, 1),
('BHU/0002/16', @P, 'Yonas Tesfaye',    'M', 'yonas@bhu.edu.et',   '0911000002', 'Regular', 4, 1),
('BHU/0003/16', @P, 'Rahel Mekonnen',   'F', 'rahel@bhu.edu.et',   '0911000003', 'Regular', 3, 2),
('BHU/0004/16', @P, 'Kebede Worku',     'M', 'kebede@bhu.edu.et',  '0911000004', 'Regular', 4, 3),
('BHU/0005/16', @P, 'Liya Tariku',      'F', 'liya@bhu.edu.et',    '0911000005', 'Regular', 4, 3),
('BHU/0006/16', @P, 'Nathan Assefa',    'M', 'nathan@bhu.edu.et',  '0911000006', 'Regular', 4, 4),
('BHU/0007/16', @P, 'Marta Bekele',     'F', 'marta@bhu.edu.et',   '0911000007', 'Regular', 4, 5);

-- ---------------------------------------------------------------------
-- Clearance records (one row per student per office)
-- ---------------------------------------------------------------------
CREATE TABLE clearances (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    office      ENUM('library','cafeteria','dormitory','finance','sports') NOT NULL,
    status      ENUM('pending','cleared','hold') NOT NULL DEFAULT 'pending',
    remarks     TEXT,
    updated_by  INT,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_office (student_id, office),
    CONSTRAINT fk_clr_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_clr_user    FOREIGN KEY (updated_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Final approval (dept head + registrar)
-- ---------------------------------------------------------------------
CREATE TABLE final_approval (
    student_id        INT PRIMARY KEY,
    dept_status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    dept_remarks      TEXT,
    request_reason    TEXT,
    dept_approved_by  INT,
    dept_approved_at  DATETIME,
    registrar_status  ENUM('pending','approved') DEFAULT 'pending',
    registrar_approved_by INT,
    registrar_approved_at DATETIME,
    certificate_code  VARCHAR(40),
    CONSTRAINT fk_fa_student   FOREIGN KEY (student_id)            REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_fa_dept_user FOREIGN KEY (dept_approved_by)      REFERENCES users(id)    ON DELETE SET NULL,
    CONSTRAINT fk_fa_reg_user  FOREIGN KEY (registrar_approved_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- Auto-create blank clearance rows for every student × office
INSERT INTO clearances (student_id, office, status)
SELECT s.id, o.office, 'pending'
FROM students s
CROSS JOIN (
    SELECT 'library' AS office UNION ALL SELECT 'cafeteria' UNION ALL SELECT 'dormitory'
    UNION ALL SELECT 'finance' UNION ALL SELECT 'sports'
) o;

-- Auto-create final_approval row per student
INSERT INTO final_approval (student_id) SELECT id FROM students;

-- Some sample state so the dashboards look alive
UPDATE clearances SET status='cleared', remarks='All books returned.',           updated_by=2 WHERE student_id=1 AND office='library';
UPDATE clearances SET status='cleared', remarks='Cafeteria tray returned.',       updated_by=3 WHERE student_id=1 AND office='cafeteria';
UPDATE clearances SET status='hold',    remarks='Room blanket not returned.',     updated_by=4 WHERE student_id=1 AND office='dormitory';
UPDATE clearances SET status='cleared', remarks='No outstanding balance.',        updated_by=5 WHERE student_id=1 AND office='finance';
UPDATE clearances SET status='hold',    remarks='University Sport Kit not returned.', updated_by=6 WHERE student_id=1 AND office='sports';

UPDATE clearances SET status='hold',    remarks='1 Java Programming book missing.', updated_by=2 WHERE student_id=2 AND office='library';

-- ---------------------------------------------------------------------
-- Notifications (in-app messages to students)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    message     TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;
