-- =====================================================
-- Supabase PostgreSQL Schema & Initial Data
-- Project: IoT Attendance & Student Leave System
-- =====================================================

-- 1. ล้างตารางเดิม (ถ้ามี)
DROP TABLE IF EXISTS leave_approvals CASCADE;
DROP TABLE IF EXISTS leave_attachments CASCADE;
DROP TABLE IF EXISTS leave_requests CASCADE;
DROP TABLE IF EXISTS attendance CASCADE;
DROP TABLE IF EXISTS api_keys CASCADE;
DROP TABLE IF EXISTS settings CASCADE;
DROP TABLE IF EXISTS system_settings CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS students CASCADE;

-- 2. ตาราง students (ข้อมูลนักเรียน/นักศึกษา)
CREATE TABLE students (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id_full VARCHAR(20) NOT NULL,
    student_code VARCHAR(10) NOT NULL UNIQUE,
    title VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    room VARCHAR(20) NOT NULL,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_students_code ON students (student_code);
CREATE INDEX idx_students_room ON students (room);

-- 3. ตาราง users (บัญชีผู้ใช้งาน: แอดมิน, อาจารย์, นักเรียน)
CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'teacher' CHECK (role IN ('admin', 'teacher', 'student')),
    student_id BIGINT DEFAULT NULL REFERENCES students(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. ตาราง attendance (บันทึกการเข้าเรียนจากเครื่อง IoT)
CREATE TABLE attendance (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    attendance_date DATE NOT NULL,
    check_in_time TIME DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('present', 'absent', 'pending')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_attendance UNIQUE (student_id, attendance_date)
);

CREATE INDEX idx_attendance_date ON attendance (attendance_date);
CREATE INDEX idx_attendance_status ON attendance (status);

-- 5. ตาราง api_keys (API Key สำหรับเครื่องสแกน IoT)
CREATE TABLE api_keys (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    api_key VARCHAR(100) NOT NULL UNIQUE,
    device_name VARCHAR(100) NOT NULL,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. ตาราง settings (ตั้งค่าเวลาเช็คชื่อ)
CREATE TABLE settings (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL
);

-- 7. ตาราง system_settings (ตั้งค่าระเบียบการลา)
CREATE TABLE system_settings (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 8. ตาราง leave_requests (คำร้องขอลาหยุด)
CREATE TABLE leave_requests (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    request_no VARCHAR(30) NOT NULL UNIQUE,
    student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    leave_type VARCHAR(20) NOT NULL CHECK (leave_type IN ('sick', 'personal')),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL DEFAULT 1,
    reason TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_leave_student ON leave_requests (student_id);
CREATE INDEX idx_leave_type ON leave_requests (leave_type);
CREATE INDEX idx_leave_status ON leave_requests (status);

-- 9. ตาราง leave_attachments (เอกสารแนบ/ใบรับรองแพทย์)
CREATE TABLE leave_attachments (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    leave_request_id BIGINT NOT NULL REFERENCES leave_requests(id) ON DELETE CASCADE,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMPTZ DEFAULT NOW()
);

-- 10. ตาราง leave_approvals (ประวัติการพิจารณาอนุมัติ)
CREATE TABLE leave_approvals (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    leave_request_id BIGINT NOT NULL UNIQUE REFERENCES leave_requests(id) ON DELETE CASCADE,
    action_by BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action VARCHAR(20) NOT NULL CHECK (action IN ('approved', 'rejected')),
    comment TEXT DEFAULT NULL,
    action_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- INSERT ข้อมูลเริ่มต้นทั้งหมด (Default Records)
-- =====================================================

-- Users (admin: admin123, teacher: teacher123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$uwsXgy3Kk6jf/M7y3DpP1.gbm1rw444yZUgflH1h/v1aFNtCzqIk.', 'ผู้ดูแลระบบ', 'admin'),
('teacher', '$2y$10$EB/t8WqkDea9q/wrhClvbeaJsfPJwxTM1aKpubIvFtPZZkz3Tyryy', 'อ.อนุสรวรรณ วารีกุล', 'teacher');

-- Settings เดิม
INSERT INTO settings (setting_key, setting_value) VALUES
('absent_time', '08:30'),
('reset_time', '16:00'),
('class_name', 'เครือข่ายคอมพิวเตอร์และความปลอดภัย (31908)');

-- API Key สำหรับ Arduino / ESP32
INSERT INTO api_keys (api_key, device_name) VALUES
('d683b2603092cfb957d82ce73035ce2bdef8df9c7ad013e1f6213250b58f8863', 'IoT Keypad Device 01');

-- System Settings สำหรับระบบการลา
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('personal_leave_notice_days', '3', 'จำนวนวันล่วงหน้าขั้นต่ำสำหรับการยื่นขอลากิจ (วัน)'),
('max_leave_file_size_mb', '5', 'ขนาดไฟล์เอกสารแนบสูงสุด (MB)'),
('allowed_file_types', 'pdf,jpg,jpeg,png', 'ประเภทไฟล์ที่อนุญาตสำหรับเอกสารประกอบการลา');

-- รายชื่อนักศึกษาห้อง ปวส.1/11 (31 คน)
INSERT INTO students (student_id_full, student_code, title, first_name, last_name, room) VALUES
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

-- รายชื่อนักศึกษาห้อง ปวส.1/12 (12 คน)
INSERT INTO students (student_id_full, student_code, title, first_name, last_name, room) VALUES
('69 31908 0002', '032761', 'นาย', 'รักภูมิ', 'วิธวัชวาทิน', 'ปวส.1/12'),
('69 31908 0076', '032835', 'นาย', 'ธนพนธ์', 'ยังมี', 'ปวส.1/12'),
('69 31908 0199', '032958', 'นางสาว', 'ณัฏภัทรลดา', 'เค้าแก้ว', 'ปวส.1/12'),
('69 31908 0205', '032964', 'นาย', 'ปิยะพงษ์', 'แสงสว่าง', 'ปวส.1/12'),
('69 31908 0259', '033018', 'นาย', 'พิชญุตม์', 'สุขวิสิฏศักดิ์', 'ปวส.1/12'),
('69 31908 0275', '033034', 'นาย', 'ศาศวัต', 'คงประพัฒน์', 'ปวส.1/12'),
('69 31908 0281', '033040', 'นางสาว', 'จิราภรณ์', 'ไทรจำปา', 'ปวส.1/12'),
('69 31908 0283', '033042', 'นาย', 'ชยพล', 'ทับทวี', 'ปวส.1/12'),
('69 31908 0286', '033045', 'นาย', 'นรเศรษฐ', 'เกาว์เกลือ', 'ปวส.1/12'),
('69 31908 0386', '033145', 'นาย', 'พงศภัค', 'ล่ำสัน', 'ปวส.1/12'),
('69 31908 0401', '033160', 'นางสาว', 'โศหลา', 'จิตตรีศิลป์', 'ปวส.1/12'),
('69 31908 0420', '033179', 'นาย', 'อนันตสิน', 'สมพามะ', 'ปวส.1/12');
