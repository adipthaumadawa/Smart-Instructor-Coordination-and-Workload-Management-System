-- =====================================================
-- Smart Instructor Coordination and Workload Management System
-- Database: smart_instructor_system
-- Complete normalized schema with sample data for XAMPP
-- =====================================================

CREATE DATABASE IF NOT EXISTS smart_instructor_system 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE smart_instructor_system;

-- Drop tables if exist (for clean re-import)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS lecture_hall_bookings;
DROP TABLE IF EXISTS lecture_rooms;
DROP TABLE IF EXISTS presentation_panel_members;
DROP TABLE IF EXISTS presentation_sessions;
DROP TABLE IF EXISTS urgency_replacements;
DROP TABLE IF EXISTS replacement_requests;
DROP TABLE IF EXISTS task_assignments;
DROP TABLE IF EXISTS additional_task_requests;
DROP TABLE IF EXISTS leave_records;
DROP TABLE IF EXISTS timetable_slots;
DROP TABLE IF EXISTS task_types;
DROP TABLE IF EXISTS instructors;
DROP TABLE IF EXISTS academic_streams;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS system_settings;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. ROLES TABLE
-- =====================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. USERS TABLE (with role_id for simplicity - single primary role)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 3. DEPARTMENTS
-- =====================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 4. ACADEMIC STREAMS
-- =====================================================
CREATE TABLE academic_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 5. INSTRUCTORS (linked to users for login, extended profile)
-- =====================================================
CREATE TABLE instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    academic_stream_id INT NOT NULL,
    designation VARCHAR(100) DEFAULT 'Lecturer',
    max_weekly_hours DECIMAL(5,2) DEFAULT 40.00,
    status ENUM('active', 'on_leave', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (academic_stream_id) REFERENCES academic_streams(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 6. TASK TYPES
-- =====================================================
CREATE TABLE task_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    weight DECIMAL(3,2) DEFAULT 1.00,           -- for future workload weighting
    is_presentation TINYINT(1) DEFAULT 0,       -- 1 = do NOT count in normal workload
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 7. TIMETABLE SLOTS (recurring weekly schedule)
-- =====================================================
CREATE TABLE timetable_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject VARCHAR(150) NOT NULL,
    location VARCHAR(100),
    semester VARCHAR(20) DEFAULT 'Semester 1',
    academic_year VARCHAR(10) DEFAULT '2025/2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 8. LEAVE RECORDS
-- =====================================================
CREATE TABLE leave_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    leave_type ENUM('Casual','Medical','Duty','Other') DEFAULT 'Casual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 9. ADDITIONAL TASK REQUESTS (created by coordinators)
-- =====================================================
CREATE TABLE additional_task_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    task_type_id INT NOT NULL,
    requested_by INT NOT NULL,                  -- user_id of coordinator
    preferred_date DATE,
    start_time TIME,
    end_time TIME,
    duration_hours DECIMAL(5,2) DEFAULT 2.00,
    location VARCHAR(100),
    urgency ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
    status ENUM('Pending','Assigned','Completed','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 10. TASK ASSIGNMENTS (core assignment table)
-- =====================================================
CREATE TABLE task_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    additional_task_request_id INT NULL,
    task_type_id INT NOT NULL,
    instructor_id INT NOT NULL,
    assigned_by INT NOT NULL,                   -- user_id who assigned
    assignment_date DATE NULL,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours DECIMAL(5,2) NOT NULL,
    location VARCHAR(100),
    status ENUM('Assigned','Accepted','Completed','Cancelled') DEFAULT 'Assigned',
    is_presentation_panel TINYINT(1) DEFAULT 0, -- 1 = presentation, do not count in workload
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (additional_task_request_id) REFERENCES additional_task_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 11. REPLACEMENT REQUESTS (from instructors)
-- =====================================================
CREATE TABLE replacement_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_assignment_id INT NOT NULL,
    requested_by_instructor_id INT NOT NULL,
    reason TEXT NOT NULL,
    suggested_instructor_id INT NULL,
    status ENUM('Pending','Accepted','Rejected') DEFAULT 'Pending',
    responded_by INT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_assignment_id) REFERENCES task_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by_instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    FOREIGN KEY (suggested_instructor_id) REFERENCES instructors(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 12. URGENCY REPLACEMENTS (handled by coordinator)
-- =====================================================
CREATE TABLE urgency_replacements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_assignment_id INT NOT NULL,
    handled_by_coordinator_id INT NOT NULL,
    new_instructor_id INT NOT NULL,
    reason TEXT,
    status ENUM('Handled','Completed') DEFAULT 'Handled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_assignment_id) REFERENCES task_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by_coordinator_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (new_instructor_id) REFERENCES instructors(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 13. PRESENTATION SESSIONS
-- =====================================================
CREATE TABLE presentation_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    course_code VARCHAR(50),
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(100),
    project_coordinator_id INT NOT NULL,
    status ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_coordinator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 14. PRESENTATION PANEL MEMBERS (separate from normal workload)
-- =====================================================
CREATE TABLE presentation_panel_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presentation_session_id INT NOT NULL,
    instructor_id INT NOT NULL,
    role_in_panel ENUM('Chair','Member','Examiner') DEFAULT 'Member',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (presentation_session_id) REFERENCES presentation_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_panel (presentation_session_id, instructor_id)
) ENGINE=InnoDB;

-- =====================================================
-- 15. LECTURE ROOMS / LABORATORIES (combined for simplicity)
-- =====================================================
CREATE TABLE lecture_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT DEFAULT 50,
    location VARCHAR(150),
    room_type ENUM('Lecture Hall','Laboratory','Tutorial Room','Seminar Room') DEFAULT 'Lecture Hall',
    status ENUM('Available','Under Maintenance','Booked') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 16. LECTURE HALL BOOKINGS
-- =====================================================
CREATE TABLE lecture_hall_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    booked_by_user_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    status ENUM('Confirmed','Cancelled','Pending') DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES lecture_rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (booked_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 17. NOTIFICATIONS (in-app)
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger','leave','replacement','task','presentation') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    related_id INT NULL,                        -- e.g. task_assignment_id or leave_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 18. EMAIL LOGS (for PHPMailer tracking)
-- =====================================================
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT,
    status ENUM('sent','failed') DEFAULT 'sent',
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 19. ACTIVITY LOGS (audit trail)
-- =====================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 20. SYSTEM SETTINGS
-- =====================================================
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_instructors_user ON instructors(user_id);
CREATE INDEX idx_task_assignments_instructor ON task_assignments(instructor_id);
CREATE INDEX idx_task_assignments_date ON task_assignments(scheduled_date);
CREATE INDEX idx_leave_instructor_dates ON leave_records(instructor_id, start_date, end_date);
CREATE INDEX idx_bookings_room_date ON lecture_hall_bookings(room_id, booking_date);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_activity_user_date ON activity_logs(user_id, created_at);

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Roles
INSERT INTO roles (id, role_name, description) VALUES
(1, 'System Administrator', 'Full system access, user and role management'),
(2, 'Instructor', 'View timetable, workload, record leave, request replacements'),
(3, 'Instructor Coordinator', 'Assign tasks, manage replacements, view availability and suggestions'),
(4, 'Chief Instructor Coordinator', 'Monitor overall workload, leave, allocations and reports'),
(5, 'Non-Academic Staff', 'Manage timetable records, room schedules, attendance, receive leave notifications'),
(6, 'Project Coordinator', 'Create and schedule presentation sessions, assign panel members'),
(7, 'Director / Department Head', 'Read-only monitoring of reports, workload distribution and overall coordination');

-- Departments
INSERT INTO departments (id, name, code) VALUES
(1, 'University of Colombo School of Computing', 'UCSC');

-- Academic Streams
INSERT INTO academic_streams (id, name, code, description) VALUES
(1, 'Computer Science', 'CS', 'Core Computer Science stream'),
(2, 'Information Systems', 'IS', 'Information Systems and Management stream'),
(3, 'Software Engineering', 'SE', 'Software Engineering specialization');

-- Users (password for all: password123)
-- Hash: $2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm
INSERT INTO users (id, username, email, password, full_name, role_id, status, phone) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'System Administrator', 1, 'active', '0771234567'),
(2, 'instructor1', 'instructor@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Dr. Kasun Perera', 2, 'active', '0772345678'),
(3, 'coordinator1', 'coordinator@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Prof. Nimali Fernando', 3, 'active', '0773456789'),
(4, 'chief1', 'chief@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Prof. Sunil Jayawardena', 4, 'active', '0774567890'),
(5, 'nonacademic1', 'nonacademic@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Mr. Ranjith Silva', 5, 'active', '0775678901'),
(6, 'projectcoord1', 'projectcoordinator@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Ms. Anjali Wickramasinghe', 6, 'active', '0776789012'),
(7, 'director1', 'director@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'Prof. Priyantha Abeysekara', 7, 'active', '0777890123');

-- Instructors (only for role 2 and some coordinators who also teach)
INSERT INTO instructors (id, user_id, employee_id, first_name, last_name, department_id, academic_stream_id, designation, max_weekly_hours, status) VALUES
(1, 2, 'EMP001', 'Kasun', 'Perera', 1, 1, 'Senior Lecturer', 40.00, 'active'),
(2, 3, 'EMP002', 'Nimali', 'Fernando', 1, 2, 'Professor', 35.00, 'active'),  -- Coordinator who also teaches
(3, 4, 'EMP003', 'Sunil', 'Jayawardena', 1, 1, 'Professor', 30.00, 'active'); -- Chief who teaches occasionally

-- Task Types
INSERT INTO task_types (id, name, description, weight, is_presentation) VALUES
(1, 'Lecture', 'Regular lecture session', 1.00, 0),
(2, 'Tutorial', 'Tutorial or discussion session', 1.00, 0),
(3, 'Lab Session', 'Practical laboratory session', 1.50, 0),
(4, 'Presentation Panel', 'Final year project presentation panel', 0.00, 1),  -- Not counted in workload
(5, 'Additional Duty', 'Extra administrative or coordination duty', 1.00, 0),
(6, 'Meeting', 'Department or committee meeting', 0.50, 0);

-- Timetable Slots (for instructor 1 - Dr. Kasun Perera)
INSERT INTO timetable_slots (instructor_id, day_of_week, start_time, end_time, subject, location, semester, academic_year) VALUES
(1, 'Monday', '08:00:00', '10:00:00', 'Data Structures and Algorithms', 'Lecture Hall 2', 'Semester 1', '2025/2026'),
(1, 'Tuesday', '10:00:00', '12:00:00', 'Database Management Systems', 'Lab A', 'Semester 1', '2025/2026'),
(1, 'Wednesday', '14:00:00', '16:00:00', 'Software Engineering Principles', 'Lecture Hall 1', 'Semester 1', '2025/2026'),
(1, 'Thursday', '09:00:00', '11:00:00', 'Data Structures and Algorithms - Tutorial', 'Tutorial Room 3', 'Semester 1', '2025/2026');

-- Leave Records (sample)
INSERT INTO leave_records (instructor_id, leave_type, start_date, end_date, reason, status, approved_by) VALUES
(1, 'Casual', '2026-07-05', '2026-07-06', 'Personal family matter', 'Approved', 3);

-- Additional Task Requests (created by coordinator)
INSERT INTO additional_task_requests (id, title, description, task_type_id, requested_by, preferred_date, start_time, end_time, duration_hours, location, urgency, status) VALUES
(1, 'Guest Lecture on AI Ethics', 'Deliver a special guest lecture for 3rd year students', 1, 3, '2026-07-10', '14:00:00', '16:00:00', 2.00, 'Lecture Hall 3', 'Medium', 'Pending'),
(2, 'Lab Supervision - Machine Learning', 'Supervise ML lab session due to staff shortage', 3, 3, '2026-07-08', '09:00:00', '12:00:00', 3.00, 'Lab B', 'High', 'Pending');

-- Task Assignments (some already assigned)
INSERT INTO task_assignments (id, additional_task_request_id, task_type_id, instructor_id, assigned_by, scheduled_date, start_time, end_time, duration_hours, location, status, is_presentation_panel, notes) VALUES
(1, NULL, 1, 1, 3, '2026-06-30', '08:00:00', '10:00:00', 2.00, 'Lecture Hall 2', 'Assigned', 0, 'Regular Monday lecture'),
(2, 1, 1, 2, 3, '2026-07-10', '14:00:00', '16:00:00', 2.00, 'Lecture Hall 3', 'Assigned', 0, 'Guest lecture assignment'),
(3, NULL, 4, 1, 6, '2026-07-15', '09:00:00', '12:00:00', 3.00, 'Seminar Room 1', 'Assigned', 1, 'FYP Presentation Panel - NOT counted in workload');

-- Replacement Requests (sample pending)
INSERT INTO replacement_requests (task_assignment_id, requested_by_instructor_id, reason, status) VALUES
(1, 1, 'Medical appointment on the same day', 'Pending');

-- Presentation Sessions
INSERT INTO presentation_sessions (id, title, course_code, session_date, start_time, end_time, venue, project_coordinator_id, status) VALUES
(1, 'Final Year Project Presentations - Batch 2023', 'SCS 4001', '2026-07-20', '09:00:00', '16:00:00', 'Seminar Room 1', 6, 'Scheduled');

-- Presentation Panel Members (presentation workload not counted)
INSERT INTO presentation_panel_members (presentation_session_id, instructor_id, role_in_panel) VALUES
(1, 1, 'Chair'),
(1, 2, 'Member');

-- Lecture Rooms
INSERT INTO lecture_rooms (id, room_name, capacity, location, room_type, status) VALUES
(1, 'Lecture Hall 1', 120, 'Ground Floor - Main Building', 'Lecture Hall', 'Available'),
(2, 'Lecture Hall 2', 80, 'First Floor - Main Building', 'Lecture Hall', 'Available'),
(3, 'Lecture Hall 3', 60, 'Second Floor - New Wing', 'Lecture Hall', 'Available'),
(4, 'Lab A', 40, 'Ground Floor - Lab Complex', 'Laboratory', 'Available'),
(5, 'Lab B', 35, 'First Floor - Lab Complex', 'Laboratory', 'Available'),
(6, 'Seminar Room 1', 25, 'Third Floor - Admin Block', 'Seminar Room', 'Available');

-- Lecture Hall Bookings (sample)
INSERT INTO lecture_hall_bookings (room_id, booked_by_user_id, booking_date, start_time, end_time, purpose, status) VALUES
(1, 6, '2026-07-05', '10:00:00', '12:00:00', 'Project Coordinator meeting with external examiner', 'Confirmed'),
(4, 2, '2026-07-08', '09:00:00', '12:00:00', 'Extra lab session for Database practical', 'Confirmed');

-- Notifications (sample)
INSERT INTO notifications (user_id, title, message, type, is_read, related_id) VALUES
(2, 'New Task Assigned', 'You have been assigned a Guest Lecture on AI Ethics on 2026-07-10', 'task', 0, 2),
(2, 'Leave Approved', 'Your casual leave from 2026-07-05 to 2026-07-06 has been approved.', 'leave', 1, 1),
(3, 'Replacement Request Pending', 'Dr. Kasun Perera has requested a replacement for the Monday lecture.', 'replacement', 0, 1);

-- Activity Logs (sample)
INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES
(1, 'User Login', 'System Administrator logged in successfully', '127.0.0.1'),
(3, 'Task Assignment', 'Assigned instructor Dr. Kasun Perera to Guest Lecture task', '127.0.0.1'),
(2, 'Leave Request', 'Submitted leave request for 2026-07-05 to 2026-07-06', '127.0.0.1');

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_weekly_workload_hours', '40', 'Maximum recommended weekly workload hours for instructors'),
('default_presentation_duration', '3', 'Default duration in hours for presentation panels'),
('email_notifications_enabled', '1', 'Enable or disable email notifications system-wide'),
('academic_year', '2025/2026', 'Current academic year'),
('semester', 'Semester 1', 'Current semester');

-- =====================================================
-- END OF DATABASE SCRIPT
-- =====================================================
-- To import in phpMyAdmin:
-- 1. Create database smart_instructor_system (if not created)
-- 2. Import this file
-- =====================================================