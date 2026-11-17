-- =====================================================
-- Migration: Student Leave Management System
-- Database: iot_attendance
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. ตรวจสอบและอัปเดตตาราง users เพื่อรองรับ student_id และ role 'student'
ALTER TABLE `users` 
    MODIFY COLUMN `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'teacher',
    ADD COLUMN IF NOT EXISTS `student_id` INT NULL DEFAULT NULL AFTER `role`,
    ADD CONSTRAINT `fk_user_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE;

-- 2. สร้างตาราง system_settings (การตั้งค่าระเบียบการลาและระบบ)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ค่าเริ่มต้นสำหรับระเบียบการลา
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('personal_leave_notice_days', '3', 'จำนวนวันล่วงหน้าขั้นต่ำสำหรับการยื่นขอลากิจ (วัน)'),
('max_leave_file_size_mb', '5', 'ขนาดไฟล์เอกสารแนบสูงสุด (MB)'),
('allowed_file_types', 'pdf,jpg,jpeg,png', 'ประเภทไฟล์ที่อนุญาตสำหรับเอกสารประกอบการลา')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 3. ตาราง leave_requests (คำร้องขอลาหยุด)
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_no` VARCHAR(30) NOT NULL UNIQUE,
    `student_id` INT NOT NULL,
    `leave_type` ENUM('sick', 'personal') NOT NULL COMMENT 'sick=ลาป่วย, personal=ลากิจ',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT NOT NULL DEFAULT 1,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_leave_student` (`student_id`),
    INDEX `idx_leave_type` (`leave_type`),
    INDEX `idx_leave_status` (`status`),
    INDEX `idx_leave_date` (`start_date`, `end_date`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตาราง leave_attachments (เอกสารประกอบการลา เช่น ใบรับรองแพทย์)
CREATE TABLE IF NOT EXISTS `leave_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `leave_request_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์ที่บันทึกในเซิร์ฟเวอร์',
    `file_name` VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์เดิมที่ผู้ใช้อัปโหลด',
    `file_type` VARCHAR(50) NOT NULL COMMENT 'MIME type หรือนามสกุลไฟล์',
    `file_size` INT NOT NULL COMMENT 'ขนาดไฟล์เป็น bytes',
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_attach_leave` (`leave_request_id`),
    FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตาราง leave_approvals (ประวัติการตรวจสอบและอนุมัติ)
CREATE TABLE IF NOT EXISTS `leave_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `leave_request_id` INT NOT NULL,
    `action_by` INT NOT NULL COMMENT 'ID ผู้ใช้งาน (ครูหรือผู้ดูแล)',
    `action` ENUM('approved', 'rejected') NOT NULL,
    `comment` TEXT NULL COMMENT 'หมายเหตุหรือเหตุผลกรณีไม่อนุมัติ',
    `action_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_approval_leave` (`leave_request_id`),
    FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`action_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
