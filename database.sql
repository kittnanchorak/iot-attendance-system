-- =====================================================
-- IoT Attendance Tracking System
-- Database: iot_attendance
-- =====================================================
-- ⚠️ XAMPP Port: 3309 (ตั้งค่าใน config/database.php)
-- =====================================================
-- ⚠️ สำหรับ AlwaysData / Shared Hosting:
--    CREATE DATABASE และ USE ถูกลบออกแล้ว
--    Import ไฟล์นี้ผ่าน phpMyAdmin โดยเลือก Database ก่อน
-- =====================================================

-- CREATE DATABASE IF NOT EXISTS `iot_attendance`
--   DEFAULT CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE `iot_attendance`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: users (อาจารย์ / ผู้ดูแลระบบ)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: students (ข้อมูลนักศึกษา)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id_full` VARCHAR(20) NOT NULL COMMENT 'รหัส 11 หลัก',
    `student_code` VARCHAR(10) NOT NULL UNIQUE COMMENT 'รหัส 6 หลัก (Keypad)',
    `title` VARCHAR(20) NOT NULL COMMENT 'คำนำหน้า',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `room` VARCHAR(20) NOT NULL COMMENT 'ห้องเรียน',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_student_code` (`student_code`),
    INDEX `idx_room` (`room`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: attendance (บันทึกการเข้าเรียน)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `check_in_time` TIME DEFAULT NULL,
    `status` ENUM('present', 'absent', 'pending') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_attendance` (`student_id`, `attendance_date`),
    INDEX `idx_date` (`attendance_date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: api_keys (API Key สำหรับเครื่อง IoT)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `api_key` VARCHAR(100) NOT NULL UNIQUE,
    `device_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: settings (ตั้งค่าระบบ)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Users (password: admin123 / teacher123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$uwsXgy3Kk6jf/M7y3DpP1.gbm1rw444yZUgflH1h/v1aFNtCzqIk.', 'ผู้ดูแลระบบ', 'admin'),
('teacher', '$2y$10$EB/t8WqkDea9q/wrhClvbeaJsfPJwxTM1aKpubIvFtPZZkz3Tyryy', 'อ.อนุสรวรรณ วารีกุล', 'teacher');

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('absent_time', '08:30'),
('reset_time', '16:00'),
('class_name', 'เครือข่ายคอมพิวเตอร์และความปลอดภัย (31908)');

-- API Key (🔑 Random Key — ใช้คู่กับ Arduino และ test_iot.php)
INSERT INTO `api_keys` (`api_key`, `device_name`) VALUES
('d683b2603092cfb957d82ce73035ce2bdef8df9c7ad013e1f6213250b58f8863', 'IoT Keypad Device 01');

-- =====================================================
-- นักศึกษาห้อง ปวส.1/11 (31 คน)
-- =====================================================
INSERT INTO `students` (`student_id_full`, `student_code`, `title`, `first_name`, `last_name`, `room`) VALUES
('69 31908 0030', '032789', 'นาย', 'ธนัชชน', 'หิรัญหลวง', 'ปวส.1/11'),
('69 31908 0032', '032791', 'นาย', 'ศตายุ', 'ศรีพงษ์', 'ปวส.1/11'),
('69 31908 0037', '032796', 'นาย', 'กรวัฒน์', 'ละวรรณวงษ์', 'ปวส.1/11'),
('69 31908 0040', '032799', 'นาย', 'ธนภัทร', 'มุ้ยจีน', 'ปวส.1/11'),
('69 31908 0061', '032820', 'นาย', 'อัษฎ์เดช', 'ฉายอรุณ', 'ปวส.1/11'),
('69 31908 0083', '032842', 'นาย', 'วทัญญู', 'พานนนท์', 'ปวส.1/11'),
('69 31908 0086', '032845', 'นาย', 'ภาคิน', 'สุขโข', 'ปวส.1/11'),
('69 31908 0120', '032879', 'นาย', 'สุรเชษฐ์', 'นามอนุ', 'ปวส.1/11'),
('69 31908 0121', '032880', 'นาย', 'ประกาศิต', 'ทองกูล', 'ปวส.1/11'),
('69 31908 0123', '032882', 'นาย', 'ธีรศักดิ์', 'สนธิ', 'ปวส.1/11'),
('69 31908 0124', '032883', 'นาย', 'รชต', 'กันเกษม', 'ปวส.1/11'),
('69 31908 0126', '032885', 'นาย', 'วชิรวิทย์', 'อุตตมากร', 'ปวส.1/11'),
('69 31908 0128', '032887', 'นาย', 'ณัฏฐกิตติ์', 'เลขมาศ', 'ปวส.1/11'),
('69 31908 0131', '032890', 'นาย', 'วรัญญู', 'กัญญาพันธ์', 'ปวส.1/11'),
('69 31908 0133', '032892', 'นาย', 'ภาสกร', 'วังนันไชย', 'ปวส.1/11'),
('69 31908 0134', '032893', 'นาย', 'กิตตินันท์', 'จงรักษ์', 'ปวส.1/11'),
('69 31908 0137', '032896', 'นาย', 'นพรุจ', 'แสงนัย', 'ปวส.1/11'),
('69 31908 0150', '032909', 'นาย', 'ปริพัฒน์', 'มีน้อย', 'ปวส.1/11'),
('69 31908 0151', '032910', 'นางสาว', 'ครองขวัญ', 'คชาไพร', 'ปวส.1/11'),
('69 31908 0153', '032912', 'นาย', 'ภูริ', 'มาสวัสดิ์', 'ปวส.1/11'),
('69 31908 0154', '032913', 'นาย', 'อนุรักษ์', 'ชาวพิจิตร', 'ปวส.1/11'),
('69 31908 0155', '032914', 'นาย', 'ธนนันท์', 'ผ่องรัศมี', 'ปวส.1/11'),
('69 31908 0156', '032915', 'นาย', 'ธนาวุฒิ', 'พุ่มเฉลา', 'ปวส.1/11'),
('69 31908 0157', '032916', 'นาย', 'รัฐศักดิ์', 'บำรุงรัฐ', 'ปวส.1/11'),
('69 31908 0158', '032917', 'นาย', 'สิรวัชร์', 'จั่นกล่ำ', 'ปวส.1/11'),
('69 31908 0159', '032918', 'นาย', 'มงคล', 'ตั้งมั่นภักดี', 'ปวส.1/11'),
('69 31908 0167', '032926', 'นาย', 'ชัชนน', 'ปานอาภรณ์', 'ปวส.1/11'),
('69 31908 0168', '032927', 'นางสาว', 'สุสลิตา', 'ขุนอินทร์', 'ปวส.1/11'),
('69 31908 0173', '032932', 'นาย', 'ณัฐพล', 'นาคศรีรุ้ง', 'ปวส.1/11'),
('69 31908 0229', '032988', 'นางสาว', 'ศิริกร', 'การสมนุช', 'ปวส.1/11'),
('69 31908 0391', '033150', 'นางสาว', 'กัญญา', 'พรหมแสง', 'ปวส.1/11');

-- =====================================================
-- นักศึกษาห้อง ปวส.1/12 (12 คน)
-- =====================================================
INSERT INTO `students` (`student_id_full`, `student_code`, `title`, `first_name`, `last_name`, `room`) VALUES
('69 31908 0002', '032761', 'นาย', 'รัฐภูมิ', 'วิธวัชวาทิน', 'ปวส.1/12'),
('69 31908 0076', '032835', 'นาย', 'ธนพนธ์', 'ยังมี', 'ปวส.1/12'),
('69 31908 0199', '032958', 'นางสาว', 'ณัฐภัทรลดา', 'เค้าแก้ว', 'ปวส.1/12'),
('69 31908 0205', '032964', 'นาย', 'ปิยะพงษ์', 'แสงสว่าง', 'ปวส.1/12'),
('69 31908 0259', '033018', 'นาย', 'พิชญุตม์', 'สุขวิสิฐภัฐ', 'ปวส.1/12'),
('69 31908 0275', '033034', 'นาย', 'ศาศวัต', 'คงประพัฒน์', 'ปวส.1/12'),
('69 31908 0281', '033040', 'นางสาว', 'จิราภรณ์', 'ไทรจำปา', 'ปวส.1/12'),
('69 31908 0283', '033042', 'นาย', 'ชยพล', 'ทับทวี', 'ปวส.1/12'),
('69 31908 0286', '033045', 'นาย', 'นรเศรษฐ', 'เกาว์เกลือ', 'ปวส.1/12'),
('69 31908 0386', '033145', 'นาย', 'พงศภัค', 'ลำสัน', 'ปวส.1/12'),
('69 31908 0401', '033160', 'นางสาว', 'โศหลา', 'จิตตรีศิลป์', 'ปวส.1/12'),
('69 31908 0420', '033179', 'นาย', 'อนันตสิน', 'สมพามา', 'ปวส.1/12');
