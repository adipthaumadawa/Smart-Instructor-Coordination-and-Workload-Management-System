-- ================================================================
-- SMART INSTRUCTOR SYSTEM
-- STARTUP / INITIAL DATABASE
-- ================================================================
-- Purpose:
--   Clean first-time installation for the complete merged system.
--
-- Includes:
--   * Complete merged schema from all 3 member databases
--   * All indexes, AUTO_INCREMENT definitions and foreign keys
--   * Required master/lookup data
--   * One initial System Administrator account
--
-- Excludes:
--   * Historical activity logs
--   * Old attendance/leave records
--   * Old timetable assignments
--   * Old notifications/emails
--   * Old test/sample transactions
--   * information_schema.sql (MariaDB system metadata)
--
-- IMPORTANT:
--   This script DROPS and recreates `smart_instructor_system`.
--   Use it for a fresh project setup, not on a live database.
-- ================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `smart_instructor_system`;
CREATE DATABASE `smart_instructor_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `smart_instructor_system`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `academic_streams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `additional_task_requests` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `lecturer_name` varchar(150) DEFAULT NULL,
  `task_type_id` int(11) NOT NULL,
  `academic_stream_id` int(11) DEFAULT NULL,
  `required_instructors` int(11) NOT NULL DEFAULT 1,
  `requested_by` int(11) NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT 2.00,
  `location` varchar(100) DEFAULT NULL,
  `urgency` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Pending','Assigned','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `to_email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `academic_stream_id` int(11) NOT NULL,
  `designation` varchar(100) DEFAULT 'Lecturer',
  `max_weekly_hours` decimal(5,2) DEFAULT 40.00,
  `status` enum('active','on_leave','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `instructor_attendance` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','On Leave','Half Day') NOT NULL DEFAULT 'Present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL COMMENT 'users.id of Non-Academic Staff who recorded this',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_records` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `leave_type` enum('Casual','Medical','Duty','Other') DEFAULT 'Casual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lecture_hall_bookings` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `booked_by_user_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` varchar(200) NOT NULL,
  `status` enum('Confirmed','Cancelled','Pending') DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lecture_rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 50,
  `location` varchar(150) DEFAULT NULL,
  `room_type` enum('Lecture Hall','Laboratory','Tutorial Room','Seminar Room') DEFAULT 'Lecture Hall',
  `status` enum('Available','Under Maintenance','Booked') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger','leave','replacement','task','presentation') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `presentation_panel_members` (
  `id` int(11) NOT NULL,
  `presentation_session_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `role_in_panel` enum('Chair','Member','Examiner') DEFAULT 'Member',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `presentation_sessions` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `project_coordinator_id` int(11) NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `replacement_requests` (
  `id` int(11) NOT NULL,
  `task_assignment_id` int(11) DEFAULT NULL,
  `leave_record_id` int(11) DEFAULT NULL,
  `requested_by_instructor_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `suggested_instructor_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `additional_task_request_id` int(11) DEFAULT NULL,
  `task_type_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assignment_date` date DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` decimal(5,2) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('Assigned','Accepted','Completed','Cancelled') DEFAULT 'Assigned',
  `is_presentation_panel` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(3,2) DEFAULT 1.00,
  `is_presentation` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `course` varchar(10) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `academic_year` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `timetable_requirements` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(150) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `academic_stream_id` int(11) DEFAULT NULL,
  `required_instructors` int(11) NOT NULL DEFAULT 1,
  `semester` varchar(20) DEFAULT 'Semester 1',
  `academic_year` varchar(10) DEFAULT '2025/2026',
  `status` enum('Open','Partially Staffed','Fully Staffed') DEFAULT 'Open',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `timetable_slots` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(150) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `semester` varchar(20) DEFAULT 'Semester 1',
  `academic_year` varchar(10) DEFAULT '2025/2026',
  `auto_assigned` tinyint(1) DEFAULT 0,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `urgency_replacements` (
  `id` int(11) NOT NULL,
  `task_assignment_id` int(11) NOT NULL,
  `handled_by_coordinator_id` int(11) NOT NULL,
  `new_instructor_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Handled','Completed') DEFAULT 'Handled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INDEXES
--
-- Indexes for table `academic_streams`
--
ALTER TABLE `academic_streams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `additional_task_requests`
--
ALTER TABLE `additional_task_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_type_id` (`task_type_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `academic_stream_id` (`academic_stream_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `academic_stream_id` (`academic_stream_id`),
  ADD KEY `idx_instructors_user` (`user_id`);

--
-- Indexes for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_instructor_date` (`instructor_id`,`attendance_date`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_attendance_status` (`status`);

--
-- Indexes for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_leave_instructor_dates` (`instructor_id`,`start_date`,`end_date`);

--
-- Indexes for table `lecture_hall_bookings`
--
ALTER TABLE `lecture_hall_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booked_by_user_id` (`booked_by_user_id`),
  ADD KEY `idx_bookings_room_date` (`room_id`,`booking_date`);

--
-- Indexes for table `lecture_rooms`
--
ALTER TABLE `lecture_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `presentation_panel_members`
--
ALTER TABLE `presentation_panel_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panel` (`presentation_session_id`,`instructor_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `presentation_sessions`
--
ALTER TABLE `presentation_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_coordinator_id` (`project_coordinator_id`);

--
-- Indexes for table `replacement_requests`
--
ALTER TABLE `replacement_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_assignment_id` (`task_assignment_id`),
  ADD KEY `requested_by_instructor_id` (`requested_by_instructor_id`),
  ADD KEY `suggested_instructor_id` (`suggested_instructor_id`),
  ADD KEY `responded_by` (`responded_by`),
  ADD KEY `leave_record_id` (`leave_record_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `additional_task_request_id` (`additional_task_request_id`),
  ADD KEY `task_type_id` (`task_type_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_task_assignments_instructor` (`instructor_id`),
  ADD KEY `idx_task_assignments_date` (`scheduled_date`);

--
-- Indexes for table `task_types`
--
ALTER TABLE `task_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable_requirements`
--
ALTER TABLE `timetable_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_stream_id` (`academic_stream_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `requirement_id` (`requirement_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `urgency_replacements`
--
ALTER TABLE `urgency_replacements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_assignment_id` (`task_assignment_id`),
  ADD KEY `handled_by_coordinator_id` (`handled_by_coordinator_id`),
  ADD KEY `new_instructor_id` (`new_instructor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`);


-- AUTO_INCREMENT
ALTER TABLE `academic_streams` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `activity_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `additional_task_requests` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `departments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `email_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `instructors` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `instructor_attendance` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `leave_records` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lecture_hall_bookings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lecture_rooms` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `notifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `presentation_panel_members` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `presentation_sessions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `replacement_requests` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `system_settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `task_assignments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `task_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `timetables` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `timetable_requirements` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `timetable_slots` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `urgency_replacements` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- INITIAL MASTER DATA
INSERT INTO `academic_streams` (`id`, `name`, `code`, `description`, `created_at`) VALUES
(1, 'Computer Science', 'CS', 'Core Computer Science stream', '2026-08-19 02:55:56'),
(2, 'Information Systems', 'IS', 'Information Systems and Management stream', '2026-08-19 02:55:56'),
(3, 'Software Engineering', 'SE', 'Software Engineering specialization', '2026-08-19 02:55:56');

INSERT INTO `departments` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'University of Colombo School of Computing', 'UCSC', '2026-08-19 02:55:56');

INSERT INTO `lecture_rooms` (`id`, `room_name`, `capacity`, `location`, `room_type`, `status`, `created_at`) VALUES
(1, 'Lecture Hall 1', 120, 'Ground Floor - Main Building', 'Lecture Hall', 'Available', '2026-08-19 02:55:56'),
(2, 'Lecture Hall 2', 80, 'First Floor - Main Building', 'Lecture Hall', 'Available', '2026-08-19 02:55:56'),
(3, 'Lecture Hall 3', 60, 'Second Floor - New Wing', 'Lecture Hall', 'Available', '2026-08-19 02:55:56'),
(4, 'Lab A', 40, 'Ground Floor - Lab Complex', 'Laboratory', 'Available', '2026-08-19 02:55:56'),
(5, 'Lab B', 35, 'First Floor - Lab Complex', 'Laboratory', 'Available', '2026-08-19 02:55:56'),
(6, 'Seminar Room 1', 25, 'Third Floor - Admin Block', 'Seminar Room', 'Available', '2026-08-19 02:55:56');

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'System Administrator', 'Full system access, user and role management', '2026-08-19 02:55:56'),
(2, 'Instructor', 'View timetable, workload, record leave, request replacements', '2026-08-19 02:55:56'),
(3, 'Instructor Coordinator', 'Assign tasks, manage replacements, view availability and suggestions', '2026-08-19 02:55:56'),
(4, 'Chief Instructor Coordinator', 'Monitor overall workload, leave, allocations and reports', '2026-08-19 02:55:56'),
(5, 'Non-Academic Staff', 'Manage timetable records, room schedules, attendance, receive leave notifications', '2026-08-19 02:55:56'),
(6, 'Project Coordinator', 'Create and schedule presentation sessions, assign panel members', '2026-08-19 02:55:56'),
(7, 'Director / Department Head', 'Read-only monitoring of reports, workload distribution and overall coordination', '2026-08-19 02:55:56');

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'max_weekly_workload_hours', '40', 'Maximum recommended weekly workload hours for instructors', '2026-08-19 02:55:56'),
(2, 'default_presentation_duration', '3', 'Default duration in hours for presentation panels', '2026-08-19 02:55:56'),
(3, 'email_notifications_enabled', '1', 'Enable or disable email notifications system-wide', '2026-08-19 02:55:56'),
(4, 'academic_year', '2025/2026', 'Current academic year', '2026-08-19 02:55:56'),
(5, 'semester', 'Semester 1', 'Current semester', '2026-08-19 02:55:56');

INSERT INTO `task_types` (`id`, `name`, `description`, `weight`, `is_presentation`, `created_at`) VALUES
(1, 'Lecture', 'Regular lecture session', 1.00, 0, '2026-08-19 02:55:56'),
(2, 'Tutorial', 'Tutorial or discussion session', 1.00, 0, '2026-08-19 02:55:56'),
(3, 'Lab Session', 'Practical laboratory session', 1.50, 0, '2026-08-19 02:55:56'),
(4, 'Presentation Panel', 'Final year project presentation panel', 0.00, 1, '2026-08-19 02:55:56'),
(5, 'Additional Duty', 'Extra administrative or coordination duty', 1.00, 0, '2026-08-19 02:55:56'),
(6, 'Meeting', 'Department or committee meeting', 0.50, 0, '2026-08-19 02:55:56');

INSERT INTO `users`
(`id`, `username`, `email`, `password`, `full_name`, `role_id`, `status`, `phone`, `avatar_url`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$V1Z.iCP7W4e4rsKwP232V.xPO5bgM3GVOFeAXp2bKPJIwTX5RYrdm', 'System Administrator', 1, 'active', NULL, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- FOREIGN KEY CONSTRAINTS
--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `additional_task_requests`
--
ALTER TABLE `additional_task_requests`
  ADD CONSTRAINT `additional_task_requests_ibfk_1` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`),
  ADD CONSTRAINT `additional_task_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `additional_task_requests_ibfk_3` FOREIGN KEY (`academic_stream_id`) REFERENCES `academic_streams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `instructors`
--
ALTER TABLE `instructors`
  ADD CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructors_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `instructors_ibfk_3` FOREIGN KEY (`academic_stream_id`) REFERENCES `academic_streams` (`id`);

--
-- Constraints for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  ADD CONSTRAINT `instructor_attendance_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructor_attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD CONSTRAINT `leave_records_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_records_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lecture_hall_bookings`
--
ALTER TABLE `lecture_hall_bookings`
  ADD CONSTRAINT `lecture_hall_bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `lecture_rooms` (`id`),
  ADD CONSTRAINT `lecture_hall_bookings_ibfk_2` FOREIGN KEY (`booked_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presentation_panel_members`
--
ALTER TABLE `presentation_panel_members`
  ADD CONSTRAINT `presentation_panel_members_ibfk_1` FOREIGN KEY (`presentation_session_id`) REFERENCES `presentation_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presentation_panel_members_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presentation_sessions`
--
ALTER TABLE `presentation_sessions`
  ADD CONSTRAINT `presentation_sessions_ibfk_1` FOREIGN KEY (`project_coordinator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `replacement_requests`
--
ALTER TABLE `replacement_requests`
  ADD CONSTRAINT `replacement_requests_ibfk_1` FOREIGN KEY (`task_assignment_id`) REFERENCES `task_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `replacement_requests_ibfk_2` FOREIGN KEY (`requested_by_instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `replacement_requests_ibfk_3` FOREIGN KEY (`suggested_instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `replacement_requests_ibfk_4` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `replacement_requests_ibfk_5` FOREIGN KEY (`leave_record_id`) REFERENCES `leave_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`additional_task_request_id`) REFERENCES `additional_task_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`),
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`),
  ADD CONSTRAINT `task_assignments_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `timetable_requirements`
--
ALTER TABLE `timetable_requirements`
  ADD CONSTRAINT `timetable_requirements_ibfk_1` FOREIGN KEY (`academic_stream_id`) REFERENCES `academic_streams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `timetable_requirements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_slots_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `timetable_requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_slots_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `urgency_replacements`
--
ALTER TABLE `urgency_replacements`
  ADD CONSTRAINT `urgency_replacements_ibfk_1` FOREIGN KEY (`task_assignment_id`) REFERENCES `task_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `urgency_replacements_ibfk_2` FOREIGN KEY (`handled_by_coordinator_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `urgency_replacements_ibfk_3` FOREIGN KEY (`new_instructor_id`) REFERENCES `instructors` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

COMMIT;