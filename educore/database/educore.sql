-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2026 at 12:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `educore`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `tenant_id`, `name`, `is_current`, `created_at`, `updated_at`) VALUES
(1, 1, '2025/2026', 1, '2026-06-14 00:37:19', '2026-06-21 17:52:53'),
(2, 1, '2026/2027', 0, '2026-06-23 11:16:08', '2026-06-23 11:16:08');

-- --------------------------------------------------------

--
-- Table structure for table `academic_tracks`
--

CREATE TABLE `academic_tracks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `section` enum('primary','junior','senior','general') NOT NULL DEFAULT 'general',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_tracks`
--

INSERT INTO `academic_tracks` (`id`, `tenant_id`, `name`, `slug`, `section`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Primary', 'primary', 'junior', 1, 1, '2026-06-17 02:41:39', '2026-06-17 02:41:39'),
(2, NULL, 'General', 'general', 'general', 1, 2, '2026-06-17 02:41:39', '2026-06-17 02:41:39'),
(3, NULL, 'Science', 'science', 'senior', 1, 3, '2026-06-17 02:41:39', '2026-06-17 02:41:39'),
(4, NULL, 'Humanities', 'humanities', 'senior', 1, 4, '2026-06-17 02:41:39', '2026-06-17 02:41:39'),
(5, NULL, 'Business', 'business', 'senior', 1, 5, '2026-06-17 02:41:39', '2026-06-17 02:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `application_number` varchar(255) NOT NULL,
  `portal_token` varchar(64) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_status` enum('not_required','pending','paid','waived') NOT NULL DEFAULT 'not_required',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `other_names` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `religion` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) NOT NULL DEFAULT 'Nigerian',
  `state_of_origin` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `last_report_card` varchar(255) DEFAULT NULL,
  `applying_for_class_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `previous_class` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) NOT NULL,
  `guardian_phone` varchar(255) NOT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `guardian_relationship` varchar(255) NOT NULL DEFAULT 'parent',
  `guardian_occupation` varchar(255) DEFAULT NULL,
  `guardian_address` varchar(255) DEFAULT NULL,
  `status` enum('pending','shortlisted','admitted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `application_date` date NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `decision_date` date DEFAULT NULL,
  `enrolled_as_student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `portal_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(255) NOT NULL DEFAULT 'portal',
  `academic_year` varchar(255) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `offer_letter_sent` tinyint(1) NOT NULL DEFAULT 0,
  `offer_sent_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`id`, `tenant_id`, `application_number`, `portal_token`, `payment_reference`, `payment_status`, `first_name`, `last_name`, `other_names`, `date_of_birth`, `gender`, `religion`, `nationality`, `state_of_origin`, `address`, `passport_photo`, `birth_certificate`, `last_report_card`, `applying_for_class_level_id`, `previous_school`, `previous_class`, `guardian_name`, `guardian_phone`, `guardian_email`, `guardian_relationship`, `guardian_occupation`, `guardian_address`, `status`, `notes`, `application_date`, `reviewed_by`, `decision_date`, `enrolled_as_student_id`, `created_at`, `updated_at`, `portal_email_verified`, `source`, `academic_year`, `interview_date`, `interview_score`, `interview_notes`, `offer_letter_sent`, `offer_sent_at`, `deleted_at`) VALUES
(1, 1, 'APP-2026-LWZMVL', NULL, NULL, 'not_required', 'ASMAU', 'ISHAQ', 'SALEH', '2014-03-20', 'female', 'Islam', 'Nigerian', 'TARABA', NULL, NULL, NULL, NULL, 6, NULL, NULL, 'ISHAK SALEH', '09012345678', NULL, 'parent', NULL, NULL, 'admitted', NULL, '2026-06-16', 2, '2026-06-16', 4, '2026-06-16 14:38:51', '2026-06-16 14:48:18', 0, 'portal', NULL, NULL, NULL, NULL, 0, NULL, NULL),
(2, 1, 'APP-GREENFIELD-ACADEMY-2026-6DPSFC', NULL, NULL, 'not_required', 'Abdulrahman', 'Yusuf', NULL, '2012-12-31', 'male', 'Islam', 'Nigerian', 'Borno', 'Abuja', NULL, NULL, NULL, 6, 'Fortress', 'SSS 2', 'Yusuf Ibrahim', '12345678900', NULL, 'father', 'Civil Servant', NULL, 'admitted', NULL, '2026-06-16', 2, '2026-06-16', NULL, '2026-06-16 16:47:19', '2026-06-16 16:48:07', 0, 'portal', NULL, NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admission_documents`
--

CREATE TABLE `admission_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admission_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_documents`
--

INSERT INTO `admission_documents` (`id`, `admission_id`, `tenant_id`, `document_type`, `file_path`, `original_name`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'passport_photo', 'admissions/1/2/hhjNjLkaHNIHFomtDl0jDmAV1389vLltMFjGy1sC.jpg', 'IMG_20241111_081116_773.jpg', '2026-06-16 16:47:19', '2026-06-16 16:47:19'),
(2, 2, 1, 'birth_certificate', 'admissions/1/2/Xg5d9AeTg9DHS8tST7Q8O5muMyOEVvMHJZ2u42fX.jpg', 'BSc Certificate.jpg', '2026-06-16 16:47:19', '2026-06-16 16:47:19'),
(3, 2, 1, 'last_report_card', 'admissions/1/2/TgLbjJr7gXGfyVa0CIo3UsUjcYgH3ZpYnGfnNuRi.jpg', 'NYSC Certificate.jpg', '2026-06-16 16:47:19', '2026-06-16 16:47:19');

-- --------------------------------------------------------

--
-- Table structure for table `admission_portal_settings`
--

CREATE TABLE `admission_portal_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `opens_on` date DEFAULT NULL,
  `closes_on` date DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `application_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `welcome_message` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `require_passport` tinyint(1) NOT NULL DEFAULT 1,
  `require_birth_cert` tinyint(1) NOT NULL DEFAULT 0,
  `require_report_card` tinyint(1) NOT NULL DEFAULT 0,
  `notify_guardian_sms` tinyint(1) NOT NULL DEFAULT 1,
  `notify_guardian_email` tinyint(1) NOT NULL DEFAULT 1,
  `auto_shortlist` tinyint(1) NOT NULL DEFAULT 0,
  `footer_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_portal_settings`
--

INSERT INTO `admission_portal_settings` (`id`, `tenant_id`, `is_open`, `opens_on`, `closes_on`, `academic_year`, `application_fee`, `welcome_message`, `requirements`, `require_passport`, `require_birth_cert`, `require_report_card`, `notify_guardian_sms`, `notify_guardian_email`, `auto_shortlist`, `footer_note`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-06-14', '2026-06-20', '2026/2027', 0.00, 'Admission into all classes at Epitome Model Islamic Schools is open', 'Birth Certificate/Birth Attestation Certificate from NPC\r\nTransfer Letter from previous school\r\nNIN\r\nLast Academic Report', 1, 1, 1, 1, 1, 1, '07065595768', '2026-06-16 03:27:03', '2026-06-16 15:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `agent_messages`
--

CREATE TABLE `agent_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `audience` enum('all','active','inactive') NOT NULL DEFAULT 'all',
  `sent_by` bigint(20) UNSIGNED NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_message_reads`
--

CREATE TABLE `agent_message_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_payouts`
--

CREATE TABLE `agent_payouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'paid',
  `note` text DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_referrals`
--

CREATE TABLE `agent_referrals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sale_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','paid') NOT NULL DEFAULT 'pending',
  `sale_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `audience` varchar(255) NOT NULL DEFAULT 'all',
  `priority` enum('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `publish_date` date NOT NULL,
  `expire_date` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_types`
--

CREATE TABLE `assessment_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `weight_percentage` tinyint(3) UNSIGNED NOT NULL,
  `is_exam` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_types`
--

INSERT INTO `assessment_types` (`id`, `tenant_id`, `term_id`, `name`, `weight_percentage`, `is_exam`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Test1', 5, 0, '2026-06-14 01:58:58', '2026-06-14 01:58:58'),
(2, 1, 1, 'Test2', 5, 0, '2026-06-14 01:59:16', '2026-06-14 01:59:16'),
(3, 1, 1, 'CA1', 10, 0, '2026-06-14 01:59:31', '2026-06-14 01:59:31'),
(4, 1, 1, 'CA2', 10, 0, '2026-06-14 01:59:46', '2026-06-14 01:59:46'),
(5, 1, 1, 'Exam', 70, 1, '2026-06-14 02:00:11', '2026-06-14 02:00:11'),
(6, 1, 2, 'Test1', 5, 0, '2026-06-24 12:44:22', '2026-06-24 12:44:22'),
(7, 1, 2, 'Test2', 5, 0, '2026-06-24 12:44:35', '2026-06-24 12:44:35'),
(8, 1, 2, 'CA1', 10, 0, '2026-06-24 12:44:48', '2026-06-24 12:44:48'),
(9, 1, 2, 'CA2', 10, 0, '2026-06-24 12:45:03', '2026-06-24 12:45:03'),
(10, 1, 2, 'Exam', 70, 0, '2026-06-24 12:45:15', '2026-06-24 12:45:15'),
(11, 1, 3, 'Test1', 5, 0, '2026-06-24 12:45:28', '2026-06-24 12:45:28'),
(13, 1, 3, 'Test2', 5, 0, '2026-06-24 12:46:35', '2026-06-24 12:46:35'),
(14, 1, 3, 'CA1', 10, 0, '2026-06-24 12:46:48', '2026-06-24 12:46:48'),
(15, 1, 3, 'CA2', 10, 0, '2026-06-24 12:47:03', '2026-06-24 12:47:03'),
(16, 1, 3, 'Exam', 70, 1, '2026-06-24 12:53:12', '2026-06-24 12:53:12');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `marked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `tenant_id`, `student_id`, `class_arm_id`, `term_id`, `marked_by`, `attendance_date`, `status`, `remark`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 14, 1, 2, '2026-06-14', 'absent', NULL, '2026-06-14 13:57:07', '2026-06-14 13:57:07'),
(2, 1, 4, 14, 1, 2, '2026-06-21', 'present', NULL, '2026-06-21 18:21:42', '2026-06-21 18:21:42'),
(3, 1, 1, 14, 1, 2, '2026-06-21', 'present', NULL, '2026-06-21 18:21:42', '2026-06-21 18:21:42');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `tenant_id`, `actor_user_id`, `auditable_type`, `auditable_id`, `action`, `old_values`, `new_values`, `reason`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'App\\Models\\Student', 1, 'student_enrollment.repaired', '{\"issues\":[\"missing_matching_enrollment\",\"no_current_enrollment\"],\"current_class_arm_id\":14}', '{\"current_enrollment_id\":1,\"class_arm_id\":14,\"session_id\":1,\"term_id\":1}', 'CLI student enrollment repair', NULL, NULL, '2026-06-19 09:19:24', '2026-06-19 09:19:24'),
(2, 1, NULL, 'App\\Models\\Student', 4, 'student_enrollment.repaired', '{\"issues\":[\"missing_matching_enrollment\",\"no_current_enrollment\"],\"current_class_arm_id\":14}', '{\"current_enrollment_id\":2,\"class_arm_id\":14,\"session_id\":1,\"term_id\":1}', 'CLI student enrollment repair', NULL, NULL, '2026-06-19 09:20:01', '2026-06-19 09:20:01'),
(3, 1, 2, 'App\\Models\\StudentClassTransfer', 1, 'student_class_transfer.requested', '[]', '{\"student_id\":4,\"from_class_arm_id\":14,\"to_class_arm_id\":15,\"academic_session_id\":1,\"term_id\":1,\"effective_date\":\"2026-06-19\",\"status\":\"pending\"}', 'Change of course', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-19 21:01:36', '2026-06-19 21:01:36'),
(4, 1, 2, 'App\\Models\\StudentClassTransfer', 1, 'student_class_transfer.completed', '{\"student_id\":4,\"class_arm_id\":14,\"current_enrollment_id\":2,\"transfer_status\":\"pending\"}', '{\"student_id\":4,\"class_arm_id\":15,\"new_enrollment_id\":3,\"transfer_status\":\"completed\",\"synced_subjects\":1}', 'Change of course', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-19 21:01:55', '2026-06-19 21:01:55'),
(5, 1, 2, 'App\\Models\\Student', 4, 'student.left', '{\"status\":\"active\",\"graduation_date\":null,\"current_enrollment_ids\":[3]}', '{\"status\":\"left\",\"effective_date\":\"2026-06-16\",\"reason\":\"Left the school\",\"status_history_id\":1,\"graduation_date\":null}', 'Left the school', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-19 21:04:21', '2026-06-19 21:04:21'),
(6, 1, 2, 'App\\Models\\Student', 4, 'student.status.changed', '{\"status\":\"active\",\"graduation_date\":null,\"current_enrollment_ids\":[3]}', '{\"status\":\"left\",\"effective_date\":\"2026-06-16\",\"reason\":\"Left the school\",\"status_history_id\":1,\"graduation_date\":null}', 'Left the school', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-19 21:04:21', '2026-06-19 21:04:21'),
(7, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-19 22:12:56', '2026-06-19 22:12:56'),
(8, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 06:07:26', '2026-06-20 06:07:26'),
(9, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 06:56:24', '2026-06-20 06:56:24'),
(10, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 07:01:17', '2026-06-20 07:01:17'),
(11, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 07:01:28', '2026-06-20 07:01:28'),
(12, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"dashboard\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 07:02:02', '2026-06-20 07:02:02'),
(13, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"settings.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 07:03:20', '2026-06-20 07:03:20'),
(14, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 09:54:18', '2026-06-20 09:54:18'),
(15, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 09:54:51', '2026-06-20 09:54:51'),
(16, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:22:27', '2026-06-20 10:22:27'),
(17, 2, 1, 'App\\Models\\Tenant', 2, 'tenant.updated', '{\"name\":\"Blueray Academy\",\"slug\":\"blue-ray-academy\",\"subdomain\":null,\"email\":\"info@bluerayacademy.ng\",\"phone\":\"08012340095\",\"address\":\"Ruga Juli, Mararaba, Karu\",\"status\":\"active\",\"subscription_expires_at\":\"2027-06-19T00:00:00.000000Z\",\"motto\":null,\"logo_path\":null,\"theme_primary\":\"#071E45\",\"theme_accent\":\"#D79A21\",\"theme_sidebar\":\"#071E45\",\"primary_color\":\"#2563EB\",\"secondary_color\":\"#1E40AF\",\"custom_domain\":null,\"domain_verified\":false}', '{\"name\":\"Blueray Academy\",\"slug\":\"bluerayacademy\",\"subdomain\":null,\"email\":\"info@bluerayacademy.ng\",\"phone\":\"08012340095\",\"address\":\"Ruga Juli, Mararaba, Karu\",\"status\":\"active\",\"subscription_expires_at\":\"2027-06-19T00:00:00.000000Z\",\"motto\":null,\"logo_path\":null,\"theme_primary\":\"#071E45\",\"theme_accent\":\"#D79A21\",\"theme_sidebar\":\"#071E45\",\"primary_color\":\"#2563EB\",\"secondary_color\":\"#1E40AF\",\"custom_domain\":null,\"domain_verified\":false}', 'super_admin_tenant_edit', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:24:34', '2026-06-20 10:24:34'),
(18, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"dashboard\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:28:56', '2026-06-20 10:28:56'),
(19, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"profile.edit\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:29:18', '2026-06-20 10:29:18'),
(20, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"profile.password\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:31:02', '2026-06-20 10:31:02'),
(21, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"profile.edit\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:31:05', '2026-06-20 10:31:05'),
(22, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"dashboard\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:32:11', '2026-06-20 10:32:11'),
(23, 2, NULL, 'App\\Models\\User', 12, 'auth.logout', '[]', '{\"tenant_slug\":\"bluerayacademy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:32:20', '2026-06-20 10:32:20'),
(24, 2, NULL, 'App\\Models\\User', 12, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:32:29', '2026-06-20 10:32:29'),
(25, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":6,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:32:33', '2026-06-20 10:32:33'),
(26, 2, NULL, 'App\\Models\\User', 12, 'auth.logout', '[]', '{\"tenant_slug\":\"bluerayacademy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 10:33:40', '2026-06-20 10:33:40'),
(27, 2, NULL, 'App\\Models\\User', 12, 'auth.login.success', '[]', '{\"tenant_slug\":\"bluerayacademy\",\"login_surface\":\"tenant_slug\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:35:26', '2026-06-20 10:35:26'),
(28, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":6,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:35:27', '2026-06-20 10:35:27'),
(29, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":6,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:36:37', '2026-06-20 10:36:37'),
(30, 2, NULL, 'App\\Models\\User', 12, 'auth.logout', '[]', '{\"tenant_slug\":\"bluerayacademy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(31, 2, NULL, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:40:43', '2026-06-20 10:40:43'),
(32, 2, NULL, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login.submit\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:01', '2026-06-20 10:41:01'),
(33, 2, NULL, 'App\\Models\\User', 12, 'auth.login.success', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"login_surface\":\"tenant_host\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:02', '2026-06-20 10:41:02'),
(34, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:04', '2026-06-20 10:41:04'),
(35, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:20', '2026-06-20 10:41:20'),
(36, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:22', '2026-06-20 10:41:22'),
(37, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:22', '2026-06-20 10:41:22'),
(38, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:23', '2026-06-20 10:41:23'),
(39, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:25', '2026-06-20 10:41:25'),
(40, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:26', '2026-06-20 10:41:26'),
(41, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:30', '2026-06-20 10:41:30'),
(42, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:31', '2026-06-20 10:41:31'),
(43, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:33', '2026-06-20 10:41:33'),
(44, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:33', '2026-06-20 10:41:33'),
(45, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:35', '2026-06-20 10:41:35'),
(46, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:38', '2026-06-20 10:41:38'),
(47, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:39', '2026-06-20 10:41:39'),
(48, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:39', '2026-06-20 10:41:39'),
(49, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:41', '2026-06-20 10:41:41'),
(50, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:41', '2026-06-20 10:41:41'),
(51, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:42', '2026-06-20 10:41:42'),
(52, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:41:59', '2026-06-20 10:41:59'),
(53, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:03', '2026-06-20 10:42:03'),
(54, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:04', '2026-06-20 10:42:04'),
(55, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:15', '2026-06-20 10:42:15'),
(56, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:17', '2026-06-20 10:42:17'),
(57, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:17', '2026-06-20 10:42:17'),
(58, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:19', '2026-06-20 10:42:19'),
(59, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:25', '2026-06-20 10:42:25'),
(60, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:26', '2026-06-20 10:42:26'),
(61, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:26', '2026-06-20 10:42:26'),
(62, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:27', '2026-06-20 10:42:27'),
(63, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:30', '2026-06-20 10:42:30'),
(64, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 10:42:32', '2026-06-20 10:42:32'),
(65, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:17:49', '2026-06-20 12:17:49'),
(66, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.apply\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:06', '2026-06-20 12:18:06'),
(67, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:07', '2026-06-20 12:18:07'),
(68, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:09', '2026-06-20 12:18:09'),
(69, 2, NULL, 'App\\Models\\User', 12, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:23:05', '2026-06-20 12:23:05'),
(70, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":6,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:23:06', '2026-06-20 12:23:06'),
(71, 2, NULL, 'App\\Models\\User', 12, 'auth.logout', '[]', '{\"tenant_slug\":\"bluerayacademy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:23:56', '2026-06-20 12:23:56'),
(72, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:24:15', '2026-06-20 12:24:15'),
(73, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:25:23', '2026-06-20 12:25:23'),
(74, 1, NULL, 'App\\Models\\User', 5, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'invalid_credentials', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:25:53', '2026-06-20 12:25:53'),
(75, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:48:13', '2026-06-20 12:48:13'),
(76, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 12:48:28', '2026-06-20 12:48:28'),
(77, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:08:22', '2026-06-20 13:08:22'),
(78, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:08:35', '2026-06-20 13:08:35'),
(79, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:08:47', '2026-06-20 13:08:47'),
(80, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:18:15', '2026-06-20 13:18:15'),
(81, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:39:03', '2026-06-20 13:39:03'),
(82, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:39:04', '2026-06-20 13:39:04'),
(83, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:40:58', '2026-06-20 13:40:58'),
(84, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:40:59', '2026-06-20 13:40:59'),
(85, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:04', '2026-06-20 13:41:04'),
(86, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:05', '2026-06-20 13:41:05'),
(87, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:15', '2026-06-20 13:41:15'),
(88, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:16', '2026-06-20 13:41:16'),
(89, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:55', '2026-06-20 13:41:55'),
(90, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:41:56', '2026-06-20 13:41:56'),
(91, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:42:09', '2026-06-20 13:42:09'),
(92, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:42:11', '2026-06-20 13:42:11'),
(93, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:48:33', '2026-06-20 13:48:33'),
(94, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:48:44', '2026-06-20 13:48:44'),
(95, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:48:45', '2026-06-20 13:48:45'),
(96, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.apply\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:48:49', '2026-06-20 13:48:49'),
(97, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.apply\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:48:52', '2026-06-20 13:48:52'),
(98, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:13', '2026-06-20 13:49:13'),
(99, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:14', '2026-06-20 13:49:14'),
(100, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:16', '2026-06-20 13:49:16'),
(101, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:17', '2026-06-20 13:49:17'),
(102, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:23', '2026-06-20 13:49:23'),
(103, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:50', '2026-06-20 13:49:50'),
(104, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:51', '2026-06-20 13:49:51'),
(105, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:54', '2026-06-20 13:49:54'),
(106, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:55', '2026-06-20 13:49:55'),
(107, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:56', '2026-06-20 13:49:56'),
(108, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:57', '2026-06-20 13:49:57'),
(109, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:58', '2026-06-20 13:49:58'),
(110, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:49:59', '2026-06-20 13:49:59'),
(111, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:00', '2026-06-20 13:50:00'),
(112, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:01', '2026-06-20 13:50:01'),
(113, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:02', '2026-06-20 13:50:02'),
(114, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:04', '2026-06-20 13:50:04'),
(115, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:05', '2026-06-20 13:50:05'),
(116, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:06', '2026-06-20 13:50:06'),
(117, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:07', '2026-06-20 13:50:07'),
(118, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:08', '2026-06-20 13:50:08'),
(119, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:09', '2026-06-20 13:50:09'),
(120, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:16', '2026-06-20 13:50:16'),
(121, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:19', '2026-06-20 13:50:19'),
(122, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:20', '2026-06-20 13:50:20'),
(123, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:40', '2026-06-20 13:50:40'),
(124, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 13:50:40', '2026-06-20 13:50:40'),
(125, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 14:40:03', '2026-06-20 14:40:03'),
(126, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 14:44:20', '2026-06-20 14:44:20'),
(127, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 14:57:54', '2026-06-20 14:57:54'),
(128, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 14:58:06', '2026-06-20 14:58:06'),
(129, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 14:59:25', '2026-06-20 14:59:25'),
(130, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:00:10', '2026-06-20 15:00:10'),
(131, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:00:12', '2026-06-20 15:00:12'),
(132, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:00:17', '2026-06-20 15:00:17'),
(133, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.host.resolved', '[]', '{\"host\":\"bluerayacademy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:00:19', '2026-06-20 15:00:19'),
(134, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 16:22:03', '2026-06-20 16:22:03'),
(135, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 16:22:27', '2026-06-20 16:22:27'),
(136, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 16:23:09', '2026-06-20 16:23:09'),
(137, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 17:12:48', '2026-06-20 17:12:48'),
(138, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-20 18:57:42', '2026-06-20 18:57:42'),
(139, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:39:32', '2026-06-21 01:39:32'),
(140, 1, 2, 'App\\Models\\Term', 1, 'academic_term.closure_attempted', '[]', '{\"blocking\":[],\"warnings\":[\"2 termly summary record(s) still have pending promotion status.\"],\"information\":[\"Term closure preserves scores, attendance, CBT records, invoices, and enrolment history.\"]}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:56:38', '2026-06-21 01:56:38');
INSERT INTO `audit_logs` (`id`, `tenant_id`, `actor_user_id`, `auditable_type`, `auditable_id`, `action`, `old_values`, `new_values`, `reason`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(141, 1, 2, 'App\\Models\\Term', 1, 'academic_term.closed', '{\"is_current\":true}', '{\"is_current\":false}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:56:39', '2026-06-21 01:56:39'),
(142, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"academic-cycle.terms\",\"path\":\"academic-cycle\\/terms\",\"blocking_count\":1,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:56:40', '2026-06-21 01:56:40'),
(143, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:57:48', '2026-06-21 01:57:48'),
(144, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:57:59', '2026-06-21 01:57:59'),
(145, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"dashboard\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:58:13', '2026-06-21 01:58:13'),
(146, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"settings.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:59:01', '2026-06-21 01:59:01'),
(147, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"settings.update\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:59:54', '2026-06-21 01:59:54'),
(148, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"settings.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 01:59:57', '2026-06-21 01:59:57'),
(149, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"portal-accounts.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:00:01', '2026-06-21 02:00:01'),
(150, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"profile.edit\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:00:07', '2026-06-21 02:00:07'),
(151, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"settings.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:00:19', '2026-06-21 02:00:19'),
(152, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"payroll.index\",\"blocking_count\":6}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:01:56', '2026-06-21 02:01:56'),
(153, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"dashboard\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:02:11', '2026-06-21 02:02:11'),
(154, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"timetable.index\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:02:19', '2026-06-21 02:02:19'),
(155, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"timetable.teacher\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:02:30', '2026-06-21 02:02:30'),
(156, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"timetable.teacher\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:02:44', '2026-06-21 02:02:44'),
(157, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"skills.index\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:02:57', '2026-06-21 02:02:57'),
(158, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"cbt.banks\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:03:09', '2026-06-21 02:03:09'),
(159, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"skills.index\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:03:16', '2026-06-21 02:03:16'),
(160, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"cbt.banks\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:03:38', '2026-06-21 02:03:38'),
(161, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.impersonation_bypass', '[]', '{\"route\":\"portal-accounts.index\",\"blocking_count\":1}', 'verified_super_admin_impersonation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 02:03:59', '2026-06-21 02:03:59'),
(162, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"staff\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:01:00', '2026-06-21 07:01:00'),
(163, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:01:09', '2026-06-21 07:01:09'),
(164, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:01:27', '2026-06-21 07:01:27'),
(165, 2, NULL, 'App\\Models\\User', 12, 'auth.login.denied', '[]', '{\"login_surface\":\"staff\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:01:50', '2026-06-21 07:01:50'),
(166, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:10:05', '2026-06-21 07:10:05'),
(167, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:10:13', '2026-06-21 07:10:13'),
(168, 2, NULL, 'App\\Models\\User', 12, 'auth.login.success', '[]', '{\"tenant_slug\":\"bluerayacademy\",\"login_surface\":\"tenant_slug\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:12:10', '2026-06-21 07:12:10'),
(169, 2, 12, 'App\\Models\\Tenant', 2, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":7,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:12:11', '2026-06-21 07:12:11'),
(170, 2, NULL, 'App\\Models\\User', 12, 'auth.logout', '[]', '{\"tenant_slug\":\"bluerayacademy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:14:19', '2026-06-21 07:14:19'),
(171, 2, NULL, 'App\\Models\\User', 12, 'auth.login.denied', '[]', '{\"login_surface\":\"staff\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:15:13', '2026-06-21 07:15:13'),
(172, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:30:42', '2026-06-21 07:30:42'),
(173, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:49:48', '2026-06-21 07:49:48'),
(174, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:49:51', '2026-06-21 07:49:51'),
(175, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"settings.index\",\"path\":\"settings\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:25', '2026-06-21 07:57:25'),
(176, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:32', '2026-06-21 07:57:32'),
(177, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:36', '2026-06-21 07:57:36'),
(178, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:39', '2026-06-21 07:57:39'),
(179, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:43', '2026-06-21 07:57:43'),
(180, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:48', '2026-06-21 07:57:48'),
(181, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"profile.edit\",\"path\":\"profile\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:57:58', '2026-06-21 07:57:58'),
(182, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"profile.edit\",\"path\":\"profile\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:58:01', '2026-06-21 07:58:01'),
(183, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"exports.index\",\"path\":\"exports\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:58:07', '2026-06-21 07:58:07'),
(184, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 07:58:13', '2026-06-21 07:58:13'),
(185, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:58:34', '2026-06-21 07:58:34'),
(186, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:58:45', '2026-06-21 07:58:45'),
(187, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:58:47', '2026-06-21 07:58:47'),
(188, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"portal-accounts.index\",\"path\":\"portal-accounts\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:58:52', '2026-06-21 07:58:52'),
(189, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"profile.edit\",\"path\":\"profile\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 07:58:58', '2026-06-21 07:58:58'),
(190, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"exports.index\",\"path\":\"exports\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:00:58', '2026-06-21 08:00:58'),
(191, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"risk.index\",\"path\":\"risk\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:01:06', '2026-06-21 08:01:06'),
(192, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"analytics.financial\",\"path\":\"analytics\\/financial\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:01:11', '2026-06-21 08:01:11'),
(193, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"analytics.index\",\"path\":\"analytics\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:01:16', '2026-06-21 08:01:16'),
(194, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"sms.index\",\"path\":\"sms\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:02:18', '2026-06-21 08:02:18'),
(195, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"dashboard\",\"path\":\"\\/\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:02:27', '2026-06-21 08:02:27'),
(196, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"analytics.financial\",\"path\":\"analytics\\/financial\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:02:32', '2026-06-21 08:02:32'),
(197, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.blocked', '[]', '{\"route\":\"notifications.index\",\"path\":\"notifications\",\"blocking_count\":2,\"next_step\":\"tenant.onboarding.session\"}', 'onboarding_incomplete', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 08:02:38', '2026-06-21 08:02:38'),
(198, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:06', '2026-06-21 10:36:06'),
(199, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:39:00', '2026-06-21 10:39:00'),
(200, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-21 10:39:37', '2026-06-21 10:39:37'),
(201, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:50:29', '2026-06-21 10:50:29'),
(202, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 10:54:05', '2026-06-21 10:54:05'),
(203, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 10:54:14', '2026-06-21 10:54:14'),
(204, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"tenant_slug\":\"greenfield-academy\",\"login_surface\":\"tenant_slug\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:56:35', '2026-06-21 10:56:35'),
(205, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 12:39:17', '2026-06-21 12:39:17'),
(206, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 12:39:46', '2026-06-21 12:39:46'),
(207, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 12:46:29', '2026-06-21 12:46:29'),
(208, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 12:48:07', '2026-06-21 12:48:07'),
(209, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 12:48:43', '2026-06-21 12:48:43'),
(210, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 12:49:06', '2026-06-21 12:49:06'),
(211, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 12:59:22', '2026-06-21 12:59:22'),
(212, 1, NULL, 'App\\Models\\User', 5, 'auth.login.denied', '[]', '{\"login_surface\":\"student\"}', 'invalid_credentials', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 12:59:47', '2026-06-21 12:59:47'),
(213, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 13:00:45', '2026-06-21 13:00:45'),
(214, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.landing\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:02:37', '2026-06-21 13:02:37'),
(215, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:02:52', '2026-06-21 13:02:52'),
(216, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login.submit\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:07', '2026-06-21 13:03:07'),
(217, 1, NULL, 'App\\Models\\Tenant', 1, 'auth.login.denied', '[]', '{\"login_id_hash\":\"370a8bd18813826beca45283035d67e998cbf0dac8118f493b1de1013837b22a\",\"host\":\"greenfield-academy.educore.test\",\"login_surface\":\"tenant_host\"}', 'invalid_credentials_or_tenant_mismatch', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:07', '2026-06-21 13:03:07'),
(218, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:08', '2026-06-21 13:03:08'),
(219, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login.submit\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:54', '2026-06-21 13:03:54'),
(220, 1, NULL, 'App\\Models\\Tenant', 1, 'auth.login.denied', '[]', '{\"login_id_hash\":\"370a8bd18813826beca45283035d67e998cbf0dac8118f493b1de1013837b22a\",\"host\":\"greenfield-academy.educore.test\",\"login_surface\":\"tenant_host\"}', 'invalid_credentials_or_tenant_mismatch', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:54', '2026-06-21 13:03:54'),
(221, 1, NULL, 'App\\Models\\Tenant', 1, 'tenant.host.resolved', '[]', '{\"host\":\"greenfield-academy.educore.test\",\"type\":\"local_subdomain\",\"route\":\"tenant.host.login\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 13:03:56', '2026-06-21 13:03:56'),
(222, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 14:49:39', '2026-06-21 14:49:39'),
(223, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 17:50:13', '2026-06-21 17:50:13'),
(224, 1, 2, 'App\\Models\\Tenant', 1, 'tenant.onboarding.step_completed', '[]', '{\"step\":\"academic-session\",\"old_values\":[],\"new_values\":{\"session_name\":\"2025\\/2026\",\"term_name\":\"First\",\"term_start_date\":\"2026-04-20\",\"term_end_date\":\"2026-07-24\"}}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 17:52:53', '2026-06-21 17:52:53'),
(225, 1, 2, 'App\\Models\\Student', 4, 'student.reactivated', '{\"status\":\"left\",\"current_enrollment_ids\":[]}', '{\"status\":\"active\",\"status_history_id\":2,\"new_enrollment_id\":4,\"synced_subjects\":1,\"current_class_arm_id\":14}', 'Readmission', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 17:54:37', '2026-06-21 17:54:37'),
(226, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 17:59:03', '2026-06-21 17:59:03'),
(227, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 17:59:33', '2026-06-21 17:59:33'),
(228, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:09:39', '2026-06-21 18:09:39'),
(229, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"staff\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:12:40', '2026-06-21 18:12:40'),
(230, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:12:48', '2026-06-21 18:12:48'),
(231, 1, 2, 'App\\Models\\Term', 4, 'academic_term.closure_attempted', '[]', '{\"blocking\":[],\"warnings\":[],\"information\":[\"Term closure preserves scores, attendance, CBT records, invoices, and enrolment history.\"]}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:14:39', '2026-06-21 18:14:39'),
(232, 1, 2, 'App\\Models\\Term', 4, 'academic_term.closed', '{\"is_current\":true}', '{\"is_current\":false}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:14:39', '2026-06-21 18:14:39'),
(233, 1, 2, 'App\\Models\\Term', 1, 'academic_term.activated', '{\"previous_current_term_ids\":[]}', '{\"term_id\":1,\"session_id\":1}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:14:46', '2026-06-21 18:14:46'),
(234, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:26:33', '2026-06-21 18:26:33'),
(235, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 18:27:37', '2026-06-21 18:27:37'),
(236, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:07:04', '2026-06-21 19:07:04'),
(237, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:07:32', '2026-06-21 19:07:32'),
(238, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:12:38', '2026-06-21 19:12:38'),
(239, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:12:55', '2026-06-21 19:12:55'),
(240, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:22:42', '2026-06-21 19:22:42'),
(241, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:22:52', '2026-06-21 19:22:52'),
(242, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:23:14', '2026-06-21 19:23:14'),
(243, 1, NULL, 'App\\Models\\User', 6, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:23:20', '2026-06-21 19:23:20'),
(244, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:23:27', '2026-06-21 19:23:27'),
(245, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:24:10', '2026-06-21 19:24:10'),
(246, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:24:24', '2026-06-21 19:24:24'),
(247, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:58:16', '2026-06-21 19:58:16'),
(248, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-21 19:58:40', '2026-06-21 19:58:40'),
(249, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 01:56:48', '2026-06-22 01:56:48'),
(250, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:21:35', '2026-06-22 02:21:35'),
(251, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:21:49', '2026-06-22 02:21:49'),
(252, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:35:14', '2026-06-22 02:35:14'),
(253, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"staff\"}', 'wrong_surface', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:46:32', '2026-06-22 02:46:32'),
(254, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:46:38', '2026-06-22 02:46:38'),
(255, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:48:00', '2026-06-22 02:48:00'),
(256, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 02:48:11', '2026-06-22 02:48:11'),
(257, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:00:00', '2026-06-22 03:00:00'),
(258, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:00:14', '2026-06-22 03:00:14'),
(259, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:01:02', '2026-06-22 03:01:02'),
(260, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:01:12', '2026-06-22 03:01:12'),
(261, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:01:52', '2026-06-22 03:01:52'),
(262, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:02:00', '2026-06-22 03:02:00'),
(263, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:02:50', '2026-06-22 03:02:50'),
(264, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:02:59', '2026-06-22 03:02:59'),
(265, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:59:45', '2026-06-22 03:59:45'),
(266, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 03:59:56', '2026-06-22 03:59:56'),
(267, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 04:01:06', '2026-06-22 04:01:06'),
(268, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 04:01:21', '2026-06-22 04:01:21'),
(269, 1, NULL, 'App\\Models\\User', 4, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 06:21:01', '2026-06-22 06:21:01'),
(270, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 06:21:09', '2026-06-22 06:21:09'),
(271, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 08:22:37', '2026-06-22 08:22:37'),
(272, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 08:22:50', '2026-06-22 08:22:50'),
(273, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 13:57:31', '2026-06-22 13:57:31'),
(274, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 14:02:08', '2026-06-22 14:02:08'),
(275, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 14:02:16', '2026-06-22 14:02:16'),
(276, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 14:02:22', '2026-06-22 14:02:22'),
(277, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 14:28:33', '2026-06-22 14:28:33'),
(278, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 14:28:53', '2026-06-22 14:28:53'),
(279, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 18:47:06', '2026-06-22 18:47:06'),
(280, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-22 22:48:42', '2026-06-22 22:48:42'),
(281, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 04:35:17', '2026-06-23 04:35:17'),
(282, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 04:45:31', '2026-06-23 04:45:31'),
(283, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 04:45:48', '2026-06-23 04:45:48'),
(284, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 04:46:42', '2026-06-23 04:46:42'),
(285, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 04:46:55', '2026-06-23 04:46:55'),
(286, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:05:11', '2026-06-23 06:05:11'),
(287, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:05:27', '2026-06-23 06:05:27'),
(288, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:06:46', '2026-06-23 06:06:46'),
(289, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:08:03', '2026-06-23 06:08:03'),
(290, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:11:02', '2026-06-23 06:11:02'),
(291, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:11:19', '2026-06-23 06:11:19');
INSERT INTO `audit_logs` (`id`, `tenant_id`, `actor_user_id`, `auditable_type`, `auditable_id`, `action`, `old_values`, `new_values`, `reason`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(292, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:13:18', '2026-06-23 06:13:18'),
(293, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:13:29', '2026-06-23 06:13:29'),
(294, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 06:24:33', '2026-06-23 06:24:33'),
(295, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 10:05:33', '2026-06-23 10:05:33'),
(296, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 10:30:40', '2026-06-23 10:30:40'),
(297, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 10:31:09', '2026-06-23 10:31:09'),
(298, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 10:54:30', '2026-06-23 10:54:30'),
(299, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 10:54:44', '2026-06-23 10:54:44'),
(300, 1, 2, 'App\\Models\\AcademicSession', 2, 'academic_session.created', '[]', '{\"name\":\"2026\\/2027\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 11:16:08', '2026-06-23 11:16:08'),
(301, 1, 2, 'App\\Models\\Term', 5, 'academic_term.created', '[]', '{\"session_id\":2,\"name\":\"1st Term\",\"start_date\":\"2026-09-12\",\"end_date\":\"2026-12-13\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 11:16:50', '2026-06-23 11:16:50'),
(302, 1, 2, 'App\\Models\\AcademicSession', 1, 'academic_rollover.started', '[]', '{\"source_session_id\":1,\"target_session_id\":2}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 11:17:38', '2026-06-23 11:17:38'),
(303, 1, 2, 'App\\Models\\AcademicSession', 1, 'academic_rollover.completed', '[]', '{\"counts\":{\"inspected\":1,\"ready\":0,\"blocked\":0,\"skipped\":1,\"promoted\":0,\"repeated\":0,\"graduated\":0,\"failed\":0}}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 11:17:38', '2026-06-23 11:17:38'),
(304, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 14:34:42', '2026-06-23 14:34:42'),
(305, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 14:34:53', '2026-06-23 14:34:53'),
(306, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 16:47:12', '2026-06-23 16:47:12'),
(307, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 16:47:26', '2026-06-23 16:47:26'),
(308, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 16:47:45', '2026-06-23 16:47:45'),
(309, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 17:00:00', '2026-06-23 17:00:00'),
(310, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 17:00:17', '2026-06-23 17:00:17'),
(311, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 17:10:54', '2026-06-23 17:10:54'),
(312, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 17:12:38', '2026-06-23 17:12:38'),
(313, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 17:14:46', '2026-06-23 17:14:46'),
(314, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:23:00', '2026-06-23 18:23:00'),
(315, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:28:37', '2026-06-23 18:28:37'),
(316, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:29:33', '2026-06-23 18:29:33'),
(317, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:30:06', '2026-06-23 18:30:06'),
(318, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 18:38:13', '2026-06-23 18:38:13'),
(319, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:42:20', '2026-06-23 18:42:20'),
(320, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:42:38', '2026-06-23 18:42:38'),
(321, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:42:49', '2026-06-23 18:42:49'),
(322, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:45:27', '2026-06-23 18:45:27'),
(323, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 18:45:39', '2026-06-23 18:45:39'),
(324, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:29:59', '2026-06-23 19:29:59'),
(325, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:30:08', '2026-06-23 19:30:08'),
(326, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:33:41', '2026-06-23 19:33:41'),
(327, 1, NULL, 'App\\Models\\User', 2, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'non_platform_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:33:48', '2026-06-23 19:33:48'),
(328, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:33:53', '2026-06-23 19:33:53'),
(329, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:35:09', '2026-06-23 19:35:09'),
(330, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 19:35:16', '2026-06-23 19:35:16'),
(331, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.4249', '2026-06-23 20:26:52', '2026-06-23 20:26:52'),
(332, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.4249', '2026-06-23 20:31:17', '2026-06-23 20:31:17'),
(333, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.4249', '2026-06-23 20:32:29', '2026-06-23 20:32:29'),
(334, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.4249', '2026-06-23 20:33:18', '2026-06-23 20:33:18'),
(335, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 21:33:07', '2026-06-23 21:33:07'),
(336, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 21:33:17', '2026-06-23 21:33:17'),
(337, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 21:43:39', '2026-06-23 21:43:39'),
(338, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-23 21:44:21', '2026-06-23 21:44:21'),
(339, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-23 21:44:55', '2026-06-23 21:44:55'),
(340, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 01:33:38', '2026-06-24 01:33:38'),
(341, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 01:33:52', '2026-06-24 01:33:52'),
(342, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 01:34:08', '2026-06-24 01:34:08'),
(343, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:27:51', '2026-06-24 03:27:51'),
(344, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 03:32:07', '2026-06-24 03:32:07'),
(345, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:32:41', '2026-06-24 03:32:41'),
(346, 1, NULL, 'App\\Models\\User', 4, 'auth.login.denied', '[]', '{\"login_surface\":\"student\"}', 'wrong_surface', '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:35:15', '2026-06-24 03:35:15'),
(347, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:35:53', '2026-06-24 03:35:53'),
(348, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:38:56', '2026-06-24 03:38:56'),
(349, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 03:39:34', '2026-06-24 03:39:34'),
(350, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 03:56:00', '2026-06-24 03:56:00'),
(351, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 04:20:29', '2026-06-24 04:20:29'),
(352, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 04:21:27', '2026-06-24 04:21:27'),
(353, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 04:25:45', '2026-06-24 04:25:45'),
(354, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 04:26:01', '2026-06-24 04:26:01'),
(355, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 04:28:07', '2026-06-24 04:28:07'),
(356, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 08:21:16', '2026-06-24 08:21:16'),
(357, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:22:54', '2026-06-24 08:22:54'),
(358, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:26:25', '2026-06-24 08:26:25'),
(359, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:36:19', '2026-06-24 08:36:19'),
(360, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:51:07', '2026-06-24 08:51:07'),
(361, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 08:51:25', '2026-06-24 08:51:25'),
(362, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:55:23', '2026-06-24 08:55:23'),
(363, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 08:55:49', '2026-06-24 08:55:49'),
(364, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 09:10:05', '2026-06-24 09:10:05'),
(365, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 09:10:46', '2026-06-24 09:10:46'),
(366, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 09:11:00', '2026-06-24 09:11:00'),
(367, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:29:33', '2026-06-24 09:29:33'),
(368, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:29:56', '2026-06-24 09:29:56'),
(369, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:35:40', '2026-06-24 09:35:40'),
(370, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:36:19', '2026-06-24 09:36:19'),
(371, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:37:41', '2026-06-24 09:37:41'),
(372, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:38:22', '2026-06-24 09:38:22'),
(373, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:40:13', '2026-06-24 09:40:13'),
(374, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:41:09', '2026-06-24 09:41:09'),
(375, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:41:23', '2026-06-24 09:41:23'),
(376, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:43:31', '2026-06-24 09:43:31'),
(377, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-24 09:43:47', '2026-06-24 09:43:47'),
(378, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 09:45:17', '2026-06-24 09:45:17'),
(379, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 14:35:37', '2026-06-24 14:35:37'),
(380, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-24 20:15:50', '2026-06-24 20:15:50'),
(381, 1, NULL, 'App\\Models\\User', 4, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '172.17.187.154', 'Mozilla/5.0 (Linux; Android 15; TECNO KM4k Build/AP3A.240905.015.A2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36', '2026-06-25 05:52:36', '2026-06-25 05:52:36'),
(382, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 15; TECNO KM4k Build/AP3A.240905.015.A2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36', '2026-06-25 05:52:51', '2026-06-25 05:52:51'),
(383, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"global\"}', 'invalid_credentials', '172.17.187.154', 'Mozilla/5.0 (Linux; Android 15; TECNO KM4k Build/AP3A.240905.015.A2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36', '2026-06-25 06:22:26', '2026-06-25 06:22:26'),
(384, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 15; TECNO KM4k Build/AP3A.240905.015.A2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36', '2026-06-25 06:23:03', '2026-06-25 06:23:03'),
(385, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 07:54:20', '2026-06-25 07:54:20'),
(386, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 15:52:29', '2026-06-25 15:52:29'),
(387, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 15:53:26', '2026-06-25 15:53:26'),
(388, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 16:14:14', '2026-06-25 16:14:14'),
(389, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:19:22', '2026-06-25 16:19:22'),
(390, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:19:50', '2026-06-25 16:19:50'),
(391, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:30:57', '2026-06-25 16:30:57'),
(392, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 16:31:28', '2026-06-25 16:31:28'),
(393, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:32:09', '2026-06-25 16:32:09'),
(394, 1, NULL, 'App\\Models\\User', 6, 'auth.login.success', '[]', '{\"login_surface\":\"student\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 16:32:59', '2026-06-25 16:32:59'),
(395, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:38:43', '2026-06-25 16:38:43'),
(396, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:38:57', '2026-06-25 16:38:57'),
(397, 1, NULL, 'App\\Models\\User', 6, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 16:40:59', '2026-06-25 16:40:59'),
(398, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:41:32', '2026-06-25 16:41:32'),
(399, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:42:37', '2026-06-25 16:42:37'),
(400, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:43:42', '2026-06-25 16:43:42'),
(401, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:45:13', '2026-06-25 16:45:13'),
(402, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 16:54:20', '2026-06-25 16:54:20'),
(403, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 19:54:09', '2026-06-25 19:54:09'),
(404, NULL, NULL, 'App\\Models\\User', 1, 'auth.logout', '[]', '{\"tenant_slug\":null}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 19:54:21', '2026-06-25 19:54:21'),
(405, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 19:54:57', '2026-06-25 19:54:57'),
(406, 1, NULL, 'App\\Models\\User', 7, 'auth.login.success', '[]', '{\"login_surface\":\"parent\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 20:01:41', '2026-06-25 20:01:41'),
(407, 1, NULL, 'App\\Models\\User', 7, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 20:03:35', '2026-06-25 20:03:35'),
(408, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 20:03:47', '2026-06-25 20:03:47'),
(409, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 20:04:08', '2026-06-25 20:04:08'),
(410, 1, NULL, 'App\\Models\\User', 7, 'auth.login.success', '[]', '{\"login_surface\":\"parent\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 20:04:43', '2026-06-25 20:04:43'),
(411, 1, NULL, 'App\\Models\\User', 7, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 20:08:23', '2026-06-25 20:08:23'),
(412, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 20:08:34', '2026-06-25 20:08:34'),
(413, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 21:02:25', '2026-06-25 21:02:25'),
(414, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 21:22:36', '2026-06-25 21:22:36'),
(415, 1, NULL, 'App\\Models\\User', 7, 'auth.login.success', '[]', '{\"login_surface\":\"parent\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 21:22:53', '2026-06-25 21:22:53'),
(416, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 21:24:28', '2026-06-25 21:24:28'),
(417, 1, NULL, 'App\\Models\\User', 7, 'auth.login.success', '[]', '{\"login_surface\":\"parent\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 21:24:42', '2026-06-25 21:24:42'),
(418, 1, NULL, 'App\\Models\\User', 7, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 22:20:33', '2026-06-25 22:20:33'),
(419, 1, NULL, 'App\\Models\\User', 7, 'auth.login.denied', '[]', '{\"login_surface\":\"admin\"}', 'wrong_surface', '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 22:20:46', '2026-06-25 22:20:46'),
(420, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 22:20:57', '2026-06-25 22:20:57'),
(421, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 22:21:21', '2026-06-25 22:21:21'),
(422, NULL, NULL, 'App\\Models\\User', 1, 'auth.login.success', '[]', '{\"login_surface\":\"global\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 22:21:29', '2026-06-25 22:21:29'),
(423, 1, NULL, 'App\\Models\\User', 7, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 22:30:39', '2026-06-25 22:30:39'),
(424, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 22:31:16', '2026-06-25 22:31:16'),
(425, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 23:21:48', '2026-06-25 23:21:48'),
(426, 1, NULL, 'App\\Models\\User', 7, 'auth.login.success', '[]', '{\"login_surface\":\"parent\"}', NULL, '10.146.183.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-25 23:22:01', '2026-06-25 23:22:01'),
(427, 1, NULL, 'App\\Models\\User', 2, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:24:43', '2026-06-25 23:24:43'),
(428, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:24:56', '2026-06-25 23:24:56'),
(429, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:25:06', '2026-06-25 23:25:06'),
(430, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:27:42', '2026-06-25 23:27:42'),
(431, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:27:52', '2026-06-25 23:27:52'),
(432, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:28:22', '2026-06-25 23:28:22'),
(433, 1, NULL, 'App\\Models\\User', 4, 'auth.login.success', '[]', '{\"login_surface\":\"staff\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:28:36', '2026-06-25 23:28:36'),
(434, 1, NULL, 'App\\Models\\User', 4, 'auth.logout', '[]', '{\"tenant_slug\":\"greenfield-academy\"}', NULL, '10.146.183.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '2026-06-25 23:29:30', '2026-06-25 23:29:30'),
(435, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:07:49', '2026-06-26 22:07:49'),
(436, 1, NULL, 'App\\Models\\User', 2, 'auth.login.success', '[]', '{\"login_surface\":\"admin\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 05:17:02', '2026-06-27 05:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'event',
  `color` varchar(20) NOT NULL DEFAULT '#2563EB',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cbt_exams`
--

CREATE TABLE `cbt_exams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `question_bank_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `total_questions` int(11) NOT NULL,
  `section_objective_count` int(11) NOT NULL DEFAULT 0,
  `section_objective_marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `section_theory_count` int(11) NOT NULL DEFAULT 0,
  `section_theory_marks` decimal(5,2) NOT NULL DEFAULT 5.00,
  `total_marks` decimal(6,2) NOT NULL DEFAULT 100.00,
  `scheduled_start` timestamp NULL DEFAULT NULL,
  `scheduled_end` timestamp NULL DEFAULT NULL,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('draft','published','active','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cbt_exams`
--

INSERT INTO `cbt_exams` (`id`, `tenant_id`, `question_bank_id`, `term_id`, `class_arm_id`, `title`, `duration_minutes`, `total_questions`, `section_objective_count`, `section_objective_marks`, `section_theory_count`, `section_theory_marks`, `total_marks`, `scheduled_start`, `scheduled_end`, `shuffle_questions`, `shuffle_options`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 1, 14, 'BIOLOGY', 60, 50, 0, 1.00, 0, 5.00, 100.00, '2026-06-19 03:00:00', '2026-06-25 03:00:00', 1, 1, 'closed', '2026-06-19 02:55:13', '2026-06-21 19:19:59'),
(3, 1, 2, 2, 14, '2nd Term Exam', 60, 20, 0, 1.00, 0, 5.00, 100.00, '2026-06-21 20:19:00', '2026-06-23 20:19:00', 1, 1, 'published', '2026-06-21 19:19:48', '2026-06-21 19:23:55');

-- --------------------------------------------------------

--
-- Table structure for table `cbt_questions`
--

CREATE TABLE `cbt_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `question_bank_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('mcq','essay','short_answer','fill_blank','true_false') NOT NULL DEFAULT 'mcq',
  `question_text` text NOT NULL,
  `option_a` text DEFAULT NULL,
  `option_b` text DEFAULT NULL,
  `option_c` text DEFAULT NULL,
  `option_d` text DEFAULT NULL,
  `question_html` longtext DEFAULT NULL,
  `question_image_path` varchar(255) DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_option` tinyint(3) UNSIGNED DEFAULT NULL,
  `explanation` varchar(255) DEFAULT NULL,
  `difficulty` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `word_limit` int(11) DEFAULT NULL,
  `model_answer` text DEFAULT NULL,
  `correct_answer_letter` varchar(1) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cbt_questions`
--

INSERT INTO `cbt_questions` (`id`, `tenant_id`, `question_bank_id`, `type`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `question_html`, `question_image_path`, `options`, `correct_option`, `explanation`, `difficulty`, `created_at`, `updated_at`, `image_path`, `marks`, `word_limit`, `model_answer`, `correct_answer_letter`, `deleted_at`) VALUES
(355, 1, 2, 'mcq', 'The branch of Biology that deals with the classification of organisms is called', 'Morphology', 'Taxonomy', 'Ecology', 'Physiology', NULL, NULL, NULL, NULL, 'Taxonomy is the branch of Biology concerned with naming and classifying organisms.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(356, 1, 2, 'mcq', 'The five-kingdom classification system was proposed by', 'Aristotle', 'Whittaker', 'Darwin', 'Linnaeus', NULL, NULL, NULL, NULL, 'R. H. Whittaker proposed the five-kingdom classification system.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(357, 1, 2, 'mcq', 'Which of the following organisms is a prokaryote?', 'Amoeba', 'Bacteria', 'Euglena', 'Chlamydomonas', NULL, NULL, NULL, NULL, 'Bacteria are prokaryotes because they lack a true nucleus and membrane-bound organelles.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(358, 1, 2, 'mcq', 'The binomial system of naming organisms was introduced by', 'Mendel', 'Linnaeus', 'Lamarck', 'Whittaker', NULL, NULL, NULL, NULL, 'Carolus Linnaeus introduced the binomial system of nomenclature.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(359, 1, 2, 'mcq', 'In the binomial system, the first name represents the', 'Class', 'Family', 'Genus', 'Species', NULL, NULL, NULL, NULL, 'The first part of a scientific name is the genus name.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(360, 1, 2, 'mcq', 'Which of the following groups contains only vertebrates?', 'Pisces, Amphibia, Reptilia, Aves, Mammalia', 'Protozoa, Mollusca, Aves, Mammalia', 'Arthropoda, Pisces, Amphibia, Annelida', 'Coelenterata, Reptilia, Aves, Mammalia', NULL, NULL, NULL, NULL, 'Pisces, Amphibia, Reptilia, Aves and Mammalia are all vertebrate classes.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(361, 1, 2, 'mcq', 'Which of the following organisms is not a member of the kingdom Protista?', 'Paramecium', 'Amoeba', 'Chlamydomonas', 'Mushroom', NULL, NULL, NULL, NULL, 'Mushroom belongs to kingdom Fungi, not Protista.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'd', NULL),
(362, 1, 2, 'mcq', 'Which of the following is NOT a characteristic of living things?', 'Respiration', 'Photosynthesis', 'Reproduction', NULL, NULL, NULL, NULL, NULL, 'Photosynthesis is not a universal characteristic of all living things; many organisms do not photosynthesize.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(363, 1, 2, 'mcq', 'The organelle responsible for energy production in the cell is the', 'Ribosome', 'Nucleus', 'Mitochondrion', 'Lysosome', NULL, NULL, NULL, NULL, 'The mitochondrion releases energy during aerobic respiration.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(364, 1, 2, 'mcq', 'The cell wall of plants is mainly composed of', 'Protein', 'Cellulose', 'Lignin', 'Pectin', NULL, NULL, NULL, NULL, 'The primary plant cell wall is mainly composed of cellulose.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(365, 1, 2, 'mcq', 'The organelle responsible for protein synthesis is the', 'Ribosome', 'Golgi body', 'Chloroplast', 'Vacuole', NULL, NULL, NULL, NULL, 'Ribosomes are the sites of protein synthesis.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(366, 1, 2, 'mcq', 'Which of the following is absent in animal cells?', 'Mitochondrion', 'Chloroplast', 'Nucleus', 'Ribosome', NULL, NULL, NULL, NULL, 'Animal cells do not contain chloroplasts.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(367, 1, 2, 'mcq', 'Which of the following is the control center of the cell?', 'Cytoplasm', 'Nucleus', 'Mitochondrion', 'Golgi body', NULL, NULL, NULL, NULL, 'The nucleus controls cell activities because it contains genetic material.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(368, 1, 2, 'mcq', 'The cell theory was proposed by', 'Watson and Crick', 'Schleiden and Schwann', 'Darwin and Lamarck', 'Hooke and Pasteur', NULL, NULL, NULL, NULL, 'Schleiden and Schwann are credited with proposing the cell theory.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(369, 1, 2, 'mcq', 'The powerhouse of the cell is the', 'Ribosome', 'Nucleus', 'Mitochondrion', 'Vacuole', NULL, NULL, NULL, NULL, 'The mitochondrion is called the powerhouse because it produces ATP.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(370, 1, 2, 'mcq', 'Which of the following cell structures is selectively permeable?', 'Nucleus', 'Cytoplasm', 'Cell membrane', 'Cell wall', NULL, NULL, NULL, NULL, 'The cell membrane is selectively permeable and controls movement of substances.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(371, 1, 2, 'mcq', 'The main conducting tissue in plants responsible for transporting water is', 'Phloem', 'Xylem', 'Cambium', 'Cortex', NULL, NULL, NULL, NULL, 'Xylem transports water and mineral salts from roots to other parts of the plant.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(372, 1, 2, 'mcq', 'Food manufactured in the leaves of plants is transported through the', 'Xylem', 'Phloem', 'Stomata', 'Cambium', NULL, NULL, NULL, NULL, 'Phloem transports manufactured food from leaves to other parts of the plant.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(373, 1, 2, 'mcq', 'Which of the following is a characteristic of xylem vessels?', 'Living cells with sieve plates', 'Dead and hollow cells', 'Cells with nuclei and cytoplasm', 'Transport only food substances', NULL, NULL, NULL, NULL, 'Mature xylem vessels are dead, hollow and adapted for water transport.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(374, 1, 2, 'mcq', 'The red coloration of blood is due to the presence of', 'Haemoglobin', 'Chlorophyll', 'Plasma', 'Platelets', NULL, NULL, NULL, NULL, 'Haemoglobin in red blood cells gives blood its red colour.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(375, 1, 2, 'mcq', 'Which of the following blood cells is responsible for fighting infections?', 'Red blood cell', 'White blood cell', 'Platelets', 'Plasma', NULL, NULL, NULL, NULL, 'White blood cells protect the body against infection.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(376, 1, 2, 'mcq', 'The chamber of the mammalian heart that pumps blood to the lungs is the', 'Left atrium', 'Right atrium', 'Left ventricle', 'Right ventricle', NULL, NULL, NULL, NULL, 'The right ventricle pumps deoxygenated blood to the lungs through the pulmonary artery.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'd', NULL),
(377, 1, 2, 'mcq', 'Which of the following blood vessels carries oxygenated blood?', 'Pulmonary artery', 'Pulmonary vein', 'Vena cava', 'Right atrium', NULL, NULL, NULL, NULL, 'The pulmonary vein carries oxygenated blood from the lungs to the heart.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(378, 1, 2, 'mcq', 'In plants, transpiration mainly takes place through the', 'Cuticle', 'Stomata', 'Lenticels', 'Epidermis', NULL, NULL, NULL, NULL, 'Most transpiration occurs through stomata on leaves.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(379, 1, 2, 'mcq', 'The tissue responsible for transport of manufactured food in plants is', 'Phloem', 'Xylem', 'Cambium', 'Cortex', NULL, NULL, NULL, NULL, 'Phloem transports manufactured food such as sugars.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(380, 1, 2, 'mcq', 'Which of the following prevents backflow of blood in veins?', 'Valves', 'Arterioles', 'Capillaries', 'Walls of the veins', NULL, NULL, NULL, NULL, 'Valves in veins prevent the backward flow of blood.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(381, 1, 2, 'mcq', 'Which of the following organs regulates the amount of water in the human body?', 'Liver', 'Kidney', 'Pancreas', 'Lungs', NULL, NULL, NULL, NULL, 'The kidney regulates body water balance through urine formation.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(382, 1, 2, 'mcq', 'The hormone responsible for controlling the level of sugar in the blood is', 'Insulin', 'Adrenaline', 'Thyroxine', 'Testosterone', NULL, NULL, NULL, NULL, 'Insulin lowers blood glucose level by promoting uptake and storage of glucose.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(383, 1, 2, 'mcq', 'The part of the brain that regulates body temperature is the', 'Cerebellum', 'Medulla', 'Hypothalamus', 'Cerebrum', NULL, NULL, NULL, NULL, 'The hypothalamus is the body temperature regulation centre.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(384, 1, 2, 'mcq', 'In mammals, shivering during cold weather is a mechanism to', 'Produce sweat', 'Generate heat', 'Reduce body temperature', 'Store glycogen', NULL, NULL, NULL, NULL, 'Shivering involves rapid muscle contraction that generates heat.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(385, 1, 2, 'mcq', 'Which of the following is NOT a component of homeostasis?', 'Temperature regulation', 'Blood sugar regulation', 'Excretion of waste', 'Photosynthesis', NULL, NULL, NULL, NULL, 'Photosynthesis is a plant food-making process, not a homeostatic control process.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'd', NULL),
(386, 1, 2, 'mcq', 'The antidiuretic hormone (ADH) controls the', 'Rate of digestion', 'Reabsorption of water in the kidney tubules', 'Production of bile in the liver', 'Breakdown of glycogen in the muscles', NULL, NULL, NULL, NULL, 'ADH increases water reabsorption in the kidney tubules.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(387, 1, 2, 'mcq', 'Which of these is an example of negative feedback?', 'Sweating during heat', 'Increase in heartbeat after exercise', 'Milk ejection during suckling', 'Ovulation in female mammals', NULL, NULL, NULL, NULL, 'Sweating during heat reduces body temperature back towards normal.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(388, 1, 2, 'mcq', 'Homeostasis in humans is mainly maintained through the activities of the', 'Endocrine and nervous systems', 'Excretory and digestive systems', 'Skeletal and muscular systems', 'Circulatory and respiratory systems', NULL, NULL, NULL, NULL, 'The nervous and endocrine systems coordinate body responses that maintain homeostasis.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(389, 1, 2, 'mcq', 'Which of the following hormones promotes seed germination in plants?', 'Auxin', 'Cytokinin', 'Gibberellin', 'Ethylene', NULL, NULL, NULL, NULL, 'Gibberellins promote seed germination and stem elongation.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(390, 1, 2, 'mcq', 'Which plant hormone is responsible for ripening of fruits?', 'Auxin', 'Gibberellin', 'Ethylene', 'Cytokinin', NULL, NULL, NULL, NULL, 'Ethylene stimulates fruit ripening.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(391, 1, 2, 'mcq', 'In humans, the hormone responsible for secondary sexual characteristics in males is', 'Oestrogen', 'Progesterone', 'Testosterone', 'Insulin', NULL, NULL, NULL, NULL, 'Testosterone controls development of male secondary sexual characteristics.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(392, 1, 2, 'mcq', 'Adrenaline is secreted by the', 'Pancreas', 'Adrenal gland', 'Pituitary gland', 'Thyroid gland', NULL, NULL, NULL, NULL, 'Adrenaline is secreted by the adrenal glands.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(393, 1, 2, 'mcq', 'Which of the following hormones regulates metabolism in humans?', 'Thyroxine', 'Insulin', 'Progesterone', 'Oxytocin', NULL, NULL, NULL, NULL, 'Thyroxine regulates the rate of body metabolism.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'a', NULL),
(394, 1, 2, 'mcq', 'Which of the following is an example of tropic movement in plants?', 'Folding of mimosa leaves', 'Opening of flowers at night', 'Growth of roots towards gravity', 'Drooping of leaves during water shortage', NULL, NULL, NULL, NULL, 'Growth of roots towards gravity is positive geotropism, a tropic movement.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(395, 1, 2, 'mcq', 'The part of the brain that regulates secretion of hormones from the pituitary gland is the', 'Medulla', 'Hypothalamus', 'Cerebrum', 'Cerebellum', NULL, NULL, NULL, NULL, 'The hypothalamus controls pituitary hormone secretion.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(396, 1, 2, 'mcq', 'The hormone responsible for the contraction of the uterus during childbirth is', 'Insulin', 'Oxytocin', 'Progesterone', 'Prolactin', NULL, NULL, NULL, NULL, 'Oxytocin stimulates uterine contraction during childbirth.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'b', NULL),
(397, 1, 2, 'mcq', 'The structural and functional unit of the nervous system is the', 'Axon', 'Nephron', 'Neuron', 'Dendrite', NULL, NULL, NULL, NULL, 'The neuron is the structural and functional unit of the nervous system.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(398, 1, 2, 'mcq', 'The part of the human brain responsible for voluntary movement is the', 'Medulla', 'Cerebellum', 'Cerebrum', 'Hypothalamus', NULL, NULL, NULL, NULL, 'The cerebrum controls conscious and voluntary activities.', 1, '2026-06-19 02:47:05', '2026-06-19 02:47:05', NULL, 1.00, NULL, NULL, 'c', NULL),
(399, 1, 2, 'mcq', 'In a reflex arc, the effector is usually a', 'Sensory neuron', 'Muscle or gland', 'Relay neuron', 'Spinal cord', NULL, NULL, NULL, NULL, 'The effector is the muscle or gland that carries out the response.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'b', NULL),
(400, 1, 2, 'mcq', 'Which of the following is not a sense organ?', 'Nose', 'Eye', 'Tongue', 'Kidney', NULL, NULL, NULL, NULL, 'The kidney is an excretory organ, not a sense organ.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'd', NULL),
(401, 1, 2, 'mcq', 'The part of the eye that regulates the amount of light entering is the', 'Cornea', 'Lens', 'Retina', 'Iris', NULL, NULL, NULL, NULL, 'The iris controls pupil size and regulates light entry.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'd', NULL),
(402, 1, 2, 'mcq', 'The gap between two adjacent neurons is called', 'Synapse', 'Axon', 'Myelin', 'Dendrite', NULL, NULL, NULL, NULL, 'The synapse is the junction or gap between two neurons.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'a', NULL),
(403, 1, 2, 'mcq', 'The nerve that connects the eye to the brain is the', 'Auditory nerve', 'Optic nerve', 'Olfactory nerve', 'Spinal nerve', NULL, NULL, NULL, NULL, 'The optic nerve carries impulses from the eye to the brain.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'b', NULL),
(404, 1, 2, 'mcq', 'Which of the following activities is controlled by the medulla oblongata?', 'Thinking', 'Walking', 'Breathing', 'Balancing', NULL, NULL, NULL, NULL, 'The medulla oblongata controls involuntary activities such as breathing.', 1, '2026-06-19 02:47:06', '2026-06-19 02:47:06', NULL, 1.00, NULL, NULL, 'c', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cbt_question_banks`
--

CREATE TABLE `cbt_question_banks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cbt_question_banks`
--

INSERT INTO `cbt_question_banks` (`id`, `tenant_id`, `subject_id`, `class_level_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 6, 'BIOLOGY', NULL, 1, '2026-06-17 12:42:14', '2026-06-17 12:42:14'),
(3, 1, 5, 4, 'Physics', NULL, 1, '2026-06-24 09:19:15', '2026-06-24 09:19:15');

-- --------------------------------------------------------

--
-- Table structure for table `cbt_student_sessions`
--

CREATE TABLE `cbt_student_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `cbt_exam_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`question_order`)),
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `flagged_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flagged_questions`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('not_started','in_progress','submitted','graded','completed','expired','cancelled') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `essay_answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`essay_answers`)),
  `marked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `manual_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`manual_scores`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cbt_student_sessions`
--

INSERT INTO `cbt_student_sessions` (`id`, `tenant_id`, `cbt_exam_id`, `student_id`, `question_order`, `answers`, `flagged_questions`, `started_at`, `submitted_at`, `last_synced_at`, `score`, `percentage`, `status`, `created_at`, `updated_at`, `essay_answers`, `marked_by`, `manual_scores`) VALUES
(2, 1, 2, 4, '[395,399,379,360,384,362,364,355,365,382,371,394,392,401,366,403,387,390,388,373,381,356,404,376,375,386,378,363,383,369,398,359,396,357,391,361,389,400,377,397,368,358,374,380,367,393,370,385,372,402]', '{\"395\":\"b\",\"399\":\"b\",\"379\":\"b\",\"360\":\"a\",\"384\":\"b\",\"362\":\"a\",\"364\":\"b\",\"355\":\"b\",\"365\":\"a\",\"382\":\"a\",\"371\":\"b\"}', NULL, '2026-06-19 02:55:34', '2026-06-19 02:58:39', NULL, 9.00, 18.00, 'graded', '2026-06-19 02:55:34', '2026-06-19 02:58:39', '[]', NULL, NULL),
(3, 1, 3, 4, '[369,388,371,373,404,378,359,401,398,391,386,360,375,380,403,399,396,400,382,358]', '{\"369\":\"a\",\"388\":\"a\",\"371\":\"b\",\"373\":\"a\",\"404\":\"c\",\"378\":\"b\",\"359\":\"c\",\"401\":\"d\",\"398\":\"c\",\"391\":\"c\",\"386\":\"b\",\"360\":\"a\",\"375\":\"b\",\"380\":\"a\",\"403\":\"b\",\"399\":\"a\",\"396\":\"d\",\"400\":\"d\",\"382\":\"a\",\"358\":\"b\"}', NULL, '2026-06-21 19:24:29', '2026-06-21 19:30:25', NULL, 16.00, 80.00, 'graded', '2026-06-21 19:24:29', '2026-06-21 19:30:25', '[]', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_arms`
--

CREATE TABLE `class_arms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `academic_track_id` bigint(20) UNSIGNED DEFAULT NULL,
  `form_tutor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_arms`
--

INSERT INTO `class_arms` (`id`, `tenant_id`, `class_level_id`, `academic_track_id`, `form_tutor_id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 2, 4, 'A', '2026-06-14 00:37:19', '2026-06-23 10:36:11', NULL),
(2, 1, 1, NULL, NULL, 'B', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(3, 1, 1, NULL, NULL, 'C', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(4, 1, 2, NULL, NULL, 'A', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(5, 1, 2, NULL, NULL, 'B', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(6, 1, 3, NULL, NULL, 'A', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(7, 1, 3, NULL, NULL, 'B', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL),
(8, 1, 4, NULL, NULL, 'A Gold', '2026-06-14 00:37:19', '2026-06-22 14:08:54', NULL),
(11, 1, 5, NULL, NULL, 'A Gold', '2026-06-14 00:37:19', '2026-06-22 14:12:28', NULL),
(12, 1, 5, NULL, NULL, 'A Silver', '2026-06-14 00:37:19', '2026-06-22 14:12:58', NULL),
(13, 1, 5, NULL, NULL, 'A Diamond', '2026-06-14 00:37:19', '2026-06-22 14:13:35', NULL),
(14, 1, 6, NULL, 3, 'A Gold', '2026-06-14 00:37:20', '2026-06-22 14:17:32', NULL),
(15, 1, 6, NULL, NULL, 'A Silver', '2026-06-14 00:37:20', '2026-06-22 14:18:05', NULL),
(16, 1, 6, NULL, NULL, 'A Diamond', '2026-06-14 00:37:20', '2026-06-22 14:18:47', NULL),
(17, 1, 4, NULL, NULL, 'A Silver', '2026-06-22 14:07:34', '2026-06-22 14:09:23', NULL),
(18, 1, 4, NULL, NULL, 'A Diamond', '2026-06-22 14:07:50', '2026-06-22 14:10:02', NULL),
(19, 1, 4, NULL, NULL, 'B/C', '2026-06-22 14:08:01', '2026-06-22 14:08:01', NULL),
(20, 1, 2, NULL, NULL, 'C', '2026-06-22 14:11:13', '2026-06-22 14:11:13', NULL),
(21, 1, 5, NULL, NULL, 'B/C', '2026-06-22 14:13:57', '2026-06-22 14:14:38', NULL),
(22, 1, 6, NULL, NULL, 'B/C', '2026-06-22 14:19:14', '2026-06-22 14:19:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_arm_subjects`
--

CREATE TABLE `class_arm_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_arm_subjects`
--

INSERT INTO `class_arm_subjects` (`id`, `tenant_id`, `class_arm_id`, `subject_id`, `teacher_id`, `session_id`, `term_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 14, 1, NULL, 1, NULL, 1, '2026-06-14 03:59:50', '2026-06-14 03:59:50'),
(2, 1, 15, 1, 3, 1, NULL, 1, '2026-06-14 13:09:38', '2026-06-14 13:09:38'),
(3, 1, 1, 1, 3, 1, NULL, 1, '2026-06-14 14:37:05', '2026-06-14 14:37:05'),
(4, 1, 1, 19, NULL, 1, NULL, 1, '2026-06-21 12:58:30', '2026-06-21 12:58:30'),
(5, 1, 2, 19, NULL, 1, NULL, 1, '2026-06-21 12:58:30', '2026-06-21 12:58:30'),
(6, 1, 3, 19, NULL, 1, NULL, 1, '2026-06-21 12:58:30', '2026-06-21 12:58:30'),
(7, 1, 1, 7, 4, 1, NULL, 1, '2026-06-22 03:00:48', '2026-06-22 03:00:48'),
(8, 1, 2, 7, 4, 1, NULL, 1, '2026-06-22 03:00:48', '2026-06-22 03:00:48'),
(9, 1, 3, 7, 4, 1, NULL, 1, '2026-06-22 03:00:48', '2026-06-22 03:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `class_levels`
--

CREATE TABLE `class_levels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `section` enum('creche','nursery','primary','junior_secondary','senior_secondary') NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_levels`
--

INSERT INTO `class_levels` (`id`, `tenant_id`, `name`, `section`, `order_index`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Basic 7', 'junior_secondary', 7, '2026-06-14 00:37:19', '2026-06-22 14:05:46', NULL),
(2, 1, 'Basic 8', 'junior_secondary', 8, '2026-06-14 00:37:19', '2026-06-22 14:06:06', NULL),
(3, 1, 'Basic 9', 'junior_secondary', 9, '2026-06-14 00:37:19', '2026-06-22 14:06:21', NULL),
(4, 1, 'Year 10', 'senior_secondary', 10, '2026-06-14 00:37:19', '2026-06-22 14:06:37', NULL),
(5, 1, 'Year 11', 'senior_secondary', 11, '2026-06-14 00:37:19', '2026-06-22 14:12:08', NULL),
(6, 1, 'Year 12', 'senior_secondary', 12, '2026-06-14 00:37:20', '2026-06-22 14:16:40', NULL),
(7, 1, 'Basic 6', 'primary', 6, '2026-06-21 10:26:23', '2026-06-22 14:20:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_level_subjects`
--

CREATE TABLE `class_level_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `academic_track_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `subject_status` enum('compulsory','elective','optional','not_offered') NOT NULL DEFAULT 'compulsory',
  `elective_group` varchar(60) DEFAULT NULL,
  `min_required` tinyint(3) UNSIGNED DEFAULT NULL,
  `max_allowed` tinyint(3) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_level_subjects`
--

INSERT INTO `class_level_subjects` (`id`, `tenant_id`, `class_level_id`, `academic_track_id`, `subject_id`, `subject_status`, `elective_group`, `min_required`, `max_allowed`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 6, NULL, 1, 'compulsory', NULL, NULL, NULL, 1, '2026-06-17 21:15:09', '2026-06-17 21:15:09'),
(3, 1, 1, 2, 2, 'compulsory', NULL, NULL, NULL, 1, '2026-06-23 10:37:08', '2026-06-23 10:37:08'),
(4, 1, 1, 3, 1, 'compulsory', NULL, NULL, NULL, 1, '2026-06-23 10:37:48', '2026-06-23 10:37:48'),
(6, 1, 1, 3, 8, 'elective', 'Science electives A', NULL, NULL, 1, '2026-06-23 10:39:13', '2026-06-23 10:40:43'),
(7, 1, 1, 3, 7, 'elective', 'Science electives A', NULL, NULL, 1, '2026-06-23 10:39:29', '2026-06-23 10:39:29'),
(8, 1, 1, 3, 9, 'elective', 'Science electives A', NULL, NULL, 1, '2026-06-23 10:41:06', '2026-06-23 10:42:43'),
(9, 1, 1, 3, 19, 'elective', 'Science electives B', NULL, NULL, 1, '2026-06-23 10:41:35', '2026-06-23 10:41:35'),
(10, 1, 1, 3, 6, 'elective', 'Science electives B', NULL, NULL, 1, '2026-06-23 10:42:24', '2026-06-23 10:42:24'),
(11, 1, 1, 3, 5, 'compulsory', NULL, NULL, NULL, 1, '2026-06-23 10:43:17', '2026-06-23 10:43:17'),
(12, 1, 1, 3, 4, 'compulsory', NULL, NULL, NULL, 1, '2026-06-23 10:43:31', '2026-06-23 10:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_categories`
--

CREATE TABLE `fee_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `school_bank_subaccount_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_categories`
--

INSERT INTO `fee_categories` (`id`, `tenant_id`, `school_bank_subaccount_id`, `name`, `is_mandatory`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'TUTION', 1, '2026-06-15 03:40:38', '2026-06-15 03:40:38'),
(2, 1, 1, 'Boarding Fee', 1, '2026-06-25 22:23:05', '2026-06-25 22:23:05'),
(3, 1, 1, 'Extension Fee', 1, '2026-06-25 22:23:22', '2026-06-25 22:23:22'),
(4, 1, 1, 'External Examinations', 1, '2026-06-25 22:23:54', '2026-06-25 22:23:54');

-- --------------------------------------------------------

--
-- Table structure for table `fee_installments`
--

CREATE TABLE `fee_installments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `installment_number` int(11) NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payment_plans`
--

CREATE TABLE `fee_payment_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `installments_count` int(11) NOT NULL,
  `installment_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`installment_schedule`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `surcharge_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_payment_plans`
--

INSERT INTO `fee_payment_plans` (`id`, `tenant_id`, `name`, `description`, `installments_count`, `installment_schedule`, `is_active`, `is_default`, `surcharge_pct`, `created_at`, `updated_at`) VALUES
(1, 1, 'SCHOOL FEE', NULL, 3, '[{\"installment\":1,\"percentage\":\"50\",\"due_days\":0,\"label\":\"1st Installment\"},{\"installment\":2,\"percentage\":\"30\",\"due_days\":30,\"label\":\"2nd Installment\"},{\"installment\":3,\"percentage\":\"20\",\"due_days\":60,\"label\":\"3rd Installment\"}]', 1, 0, 5.00, '2026-06-22 02:33:42', '2026-06-22 02:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'sms',
  `recipient` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `fee_category_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `tenant_id`, `fee_category_id`, `class_level_id`, `term_id`, `amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 45000.00, 1, '2026-06-15 03:41:33', '2026-06-15 03:41:33'),
(2, 1, 1, 6, 1, 70000.00, 1, '2026-06-15 03:42:43', '2026-06-15 03:42:43'),
(3, 1, 2, 6, 1, 250000.00, 1, '2026-06-25 22:24:26', '2026-06-25 22:24:26'),
(4, 1, 3, 6, 1, 150000.00, 1, '2026-06-25 22:25:50', '2026-06-25 22:25:50'),
(5, 1, 4, 6, 1, 200000.00, 1, '2026-06-25 22:26:11', '2026-06-25 22:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `grading_systems`
--

CREATE TABLE `grading_systems` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `grade_letter` varchar(5) NOT NULL,
  `min_score` tinyint(3) UNSIGNED NOT NULL,
  `max_score` tinyint(3) UNSIGNED NOT NULL,
  `remark` varchar(255) NOT NULL,
  `is_pass_grade` tinyint(1) NOT NULL DEFAULT 1,
  `grade_point` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_systems`
--

INSERT INTO `grading_systems` (`id`, `tenant_id`, `class_level_id`, `grade_letter`, `min_score`, `max_score`, `remark`, `is_pass_grade`, `grade_point`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(2, 1, 1, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(3, 1, 1, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(4, 1, 1, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(5, 1, 1, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(6, 1, 1, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(7, 1, 1, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(8, 1, 1, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(9, 1, 1, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(10, 1, 2, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(11, 1, 2, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(12, 1, 2, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(13, 1, 2, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(14, 1, 2, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(15, 1, 2, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(16, 1, 2, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(17, 1, 2, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(18, 1, 2, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(19, 1, 3, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(20, 1, 3, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(21, 1, 3, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(22, 1, 3, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(23, 1, 3, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(24, 1, 3, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(25, 1, 3, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(26, 1, 3, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(27, 1, 3, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(28, 1, 4, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(29, 1, 4, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(30, 1, 4, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(31, 1, 4, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(32, 1, 4, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(33, 1, 4, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(34, 1, 4, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(35, 1, 4, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(36, 1, 4, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(37, 1, 5, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(38, 1, 5, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(39, 1, 5, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(40, 1, 5, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(41, 1, 5, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(42, 1, 5, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(43, 1, 5, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(44, 1, 5, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(45, 1, 5, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(46, 1, 6, 'A1', 75, 100, 'Excellent', 1, 1, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(47, 1, 6, 'B2', 70, 74, 'Very Good', 1, 2, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(48, 1, 6, 'B3', 65, 69, 'Good', 1, 3, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(49, 1, 6, 'C4', 60, 64, 'Credit', 1, 4, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(50, 1, 6, 'C5', 55, 59, 'Credit', 1, 5, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(51, 1, 6, 'C6', 50, 54, 'Credit', 1, 6, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(52, 1, 6, 'D7', 45, 49, 'Pass', 1, 7, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(53, 1, 6, 'E8', 40, 44, 'Pass', 1, 8, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(54, 1, 6, 'F9', 0, 39, 'Fail', 0, 9, '2026-06-14 00:37:20', '2026-06-14 00:37:20');

-- --------------------------------------------------------

--
-- Table structure for table `guardians`
--

CREATE TABLE `guardians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `relationship` enum('father','mother','guardian','other') NOT NULL DEFAULT 'guardian',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guardians`
--

INSERT INTO `guardians` (`id`, `tenant_id`, `user_id`, `first_name`, `last_name`, `phone`, `email`, `occupation`, `address`, `relationship`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 'Suleiman', 'Ibrahim', '09021832450', NULL, NULL, NULL, 'father', '2026-06-14 09:08:24', '2026-06-14 09:08:24', NULL),
(2, 1, 7, 'ISHAK', 'SALEH', '09012345678', NULL, NULL, NULL, 'guardian', '2026-06-16 14:48:18', '2026-06-17 20:01:33', NULL),
(3, 1, 13, 'Ibrahim', 'Bello', '8012345678', NULL, NULL, NULL, 'father', '2026-06-21 09:55:23', '2026-06-25 16:46:18', NULL),
(4, 1, NULL, 'Mrs', 'Okafor', '7098765432', NULL, NULL, NULL, 'mother', '2026-06-21 09:55:23', '2026-06-21 09:55:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `guardian_student`
--

CREATE TABLE `guardian_student` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary_contact` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guardian_student`
--

INSERT INTO `guardian_student` (`id`, `tenant_id`, `guardian_id`, `student_id`, `is_primary_contact`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2026-06-14 09:08:24', '2026-06-14 09:08:24'),
(2, 1, 2, 4, 0, '2026-06-16 14:48:18', '2026-06-16 14:48:18'),
(3, 1, 3, 7, 0, '2026-06-21 09:55:23', '2026-06-21 09:55:23'),
(4, 1, 4, 8, 0, '2026-06-21 09:55:23', '2026-06-21 09:55:23');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','partially_paid','paid','waived','overpaid') NOT NULL DEFAULT 'unpaid',
  `has_payment_plan` tinyint(1) NOT NULL DEFAULT 0,
  `next_installment_due` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `discount_template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `generation_batch_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `tenant_id`, `student_id`, `term_id`, `session_id`, `invoice_number`, `total_amount`, `amount_paid`, `status`, `has_payment_plan`, `next_installment_due`, `due_date`, `created_at`, `updated_at`, `deleted_at`, `discount_template_id`, `discount_amount`, `notes`, `generation_batch_id`) VALUES
(1, 1, 1, 1, 1, 'INV-2026-00001', 70000.00, 0.00, 'unpaid', 0, NULL, '2025-12-12', '2026-06-15 03:42:55', '2026-06-15 03:42:55', NULL, NULL, 0.00, NULL, NULL),
(2, 1, 4, 1, 1, 'INV-2026-00002', 670000.00, 0.00, 'unpaid', 0, NULL, '2025-12-12', '2026-06-25 22:26:48', '2026-06-25 22:26:48', NULL, NULL, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_discounts`
--

CREATE TABLE `invoice_discounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_discount_templates`
--

CREATE TABLE `invoice_discount_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_generation_batches`
--

CREATE TABLE `invoice_generation_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `generated_by` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('all','class_level','class_arm','individual') NOT NULL DEFAULT 'class_level',
  `class_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `class_arm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `total_students` int(11) NOT NULL,
  `generated_count` int(11) NOT NULL,
  `skipped_count` int(11) NOT NULL,
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('completed','partial','failed') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `fee_category_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `tenant_id`, `invoice_id`, `fee_category_id`, `description`, `amount`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'TUTION', 70000.00, '2026-06-15 03:42:55', '2026-06-15 03:42:55'),
(2, 1, 2, 1, 'TUTION', 70000.00, '2026-06-25 22:26:48', '2026-06-25 22:26:48'),
(3, 1, 2, 2, 'Boarding Fee', 250000.00, '2026-06-25 22:26:48', '2026-06-25 22:26:48'),
(4, 1, 2, 3, 'Extension Fee', 150000.00, '2026-06-25 22:26:48', '2026-06-25 22:26:48'),
(5, 1, 2, 4, 'External Examinations', 200000.00, '2026-06-25 22:26:48', '2026-06-25 22:26:48');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_reference` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_channel` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payment_plans`
--

CREATE TABLE `invoice_payment_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_plans`
--

CREATE TABLE `lesson_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `curriculum_type` enum('nerdc','british') NOT NULL DEFAULT 'nerdc',
  `topic` varchar(255) NOT NULL,
  `subtopic` varchar(255) DEFAULT NULL,
  `week_number` int(11) DEFAULT NULL,
  `plan_date` date DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 40,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `previous_knowledge` text DEFAULT NULL,
  `entry_behaviour` text DEFAULT NULL,
  `behavioural_objectives` text DEFAULT NULL,
  `instructional_materials` text DEFAULT NULL,
  `reference_materials` text DEFAULT NULL,
  `set_induction` text DEFAULT NULL,
  `presentation` text DEFAULT NULL,
  `class_activity` text DEFAULT NULL,
  `evaluation` text DEFAULT NULL,
  `assignment` text DEFAULT NULL,
  `conclusion` text DEFAULT NULL,
  `lesson_notes` longtext DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `success_criteria` text DEFAULT NULL,
  `starter_activity` text DEFAULT NULL,
  `differentiation` text DEFAULT NULL,
  `plenary` text DEFAULT NULL,
  `assessment_for_learning` text DEFAULT NULL,
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lesson_plans`
--

INSERT INTO `lesson_plans` (`id`, `tenant_id`, `teacher_id`, `subject_id`, `class_level_id`, `class_arm_id`, `term_id`, `curriculum_type`, `topic`, `subtopic`, `week_number`, `plan_date`, `duration_minutes`, `status`, `previous_knowledge`, `entry_behaviour`, `behavioural_objectives`, `instructional_materials`, `reference_materials`, `set_induction`, `presentation`, `class_activity`, `evaluation`, `assignment`, `conclusion`, `lesson_notes`, `learning_objectives`, `success_criteria`, `starter_activity`, `differentiation`, `plenary`, `assessment_for_learning`, `ai_generated`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, 16, 1, NULL, 5, 'nerdc', 'reproduction', 'fertilization', 2, NULL, 40, 'published', 'Students previously learned about the basic structures of plants and animals in Basic Science, specifically the topics of cell biology and photosynthesis.', 'Students should have a basic understanding of cell division and the life cycle of living organisms before proceeding with this lesson.', 'At the end of this lesson, students will be able to explain the process of fertilization, describe the roles of the male and female reproductive cells, and identify the importance of fertilization in the reproduction of plants and animals.', 'Whiteboard, markers, diagrams of male and female reproductive systems, pictures of fertilization processes in plants and animals, and a model of a flower.', 'Basic Science for Junior Secondary Schools by Okoro et al. (University Press PLC, 2018), and the NERDC-approved Basic Science curriculum.', 'The teacher will start by asking students what they know about how plants and animals reproduce, and then write their responses on the board.', NULL, NULL, 'What is fertilization? Describe the role of the male reproductive cell in fertilization. What is the importance of fertilization in plant reproduction? How does fertilization occur in animals? Give an example of fertilization in a Nigerian plant species.', NULL, 'The teacher will summarize the key points of the lesson, emphasizing the importance of fertilization in the reproduction of plants and animals, and preview the next topic on pregnancy and childbirth, encouraging students to ask questions and seek help when needed.', '<h1 style=\"color: #00698f; text-align: center;\">Reproduction: Fertilization</h1>\n<h2 style=\"color: #008000; text-align: center;\">Introduction</h2>\n<p style=\"font-size: 18px; text-align: justify;\">Fertilization is the process by which male and female reproductive cells combine to form a zygote. This process is crucial for the reproduction of plants and animals. In this lesson, we will explore the process of fertilization, the roles of the male and female reproductive cells, and the importance of fertilization in the reproduction of plants and animals.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">The Process of Fertilization</h2>\n<p style=\"font-size: 18px; text-align: justify;\">The process of fertilization involves the fusion of a male reproductive cell (sperm) with a female reproductive cell (egg). In animals, this process occurs in the fallopian tube, while in plants, it occurs in the ovary. The sperm cell swims towards the egg cell and penetrates the outer layer, resulting in the fusion of the two cells.</p>\n<svg width=\"300\" height=\"200\" style=\"border: 1px solid black; margin: auto; display: block;\">\n  <circle cx=\"100\" cy=\"100\" r=\"50\" fill=\"#ccc\" />\n  <circle cx=\"200\" cy=\"100\" r=\"50\" fill=\"#ccc\" />\n  <line x1=\"150\" y1=\"100\" x2=\"250\" y2=\"100\" stroke=\"#000\" stroke-width=\"2\" />\n  <text x=\"100\" y=\"120\" font-size=\"18\" text-anchor=\"middle\">Sperm</text>\n  <text x=\"200\" y=\"120\" font-size=\"18\" text-anchor=\"middle\">Egg</text>\n</svg>\n<figcaption style=\"text-align: center; font-size: 18px;\">Diagram of sperm and egg cells</figcaption>\n\n<h2 style=\"color: #008000; text-align: center;\">Roles of the Male and Female Reproductive Cells</h2>\n<p style=\"font-size: 18px; text-align: justify;\">The male reproductive cell (sperm) plays a crucial role in fertilization. It carries the genetic material from the male parent and swims towards the egg cell to fertilize it. The female reproductive cell (egg) also plays a vital role in fertilization. It provides the necessary nutrients and environment for the development of the zygote.</p>\n<table style=\"border: 1px solid black; border-collapse: collapse; margin: auto; width: 50%;\" align=\"center\">\n  <tr style=\"background-color: #f0f0f0; border: 1px solid black; padding: 10px;\">\n    <th>Reproductive Cell</th>\n    <th>Role</th>\n  </tr>\n  <tr style=\"border: 1px solid black; padding: 10px;\">\n    <td>Sperm</td>\n    <td>Carries genetic material from male parent</td>\n  </tr>\n  <tr style=\"border: 1px solid black; padding: 10px;\">\n    <td>Egg</td>\n    <td>Provides nutrients and environment for zygote development</td>\n  </tr>\n</table>\n\n<h2 style=\"color: #008000; text-align: center;\">Importance of Fertilization</h2>\n<p style=\"font-size: 18px; text-align: justify;\">Fertilization is essential for the reproduction of plants and animals. It allows for the creation of a new individual with a unique combination of genetic traits from the parents. Without fertilization, there would be no new generation of plants and animals.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">Conclusion</h2>\n<p style=\"font-size: 18px; text-align: justify;\">In conclusion, fertilization is a crucial process in the reproduction of plants and animals. It involves the fusion of male and female reproductive cells to form a zygote. Understanding the process of fertilization and the roles of the male and female reproductive cells is essential for appreciating the importance of fertilization in the reproduction of plants and animals.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">KEY POINTS</h2>\n<ul style=\"font-size: 18px; text-align: justify; margin: auto; width: 80%;\">\n  <li>Fertilization is the process by which male and female reproductive cells combine to form a zygote.</li>\n  <li>The process of fertilization involves the fusion of a sperm cell with an egg cell.</li>\n  <li>The male reproductive cell (sperm) carries genetic material from the male parent.</li>\n  <li>The female reproductive cell (egg) provides nutrients and environment for zygote development.</li>\n  <li>Fertilization is essential for the reproduction of plants and animals.</li>\n</ul>\n\n<h2 style=\"color: #008000; text-align: center;\">PAST EXAM QUESTIONS</h2>\n<p style=\"font-size: 18px; text-align: justify;\">1. What is fertilization?</p>\n<p style=\"font-size: 18px; text-align: justify;\">Answer: Fertilization is the process by which male and female reproductive cells combine to form a zygote.</p>\n<p style=\"font-size: 18px; text-align: justify;\">2. What is the role of the sperm cell in fertilization?</p>\n<p style=\"font-size: 18px; text-align: justify;\">Answer: The sperm cell carries genetic material from the male parent and swims towards the egg cell to fertilize it.</p>\n<p style=\"font-size: 18px; text-align: justify;\">3. What is the role of the egg cell in fertilization?</p>\n<p style=\"font-size: 18px; text-align: justify;\">Answer: The egg cell provides the necessary nutrients and environment for the development of the zygote.</p>\n<p style=\"font-size: 18px; text-align: justify;\">4. Why is fertilization important in the reproduction of plants and animals?</p>\n<p style=\"font-size: 18px; text-align: justify;\">Answer: Fertilization is essential for the creation of a new individual with a unique combination of genetic traits from the parents.</p>\n<p style=\"font-size: 18px; text-align: justify;\">5. Where does fertilization occur in animals?</p>\n<p style=\"font-size: 18px; text-align: justify;\">Answer: Fertilization occurs in the fallopian tube in animals.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-27 05:21:46', '2026-06-27 06:05:22', NULL),
(2, 1, 2, 1, 6, NULL, 5, 'nerdc', 'Regulation of internal enviroment', 'Homeostasis', 2, '2026-03-20', 40, 'published', 'In the previous lesson, we discussed the importance of the internal environment and its role in maintaining the overall health of an organism, which leads to understanding the concept of homeostasis.\r\nThis lesson builds on that foundation by exploring the regulation of internal environment through homeostasis.', 'Students are expected to have a basic understanding of biology and the human body systems.\r\nThey should also be familiar with the concept of internal and external environments and their significance in biological processes.', 'By the end of this lesson, students should be able to:\r\n1. define homeostasis and its importance in maintaining internal environment.\r\n2. identify the factors that affect homeostasis.\r\n3. explain the mechanisms of homeostasis.\r\n4. describe the role of the nervous and endocrine systems in regulating homeostasis.', '- Diagrams of the human body systems involved in homeostasis\r\n- Chart showing the factors that affect homeostasis\r\n- Models of the nervous and endocrine systems', 'Ababio, R. O. \"Biology for Senior Secondary Schools\", pages 123-125\r\nAdeyemo, D. A. \"Essential Biology for Senior Secondary Schools\", pages 156-158', 'The teacher will start the lesson by asking students about their daily activities and how their bodies respond to changes in the environment.\r\nThis will lead to a discussion on the importance of maintaining a stable internal environment, which introduces the concept of homeostasis.', NULL, NULL, '1. What is homeostasis and why is it important? (Answer: Homeostasis is the ability of the body to maintain a stable internal environment, which is essential for proper bodily functions.\r\n2. Describe the negative feedback mechanism in regulating body temperature.\r\n3. What is the role of the pancreas in maintaining blood sugar homeostasis?\r\n4. Explain how the kidneys help regulate homeostasis.\r\n5. What are the consequences of not maintaining homeostasis?', NULL, 'In conclusion, homeostasis is a critical process that maintains the stability of the internal environment, and its regulation is essential for proper bodily functions.\r\nIn the next lesson, we will explore the effects of homeostatic imbalance on human health and discuss ways to maintain homeostasis.', '<h1 style=\"color: #00698f; text-align: center;\">Regulation of Internal Environment: Homeostasis</h1>\n<h2 style=\"color: #008000; text-align: center;\">Introduction to Homeostasis</h2>\n<p style=\"text-align: justify;\">Homeostasis is the ability of the body to maintain a stable internal environment despite changes in the external environment. It involves the regulation of various physiological processes to keep the internal environment within a narrow range that is necessary for proper functioning of the body. This concept was first introduced by Walter Cannon in 1932.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">Importance of Homeostasis</h2>\n<p style=\"text-align: justify;\">Homeostasis is crucial for the survival of the body. It helps to maintain a stable internal environment that is necessary for proper functioning of the cells, tissues, and organs. Without homeostasis, the body would not be able to function properly, and this could lead to illness or even death. For example, the regulation of blood sugar levels is an important aspect of homeostasis. If blood sugar levels become too high or too low, it can lead to serious health problems.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">Role of the Nervous and Endocrine Systems in Maintaining Homeostasis</h2>\n<p style=\"text-align: justify;\">The nervous and endocrine systems play important roles in maintaining homeostasis. The nervous system helps to regulate homeostasis through the use of nerve impulses, while the endocrine system uses hormones to regulate various physiological processes. The hypothalamus, which is part of the nervous system, acts as the main regulatory center for homeostasis. It receives information from various sensors in the body and sends signals to the effectors, such as muscles and glands, to bring about the necessary changes to maintain homeostasis.</p>\n<svg width=\"400\" height=\"200\" style=\"border: 1px solid black; margin: auto; display: block;\">\n  <rect x=\"50\" y=\"50\" width=\"100\" height=\"50\" fill=\"#cccccc\" />\n  <rect x=\"200\" y=\"50\" width=\"100\" height=\"50\" fill=\"#cccccc\" />\n  <line x1=\"150\" y1=\"75\" x2=\"250\" y2=\"75\" stroke=\"black\" stroke-width=\"2\" />\n  <text x=\"75\" y=\"90\" font-size=\"18\">Hypothalamus</text>\n  <text x=\"225\" y=\"90\" font-size=\"18\">Effectors</text>\n  <text x=\"200\" y=\"120\" font-size=\"18\">Nerve impulses</text>\n</svg>\n<figcaption style=\"text-align: center;\">Diagram showing the role of the hypothalamus in maintaining homeostasis</figcaption>\n\n<h2 style=\"color: #008000; text-align: center;\">Positive and Negative Feedback Mechanisms</h2>\n<p style=\"text-align: justify;\">There are two types of feedback mechanisms that help to maintain homeostasis: positive feedback and negative feedback. Positive feedback mechanisms involve the amplification of a response to a stimulus, while negative feedback mechanisms involve the reduction of a response to a stimulus. Negative feedback is the most common type of feedback mechanism and is used to maintain homeostasis. For example, the regulation of blood pressure is an example of a negative feedback mechanism. When blood pressure increases, it stimulates the baroreceptors in the walls of the blood vessels, which send signals to the brain to reduce heart rate and dilate blood vessels, thereby reducing blood pressure.</p>\n<table style=\"border: 1px solid black; border-collapse: collapse; width: 100%; margin: auto;\">\n  <tr style=\"background-color: #f0f0f0;\">\n    <th style=\"padding: 10px; border: 1px solid black;\">Type of Feedback</th>\n    <th style=\"padding: 10px; border: 1px solid black;\">Description</th>\n    <th style=\"padding: 10px; border: 1px solid black;\">Example</th>\n  </tr>\n  <tr>\n    <td style=\"padding: 10px; border: 1px solid black;\">Positive Feedback</td>\n    <td style=\"padding: 10px; border: 1px solid black;\">Amplification of a response to a stimulus</td>\n    <td style=\"padding: 10px; border: 1px solid black;\">Blood clotting</td>\n  </tr>\n  <tr>\n    <td style=\"padding: 10px; border: 1px solid black;\">Negative Feedback</td>\n    <td style=\"padding: 10px; border: 1px solid black;\">Reduction of a response to a stimulus</td>\n    <td style=\"padding: 10px; border: 1px solid black;\">Regulation of blood pressure</td>\n  </tr>\n</table>\n\n<h2 style=\"color: #008000; text-align: center;\">Conclusion</h2>\n<p style=\"text-align: justify;\">In conclusion, homeostasis is an important concept in biology that helps to maintain a stable internal environment despite changes in the external environment. The nervous and endocrine systems play important roles in maintaining homeostasis, and feedback mechanisms, such as positive and negative feedback, help to regulate various physiological processes.</p>\n\n<h2 style=\"color: #008000; text-align: center;\">KEY POINTS</h2>\n<ul style=\"list-style: disc; margin: 20px;\">\n  <li>Homeostasis is the ability of the body to maintain a stable internal environment despite changes in the external environment.</li>\n  <li>The nervous and endocrine systems play important roles in maintaining homeostasis.</li>\n  <li>Feedback mechanisms, such as positive and negative feedback, help to regulate various physiological processes.</li>\n  <li>Homeostasis is crucial for the survival of the body.</li>\n  <li>The hypothalamus acts as the main regulatory center for homeostasis.</li>\n</ul>\n\n<h2 style=\"color: #008000; text-align: center;\">PAST EXAM QUESTIONS</h2>\n<p style=\"text-align: justify;\">1. What is homeostasis, and why is it important in the human body? (WAEC, 2018)</p>\n<p style=\"text-align: justify;\">Model Answer: Homeostasis is the ability of the body to maintain a stable internal environment despite changes in the external environment. It is important because it helps to maintain a stable internal environment that is necessary for proper functioning of the cells, tissues, and organs.</p>\n\n<p style=\"text-align: justify;\">2. Describe the role of the nervous system in maintaining homeostasis. (NECO, 2019)</p>\n<p style=\"text-align: justify;\">Model Answer: The nervous system helps to regulate homeostasis through the use of nerve impulses. It receives information from various sensors in the body and sends signals to the effectors, such as muscles and glands, to bring about the necessary changes to maintain homeostasis.</p>\n\n<p style=\"text-align: justify;\">3. What is the difference between positive and negative feedback mechanisms? (WAEC, 2020)</p>\n<p style=\"text-align: justify;\">Model Answer: Positive feedback mechanisms involve the amplification of a response to a stimulus, while negative feedback mechanisms involve the reduction of a response to a stimulus.</p>\n\n<p style=\"text-align: justify;\">4. Explain the importance of the hypothalamus in maintaining homeostasis. (NECO, 2018)</p>\n<p style=\"text-align: justify;\">Model Answer: The hypothalamus acts as the main regulatory center for homeostasis. It receives information from various sensors in the body and sends signals to the effectors, such as muscles and glands, to bring about the necessary changes to maintain homeostasis.</p>\n\n<p style=\"text-align: justify;\">5. Describe the regulation of blood sugar levels as an example of homeostasis. (WAEC, 2019)</p>\n<p style=\"text-align: justify;\">Model Answer: The regulation of blood sugar levels is an example of homeostasis. When blood sugar levels increase, it stimulates the release of insulin from the pancreas, which helps to reduce blood sugar levels. When blood sugar levels decrease, it stimulates the release of glucagon from the pancreas, which helps to increase blood sugar levels.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-27 06:40:22', '2026-06-27 08:07:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `edition` varchar(255) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `condition` enum('excellent','good','fair','poor') NOT NULL DEFAULT 'good',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_loans`
--

CREATE TABLE `library_loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('issued','returned','overdue','lost') NOT NULL DEFAULT 'issued',
  `fine_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `issued_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_threads`
--

CREATE TABLE `message_threads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `initiated_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_thread_replies`
--

CREATE TABLE `message_thread_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_01_01_000001_create_tenants_table', 1),
(2, '2025_01_01_000002_create_users_table', 1),
(3, '2025_01_01_000003_create_academic_foundation_tables', 1),
(4, '2025_01_01_000004_create_students_and_guardians_tables', 1),
(5, '2025_01_01_000005_create_subjects_and_timetable_tables', 1),
(6, '2025_01_01_000006_create_assessment_and_grading_tables', 1),
(7, '2025_01_01_000007_create_financial_tables', 1),
(8, '2025_01_01_000008_create_cbt_attendance_messaging_tables', 1),
(9, '2026_06_14_004556_create_permission_tables', 1),
(10, '2025_01_01_000009_create_skills_tables', 2),
(11, '2025_01_01_000010_create_timetable_config_table', 3),
(12, '2025_01_01_000011_update_termly_summaries_table', 4),
(14, '2025_01_01_000012_fix_subaccount_nullable', 5),
(15, '2025_01_01_000013_create_super_admin_tables', 5),
(16, '2025_01_01_000017_create_library_announcement_tables', 5),
(17, '2025_01_01_000018_create_agents_parent_portal_tables', 5),
(18, '2025_01_01_000019_create_messaging_payment_gateway_tables', 5),
(19, '2025_01_01_000014_create_school_settings_calendar_tables', 6),
(20, '2025_01_01_000015_create_admissions_health_tables', 6),
(21, '2025_01_01_000016_create_finance_payroll_tables', 6),
(22, '2025_01_01_000020_enhance_admissions_portal', 7),
(23, '2025_01_01_000021_create_fee_payment_plans', 8),
(24, '2025_01_01_000022_create_invoice_generation_log_table', 9),
(25, '2025_01_01_000021_create_fee_payment_plans', 10),
(26, '2025_01_01_000023_create_student_risk_flags_table', 11),
(27, '2025_01_01_000024_create_payroll_templates_table', 12),
(28, '2025_01_01_000025_add_publish_status_to_report_cards', 13),
(29, '2025_01_01_000026_enhance_cbt_questions', 13),
(30, '2026_06_17_000001_create_transport_tables', 14),
(31, '2026_06_17_000002_create_sms_campaign_tables', 14),
(32, '2026_06_17_000003_create_notification_trigger_tables', 14),
(33, '2026_06_17_000004_create_school_group_tables', 14),
(34, '2026_06_17_000005_create_platform_invoice_table', 14),
(37, '2026_06_17_100001_create_academic_tracks_table', 15),
(38, '2026_06_17_100002_seed_academic_tracks', 15),
(39, '2026_06_18_000001_add_primary_to_academic_tracks_section_enum', 16),
(40, '2026_06_18_000002_add_last_login_to_users', 16),
(41, '2026_06_18_000003_add_user_id_to_guardians', 16),
(42, '2026_06_18_000004_add_email_to_students', 16),
(43, '2026_06_17_052614_fix_roles_and_invoice_payments_table', 17),
(44, '2026_06_18_000005_update_user_roles_and_add_login_ids', 18),
(45, '2026_06_18_000006_expand_users_role_enum', 19),
(46, '2026_06_18_000007_add_admission_officer_badge', 20),
(47, '2026_06_17_113434_fix_cbt_questions_nullable_correct_option', 21),
(48, '2026_06_18_000008_add_profile_fields_to_users', 22),
(49, '2026_06_18_000009_enhance_cbt_questions', 23),
(50, '2026_06_17_132727_alter_status_column_in_cbt_student_sessions_table', 24),
(51, '2026_06_18_000010_create_agent_system_tables', 24),
(52, '2026_06_18_000011_add_portal_columns_to_platform_agents', 25),
(53, '2026_06_18_000013_add_proxy_verification_to_staff_attendance', 26),
(54, '2026_06_18_000014_add_photo_to_staff_attendance', 27),
(55, '2026_06_18_000015_add_qr_secret_to_users', 28),
(56, '2026_06_18_000016_add_permanent_qr_to_attendance_settings', 29),
(57, '2026_06_18_000017_add_payment_to_admissions', 30),
(58, '2026_06_18_000018_create_staff_permissions_table', 30),
(59, '2026_06_18_000019_create_platform_settings_table', 30),
(60, '2026_06_18_000020_add_theme_to_tenant', 31),
(61, '2026_06_19_000001_prepare_student_lifecycle_foundation', 32),
(62, '2026_06_19_000002_prepare_staff_lifecycle_foundation', 33),
(63, '2026_06_19_000003_create_lifecycle_audit_logs_table', 34),
(64, '2026_06_19_000004_add_cancellation_reason_to_student_class_transfers', 35),
(65, '2026_06_19_193413_create_sessions_table', 36),
(66, '2026_06_18_000012_create_staff_attendance_records_table', 37),
(67, '2026_06_18_000012_create_staff_attendance_settings_table', 37),
(68, '2026_06_18_000012_create_staff_offline_clockins_table', 37),
(69, '2026_06_21_000001_seed_payment_gateway_settings', 38),
(70, '2026_06_22_000001_create_staff_deductions_table', 39),
(71, '2026_06_22_000002_create_payroll_tax_bands_table', 39),
(72, '2026_06_22_000003_add_rent_relief_and_deduction_breakdown', 39),
(73, '2026_06_22_000004_add_tin_and_lock_to_staff_salary_settings', 40),
(74, '2026_06_22_000005_grant_orphaned_lifecycle_permissions', 40),
(75, '2026_06_22_000006_add_friend_photo_to_proxy_requests', 41),
(76, '2026_06_22_000007_add_monnify_to_payment_gateway_configs', 42),
(77, '2026_06_22_000008_add_bvn_nin_to_staff_salary_settings', 42),
(78, '2026_06_22_000009_add_section_config_to_cbt_exams', 43),
(79, '2026_06_22_000010_encrypt_payment_and_payroll_secrets', 44),
(80, '2026_06_23_000001_ensure_subscription_plan_features', 45),
(81, '2026_06_24_000001_create_queue_tables', 45),
(82, '2026_06_24_000001_update_subscription_plans_trial_and_enterprise', 46),
(83, '2026_06_24_000002_add_admissions_feature_to_plans', 47),
(84, '2026_06_24_100000_add_next_term_begins_to_terms_table', 48),
(86, '2026_06_25_115113_encrypt_existing_gateway_secret_keys', 49),
(87, '2026_06_25_132043_add_soft_deletes_to_core_tables', 50),
(88, '2026_06_25_133536_add_two_factor_to_users_table', 50),
(89, '2026_06_26_013810_widen_gateway_key_columns_to_text', 51),
(90, '2026_06_26_100000_create_lesson_planner_tables', 52),
(91, '2026_06_27_100000_add_lesson_notes_to_lesson_plans', 53);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(2, 'App\\Models\\User', 12),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 11),
(11, 'App\\Models\\User', 10),
(13, 'App\\Models\\User', 9);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guardian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('queued','sent','failed','delivered') NOT NULL DEFAULT 'queued',
  `gateway_message_id` varchar(255) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `unit_cost` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_queue`
--

CREATE TABLE `notification_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `channel` varchar(255) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `gateway` varchar(255) NOT NULL DEFAULT 'termii',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_queue`
--

INSERT INTO `notification_queue` (`id`, `tenant_id`, `channel`, `recipient`, `subject`, `body`, `gateway`, `status`, `attempts`, `error_message`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'sms', '12345678900', NULL, 'Dear Yusuf Ibrahim, your application for Abdulrahman Yusuf to Greenfield Academy has been received. Application No: APP-GREENFIELD-ACADEMY-2026-6DPSFC. Track status at: http://127.0.0.1:8000/apply/greenfield-academy/status?APP-GREENFIELD-ACADEMY-2026-6DPSFC', 'termii', 'pending', 0, NULL, NULL, '2026-06-16 16:47:19', '2026-06-16 16:47:19');

-- --------------------------------------------------------

--
-- Table structure for table `notification_queues`
--

CREATE TABLE `notification_queues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `channel` varchar(20) NOT NULL,
  `recipient` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `event` varchar(80) DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_triggers`
--

CREATE TABLE `notification_triggers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(80) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `channel` enum('sms','email','both') NOT NULL DEFAULT 'sms',
  `template` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_trigger_logs`
--

CREATE TABLE `notification_trigger_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(80) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `recipient` varchar(150) NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `online_payment_logs`
--

CREATE TABLE `online_payment_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `gateway` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `online_payment_logs`
--

INSERT INTO `online_payment_logs` (`id`, `tenant_id`, `invoice_id`, `student_id`, `gateway`, `reference`, `amount`, `status`, `gateway_response`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 4, 'monnify', 'SMS-XCWCPMNQWSR5', 670000.00, 'pending', NULL, NULL, '2026-06-25 23:22:12', '2026-06-25 23:22:12'),
(2, 1, 2, 4, 'monnify', 'SMS-HDJEB0UYRMM6', 670000.00, 'pending', NULL, NULL, '2026-06-25 23:22:27', '2026-06-25 23:22:27'),
(3, 1, 2, 4, 'monnify', 'SMS-F7RJPL1EEJEL', 670000.00, 'pending', NULL, NULL, '2026-06-25 23:23:16', '2026-06-25 23:23:16');

-- --------------------------------------------------------

--
-- Table structure for table `parent_messages`
--

CREATE TABLE `parent_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `from_user_id` bigint(20) UNSIGNED NOT NULL,
  `to_user_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_portal_accounts`
--

CREATE TABLE `parent_portal_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_configs`
--

CREATE TABLE `payment_gateway_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `gateway` enum('paystack','flutterwave','monnify') NOT NULL DEFAULT 'paystack',
  `public_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `contract_code` varchar(255) DEFAULT NULL,
  `is_live` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateway_configs`
--

INSERT INTO `payment_gateway_configs` (`id`, `tenant_id`, `gateway`, `public_key`, `secret_key`, `contract_code`, `is_live`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'monnify', 'MK_TEST_JWKK91VJCA', 'eyJpdiI6ImJ3QjY3d0ZkV1lCSjF3UkxnTkF6R1E9PSIsInZhbHVlIjoiVUVjY3d5Um5jMEpHSzcwSm1yQy96VXpWb2pCYXBxY1JCbFVlZlh4dUdudTIvS0VkdS93U1YwUWFiSzRxTkVEaSIsIm1hYyI6IjE3MTNhODgzYjU1MWIxYjQ2YTQzMzgxYjljNTRhNmZmZTdlYzNiZjBjYzQzNzYxNjY0NzdjYWZmOWY3OWI3OWQiLCJ0YWciOiIifQ==', '5644623779', 0, 1, '2026-06-25 23:21:41', '2026-06-25 23:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `gateway_reference` varchar(255) NOT NULL,
  `gateway` varchar(255) NOT NULL DEFAULT 'paystack',
  `amount_paid` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `status` enum('pending','success','failed','reversed') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `split_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`split_breakdown`)),
  `paid_by_name` varchar(255) DEFAULT NULL,
  `paid_by_phone` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deduction_templates`
--

CREATE TABLE `payroll_deduction_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('tax','pension','loan','other') NOT NULL DEFAULT 'other',
  `calc_method` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_items`
--

CREATE TABLE `payroll_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pension_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deduction_breakdown`)),
  `total_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_items`
--

INSERT INTO `payroll_items` (`id`, `tenant_id`, `payroll_period_id`, `staff_id`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `gross_pay`, `tax_deduction`, `pension_deduction`, `other_deductions`, `deduction_breakdown`, `total_deductions`, `net_pay`, `bank_name`, `account_number`, `account_name`, `payment_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 160000.00, 4000.00, 2000.00, 3000.00, 169000.00, 12675.00, 13520.00, 0.00, NULL, 26195.00, 142805.00, 'Access Bank', '0011503330', NULL, 'paid', NULL, '2026-06-21 11:00:12', '2026-06-21 11:03:28');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('draft','approved','paid') NOT NULL DEFAULT 'draft',
  `total_gross` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `tenant_id`, `title`, `period_start`, `period_end`, `status`, `total_gross`, `total_deductions`, `total_net`, `approved_by`, `payment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'June 2026 staff Salary', '2026-06-01', '2026-06-30', 'paid', 169000.00, 26195.00, 142805.00, 2, '2026-06-21', '2026-06-21 11:00:12', '2026-06-21 11:03:28');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_role_templates`
--

CREATE TABLE `payroll_role_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deduction_ids`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_tax_bands`
--

CREATE TABLE `payroll_tax_bands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `lower_bound` decimal(12,2) NOT NULL,
  `upper_bound` decimal(12,2) DEFAULT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_tax_bands`
--

INSERT INTO `payroll_tax_bands` (`id`, `tenant_id`, `lower_bound`, `upper_bound`, `rate_percent`, `order_index`, `created_at`, `updated_at`) VALUES
(1, 1, 0.00, 800000.00, 0.00, 0, '2026-06-21 18:18:26', '2026-06-21 18:18:26'),
(2, 1, 800000.00, 3000000.00, 15.00, 1, '2026-06-21 18:18:26', '2026-06-21 18:18:26'),
(3, 1, 3000000.00, 12000000.00, 18.00, 2, '2026-06-21 18:18:26', '2026-06-21 18:18:26'),
(4, 1, 12000000.00, 25000000.00, 21.00, 3, '2026-06-21 18:18:26', '2026-06-21 18:18:26'),
(5, 1, 25000000.00, 50000000.00, 23.00, 4, '2026-06-21 18:18:26', '2026-06-21 18:18:26'),
(6, 1, 50000000.00, NULL, 25.00, 5, '2026-06-21 18:18:26', '2026-06-21 18:18:26');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'students.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(2, 'students.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(3, 'students.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(4, 'students.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(5, 'students.promote', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(6, 'students.admit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(7, 'guardians.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(8, 'guardians.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(9, 'guardians.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(10, 'guardians.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(11, 'classes.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(12, 'classes.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(13, 'classes.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(14, 'classes.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(15, 'subjects.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(16, 'subjects.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(17, 'subjects.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(18, 'subjects.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(19, 'subjects.assign-teacher', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(20, 'scores.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(21, 'scores.enter', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(22, 'scores.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(23, 'scores.approve', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(24, 'scores.broadsheet.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(25, 'reports.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(26, 'reports.generate', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(27, 'reports.print', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(28, 'reports.remark', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(29, 'fees.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(30, 'fees.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(31, 'fees.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(32, 'fees.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(33, 'fees.invoice.generate', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(34, 'fees.invoice.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(35, 'fees.payment.record', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(36, 'fees.payment.verify', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(37, 'fees.discount.apply', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(38, 'fees.report.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(39, 'cbt.bank.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(40, 'cbt.bank.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(41, 'cbt.bank.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(42, 'cbt.bank.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(43, 'cbt.exam.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(44, 'cbt.exam.publish', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(45, 'cbt.exam.close', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(46, 'cbt.results.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(47, 'attendance.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(48, 'attendance.mark', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(49, 'attendance.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(50, 'attendance.report.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(51, 'timetable.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(52, 'timetable.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(53, 'timetable.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(54, 'staff.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(55, 'staff.create', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(56, 'staff.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(57, 'staff.delete', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(58, 'settings.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(59, 'settings.edit', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(60, 'settings.grading.manage', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(61, 'settings.session.manage', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(62, 'settings.fees.setup', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(63, 'messaging.send', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(64, 'messaging.view-logs', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(65, 'portal.child.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(66, 'portal.fees.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(67, 'portal.results.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(68, 'portal.attendance.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(69, 'portal.student.results.view', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(70, 'portal.student.cbt.take', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(71, 'dashboard.view', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(72, 'classes.manage', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(73, 'subjects.manage', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(74, 'scores.enter.own', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(75, 'scores.enter.all', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(76, 'reports.compute', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(77, 'reports.pdf', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(78, 'reports.remarks.teacher', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(79, 'reports.remarks.principal', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(80, 'reports.remarks.bulk', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(81, 'skills.view', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(82, 'skills.enter', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(83, 'timetable.view.own', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(84, 'timetable.manage', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(85, 'cbt.view', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(86, 'cbt.manage', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(87, 'fees.manage', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(88, 'notifications.send', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(89, 'super.access', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(90, 'student.transfer.request', 'web', '2026-06-19 09:15:59', '2026-06-19 09:15:59'),
(91, 'student.transfer.approve', 'web', '2026-06-19 09:15:59', '2026-06-19 09:15:59'),
(92, 'student.transfer.reject', 'web', '2026-06-19 09:15:59', '2026-06-19 09:15:59'),
(93, 'student.transfer.cancel', 'web', '2026-06-19 09:15:59', '2026-06-19 09:15:59'),
(94, 'student.transfer.view', 'web', '2026-06-19 09:15:59', '2026-06-19 09:15:59'),
(95, 'student.status.view', 'web', '2026-06-19 12:24:54', '2026-06-19 12:24:54'),
(96, 'student.status.change', 'web', '2026-06-19 12:24:54', '2026-06-19 12:24:54'),
(97, 'student.status.approve', 'web', '2026-06-19 12:24:54', '2026-06-19 12:24:54'),
(98, 'student.archive.view', 'web', '2026-06-19 12:24:54', '2026-06-19 12:24:54'),
(99, 'student.archive.export', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(100, 'student.reactivate', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(101, 'student.readmit', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(102, 'student.status.correct-graduation', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(103, 'staff.status.view', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(104, 'staff.status.change', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(105, 'staff.status.approve', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(106, 'staff.archive.view', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(107, 'staff.archive.export', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(108, 'staff.reinstate', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(109, 'staff.reinstate-terminated', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(110, 'staff.work-history.view', 'web', '2026-06-19 17:19:40', '2026-06-19 17:19:40'),
(111, 'staff.work-history.manage', 'web', '2026-06-19 17:19:41', '2026-06-19 17:19:41'),
(112, 'staff.work-history.approve', 'web', '2026-06-19 17:19:41', '2026-06-19 17:19:41'),
(113, 'academic-cycle.view', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(114, 'academic-session.manage', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(115, 'academic-term.manage', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(116, 'student-promotion.view', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(117, 'student-promotion.manage', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(118, 'academic-rollover.preview', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(119, 'academic-rollover.execute', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48'),
(120, 'academic-cycle.repair', 'web', '2026-06-20 10:36:48', '2026-06-20 10:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `platform_agents`
--

CREATE TABLE `platform_agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `total_earned` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `referral_code` varchar(20) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(20) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_agents`
--

INSERT INTO `platform_agents` (`id`, `name`, `email`, `phone`, `state`, `commission_rate`, `total_earned`, `total_paid`, `is_active`, `referral_code`, `password`, `remember_token`, `last_login_at`, `created_at`, `updated_at`, `bank_name`, `bank_account_number`, `bank_account_name`, `notes`) VALUES
(1, 'Ummukulsum Yusuf', 'arriyadmcs@gmail.com', '09069136185', 'Nasarawa', 10.00, 0.00, 0.00, 1, 'NYXZQZTM', '$2y$12$Gon8P5Te5H8RSsUvSEXjjeQm6GEDbdd9TnM1OZYW7ihU1SUleZ0u6', NULL, '2026-06-18 18:37:50', '2026-06-17 15:02:45', '2026-06-18 18:40:31', 'Opay', '9069136185', 'Ummukulsum Yusuf', NULL),
(2, 'Musatapha Musa', 'xpressaccess6@gmail.com', '090123456789', 'Kwara State', 10.00, 0.00, 0.00, 1, 'MCJRROW4', '$2y$12$l3DyhMhnGb0Rq/cZhlfJC.ERa9mNQf/d3OKqazk3FntejvivmuH4.', NULL, '2026-06-25 19:39:43', '2026-06-23 16:05:19', '2026-06-25 19:39:43', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `platform_invoices`
--

CREATE TABLE `platform_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `billing_cycle` enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `status` enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `due_date` date NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(60) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_invoices`
--

INSERT INTO `platform_invoices` (`id`, `tenant_id`, `plan_id`, `invoice_number`, `amount`, `billing_cycle`, `status`, `payment_reference`, `due_date`, `paid_at`, `payment_method`, `payment_ref`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'INV-06LWZBRC', 15000.00, 'monthly', 'paid', 'SUB-6GQJA12GS5', '2026-06-28', '2026-06-23 16:02:26', 'bank_transfer', NULL, 'Self-service plan selection', '2026-06-21 08:47:43', '2026-06-23 16:02:26'),
(2, 1, 1, 'INV-XGUKEAEW', 15000.00, 'monthly', 'paid', 'SUB-TOLNXAGMNY', '2026-07-07', '2026-06-23 16:02:15', 'card', NULL, NULL, '2026-06-23 04:48:45', '2026-06-23 16:02:15'),
(3, 1, 1, 'INV-LSYJRNLE', 10000.00, 'monthly', 'paid', NULL, '2026-07-07', '2026-06-23 18:45:17', 'bank_transfer', NULL, NULL, '2026-06-23 18:45:06', '2026-06-23 18:45:17'),
(4, 1, 1, 'INV-NRNOM7UX', 10000.00, 'monthly', 'paid', 'SUB-QFMPQLGOZT', '2026-06-30', '2026-06-23 19:35:33', 'bank_transfer', NULL, 'Self-service plan selection', '2026-06-23 19:24:50', '2026-06-23 19:35:33'),
(5, 2, 2, 'INV-26UJZMIU', 30000.00, 'monthly', 'paid', 'SUB-HY62TYC5O0', '2026-06-30', '2026-06-23 19:38:14', 'bank_transfer', NULL, 'Self-service plan selection', '2026-06-23 19:37:32', '2026-06-23 19:38:14');

-- --------------------------------------------------------

--
-- Table structure for table `platform_payments`
--

CREATE TABLE `platform_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `status` enum('pending','confirmed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_channel` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_payments`
--

INSERT INTO `platform_payments` (`id`, `tenant_id`, `subscription_id`, `reference`, `amount`, `currency`, `status`, `payment_method`, `payment_channel`, `description`, `meta`, `confirmed_by`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'PAY-F8QZD48U', 15000.00, 'NGN', 'confirmed', 'bank_transfer', NULL, 'Invoice INV-XGUKEAEW payment', NULL, 1, '2026-06-23 16:02:01', '2026-06-23 16:02:01', '2026-06-23 16:02:01'),
(2, 1, NULL, 'PAY-RTFB0FZC', 15000.00, 'NGN', 'confirmed', 'card', NULL, 'Invoice INV-XGUKEAEW payment', NULL, 1, '2026-06-23 16:02:15', '2026-06-23 16:02:15', '2026-06-23 16:02:15'),
(3, 1, NULL, 'PAY-XS98OCJE', 15000.00, 'NGN', 'confirmed', 'bank_transfer', NULL, 'Invoice INV-06LWZBRC payment', NULL, 1, '2026-06-23 16:02:26', '2026-06-23 16:02:26', '2026-06-23 16:02:26'),
(4, 1, NULL, 'PAY-SDMQYPKW', 10000.00, 'NGN', 'confirmed', 'bank_transfer', NULL, 'Invoice INV-LSYJRNLE payment', NULL, 1, '2026-06-23 18:45:17', '2026-06-23 18:45:17', '2026-06-23 18:45:17'),
(5, 1, NULL, 'PAY-DYSIIRCU', 10000.00, 'NGN', 'confirmed', 'bank_transfer', NULL, 'Invoice INV-NRNOM7UX payment', NULL, 1, '2026-06-23 19:35:33', '2026-06-23 19:35:33', '2026-06-23 19:35:33'),
(6, 2, NULL, 'PAY-H10SUUHQ', 30000.00, 'NGN', 'confirmed', 'bank_transfer', NULL, 'Invoice INV-26UJZMIU payment', NULL, 1, '2026-06-23 19:38:14', '2026-06-23 19:38:14', '2026-06-23 19:38:14');

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`id`, `key`, `value`, `type`, `group`, `label`, `created_at`, `updated_at`) VALUES
(1, 'platform_name', 'EduCore', 'string', 'general', NULL, '2026-06-15 09:42:09', '2026-06-25 15:55:29'),
(2, 'support_email', 'support@enterprisesms.ng', 'string', 'general', NULL, '2026-06-15 09:42:09', '2026-06-25 15:55:29'),
(3, 'trial_days', '7', 'integer', 'general', NULL, '2026-06-15 09:42:09', '2026-06-25 15:55:29'),
(4, 'grace_period_days', '7', 'integer', 'general', NULL, '2026-06-15 09:42:09', '2026-06-25 15:55:29'),
(5, 'paystack_public_key', '', 'string', 'payment', NULL, '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(6, 'paystack_secret_key', NULL, 'string', 'payment', 'Paystack Secret Key', '2026-06-21 12:26:19', '2026-06-21 12:26:19'),
(7, 'paystack_is_live', '0', 'boolean', 'payment', NULL, '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(8, 'monnify_api_key', 'MK_TEST_JWKK91VJCA', 'string', 'payment', NULL, '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(9, 'monnify_secret_key', 'QQEMQDDDEY0P6C2JJ9PQL1TS186URTAU', 'string', 'payment', 'Monnify Secret Key', '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(10, 'monnify_contract_code', '5644623779', 'string', 'payment', NULL, '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(11, 'monnify_is_live', '1', 'boolean', 'payment', NULL, '2026-06-21 12:26:19', '2026-06-23 19:33:23'),
(12, 'support_phone', '07065595768', 'string', 'general', NULL, '2026-06-23 04:56:23', '2026-06-25 15:55:29'),
(13, 'default_sms_gateway', 'termii', 'string', 'general', NULL, '2026-06-23 04:56:24', '2026-06-25 15:55:29'),
(14, 'sms_sender_id', 'EduCore', 'string', 'general', NULL, '2026-06-23 04:56:25', '2026-06-25 15:55:29'),
(15, 'maintenance_mode', '1', 'boolean', 'general', NULL, '2026-06-23 04:56:27', '2026-06-23 04:56:27'),
(16, 'flutterwave_public_key', '', 'string', 'general', NULL, NULL, '2026-06-23 19:33:23'),
(17, 'flutterwave_is_live', '0', 'string', 'general', NULL, NULL, '2026-06-23 19:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `promotion_rules`
--

CREATE TABLE `promotion_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `min_required_average` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `max_failed_subjects_allowed` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `compulsory_subject_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compulsory_subject_ids`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotion_rules`
--

INSERT INTO `promotion_rules` (`id`, `tenant_id`, `class_level_id`, `min_required_average`, `max_failed_subjects_allowed`, `compulsory_subject_ids`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 40, 3, NULL, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(2, 1, 2, 40, 3, NULL, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(3, 1, 3, 40, 3, NULL, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(4, 1, 4, 40, 3, NULL, '2026-06-14 00:37:19', '2026-06-14 00:37:19'),
(5, 1, 5, 40, 3, NULL, '2026-06-14 00:37:20', '2026-06-14 00:37:20'),
(6, 1, 6, 40, 3, NULL, '2026-06-14 00:37:20', '2026-06-14 00:37:20');

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card_publications`
--

CREATE TABLE `report_card_publications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) UNSIGNED DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_threshold_configs`
--

CREATE TABLE `risk_threshold_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `academic_threshold` decimal(5,2) NOT NULL DEFAULT 45.00,
  `attendance_threshold` decimal(5,2) NOT NULL DEFAULT 75.00,
  `subjects_failed_threshold` int(11) NOT NULL DEFAULT 2,
  `include_fee_risk` tinyint(1) NOT NULL DEFAULT 1,
  `academic_weight` int(11) NOT NULL DEFAULT 40,
  `attendance_weight` int(11) NOT NULL DEFAULT 35,
  `fee_weight` int(11) NOT NULL DEFAULT 25,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_threshold_configs`
--

INSERT INTO `risk_threshold_configs` (`id`, `tenant_id`, `academic_threshold`, `attendance_threshold`, `subjects_failed_threshold`, `include_fee_risk`, `academic_weight`, `attendance_weight`, `fee_weight`, `created_at`, `updated_at`) VALUES
(1, 1, 45.00, 75.00, 2, 1, 40, 35, 25, '2026-06-16 13:09:44', '2026-06-16 13:09:44');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(2, 'admin', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(3, 'teacher', 'web', '2026-06-14 00:37:18', '2026-06-14 00:37:18'),
(4, 'accountant', 'web', '2026-06-14 00:37:18', '2026-06-17 04:36:08'),
(5, 'parent', 'web', '2026-06-14 00:37:18', '2026-06-17 04:36:08'),
(6, 'student', 'web', '2026-06-14 00:37:18', '2026-06-17 04:36:08'),
(7, 'super_admin', 'web', '2026-06-14 13:51:18', '2026-06-14 13:51:18'),
(8, 'principal', 'web', '2026-06-14 13:51:18', '2026-06-17 04:36:08'),
(9, 'vice_principal', 'web', '2026-06-14 13:51:18', '2026-06-17 04:36:08'),
(10, 'form_teacher', 'web', '2026-06-14 13:51:18', '2026-06-17 04:36:08'),
(11, 'subject_teacher', 'web', '2026-06-14 13:51:19', '2026-06-17 04:36:08'),
(12, 'administrator', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(13, 'admission_officer', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(14, 'assistant_form_teacher', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(15, 'form_subject_teacher', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(16, 'health_officer', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(17, 'librarian', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(18, 'transport_officer', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(19, 'communication_officer', 'web', '2026-06-17 04:36:08', '2026-06-17 04:36:08'),
(20, 'head', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(21, 'head_teacher', 'web', '2026-06-19 12:24:55', '2026-06-19 12:24:55'),
(22, 'academic_administrator', 'web', '2026-06-19 12:24:56', '2026-06-19 12:24:56');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 8),
(3, 9),
(4, 1),
(4, 2),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(11, 2),
(11, 8),
(11, 9),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(15, 2),
(15, 8),
(15, 9),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(20, 2),
(20, 3),
(20, 8),
(20, 9),
(20, 10),
(20, 11),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(25, 2),
(25, 3),
(25, 8),
(25, 9),
(25, 10),
(25, 11),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(29, 2),
(29, 4),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(47, 2),
(47, 3),
(47, 8),
(47, 9),
(47, 10),
(47, 11),
(48, 1),
(48, 2),
(48, 3),
(48, 8),
(48, 9),
(48, 10),
(48, 11),
(49, 1),
(50, 1),
(51, 1),
(51, 2),
(51, 3),
(51, 8),
(51, 9),
(51, 10),
(52, 1),
(53, 1),
(54, 1),
(54, 2),
(54, 8),
(54, 9),
(55, 1),
(55, 2),
(56, 1),
(56, 2),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(65, 5),
(66, 1),
(66, 5),
(67, 1),
(67, 5),
(68, 1),
(68, 5),
(69, 1),
(69, 6),
(70, 1),
(70, 6),
(71, 2),
(71, 3),
(71, 4),
(71, 7),
(71, 8),
(71, 9),
(71, 10),
(71, 11),
(72, 2),
(72, 8),
(73, 2),
(73, 8),
(74, 3),
(74, 11),
(75, 2),
(75, 8),
(75, 9),
(76, 2),
(76, 8),
(77, 2),
(77, 3),
(77, 8),
(77, 9),
(77, 10),
(78, 2),
(78, 3),
(78, 9),
(78, 10),
(79, 2),
(79, 8),
(80, 2),
(80, 8),
(81, 2),
(81, 3),
(81, 8),
(81, 9),
(81, 10),
(82, 2),
(82, 3),
(82, 8),
(82, 9),
(82, 10),
(83, 3),
(83, 11),
(84, 2),
(84, 8),
(85, 2),
(85, 3),
(85, 8),
(85, 9),
(85, 11),
(86, 2),
(86, 3),
(86, 8),
(86, 11),
(87, 2),
(87, 4),
(88, 2),
(88, 8),
(89, 7),
(90, 2),
(90, 8),
(90, 9),
(90, 12),
(90, 13),
(91, 2),
(91, 8),
(91, 12),
(92, 2),
(92, 8),
(92, 12),
(93, 2),
(93, 8),
(93, 12),
(94, 2),
(94, 8),
(94, 9),
(94, 12),
(94, 13),
(95, 2),
(95, 8),
(95, 9),
(95, 10),
(95, 12),
(95, 13),
(95, 20),
(95, 21),
(95, 22),
(96, 2),
(96, 8),
(96, 9),
(96, 12),
(96, 20),
(96, 21),
(96, 22),
(97, 2),
(97, 8),
(97, 12),
(97, 20),
(97, 21),
(98, 2),
(98, 8),
(98, 9),
(98, 12),
(98, 13),
(98, 20),
(98, 21),
(98, 22),
(99, 2),
(99, 8),
(99, 12),
(99, 20),
(99, 21),
(100, 2),
(100, 8),
(100, 12),
(100, 20),
(100, 21),
(101, 2),
(101, 8),
(101, 12),
(101, 20),
(101, 21),
(102, 2),
(102, 8),
(102, 12),
(102, 20),
(102, 21),
(103, 2),
(103, 8),
(103, 9),
(103, 12),
(103, 20),
(103, 21),
(103, 22),
(104, 2),
(104, 8),
(104, 12),
(104, 20),
(104, 21),
(105, 2),
(105, 8),
(105, 12),
(105, 20),
(105, 21),
(106, 2),
(106, 4),
(106, 8),
(106, 9),
(106, 12),
(106, 20),
(106, 21),
(106, 22),
(107, 2),
(107, 8),
(107, 12),
(107, 20),
(107, 21),
(108, 2),
(108, 8),
(108, 12),
(108, 20),
(108, 21),
(109, 2),
(109, 8),
(109, 12),
(109, 20),
(109, 21),
(110, 2),
(110, 4),
(110, 8),
(110, 9),
(110, 12),
(110, 20),
(110, 21),
(110, 22),
(111, 2),
(111, 8),
(111, 12),
(111, 20),
(111, 21),
(112, 2),
(112, 8),
(112, 12),
(112, 20),
(112, 21),
(113, 2),
(113, 8),
(113, 9),
(113, 12),
(113, 20),
(113, 21),
(113, 22),
(114, 2),
(114, 8),
(114, 12),
(114, 20),
(114, 21),
(114, 22),
(115, 2),
(115, 8),
(115, 12),
(115, 20),
(115, 21),
(115, 22),
(116, 2),
(116, 8),
(116, 9),
(116, 12),
(116, 20),
(116, 21),
(116, 22),
(117, 2),
(117, 8),
(117, 9),
(117, 12),
(117, 20),
(117, 21),
(117, 22),
(118, 2),
(118, 8),
(118, 9),
(118, 12),
(118, 20),
(118, 21),
(118, 22),
(119, 2),
(119, 8),
(119, 12),
(119, 20),
(119, 21),
(120, 2),
(120, 8),
(120, 12),
(120, 20),
(120, 21),
(120, 22);

-- --------------------------------------------------------

--
-- Table structure for table `school_bank_subaccounts`
--

CREATE TABLE `school_bank_subaccounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `purpose_name` varchar(255) NOT NULL,
  `gateway_subaccount_code` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(10) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `gateway` enum('paystack','monnify','flutterwave') NOT NULL DEFAULT 'paystack',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_bank_subaccounts`
--

INSERT INTO `school_bank_subaccounts` (`id`, `tenant_id`, `purpose_name`, `gateway_subaccount_code`, `bank_name`, `account_number`, `account_name`, `gateway`, `is_active`, `created_at`, `updated_at`, `description`) VALUES
(1, 1, 'Tution Account', 'ACCT', 'First Bank', '0000111100', 'Greenfield Academy', 'paystack', 1, '2026-06-15 03:38:16', '2026-06-15 03:38:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `school_expenses`
--

CREATE TABLE `school_expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_expenses`
--

INSERT INTO `school_expenses` (`id`, `tenant_id`, `session_id`, `term_id`, `title`, `category`, `amount`, `expense_date`, `payment_method`, `reference`, `description`, `receipt_path`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'BOOKS AND STATIONERIES', 'supplies', 2000000.00, '2026-06-22', 'bank_transfer', NULL, NULL, NULL, 2, '2026-06-22 02:34:47', '2026-06-22 02:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `school_groups`
--

CREATE TABLE `school_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_name` varchar(120) DEFAULT NULL,
  `owner_email` varchar(180) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_group_members`
--

CREATE TABLE `school_group_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('member','lead') NOT NULL DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_settings`
--

INSERT INTO `school_settings` (`id`, `tenant_id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES
(1, 1, 'motto', 'Knowledge Beget Wisdom', 'general', '2026-06-16 13:15:38', '2026-06-16 13:15:38'),
(2, 1, 'website', NULL, 'general', '2026-06-16 13:15:38', '2026-06-16 13:15:38'),
(3, 1, 'established_year', NULL, 'general', '2026-06-16 13:15:38', '2026-06-16 13:15:38'),
(4, 1, 'proprietor', NULL, 'general', '2026-06-16 13:15:38', '2026-06-16 13:15:38'),
(5, 1, 'slogan', '', 'general', '2026-06-16 13:15:38', '2026-06-16 13:15:38');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `assessment_type_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `entered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `entered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `tenant_id`, `student_id`, `subject_id`, `assessment_type_id`, `term_id`, `session_id`, `entered_by`, `score`, `entered_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 3, 1, 1, 2, 10.00, '2026-06-19 12:13:29', '2026-06-14 09:09:54', '2026-06-19 12:13:29'),
(2, 1, 1, 1, 4, 1, 1, 2, 10.00, '2026-06-19 12:13:29', '2026-06-14 09:09:54', '2026-06-19 12:13:29'),
(3, 1, 1, 1, 1, 1, 1, 2, 5.00, '2026-06-19 12:13:29', '2026-06-14 09:09:54', '2026-06-19 12:13:29'),
(4, 1, 1, 1, 2, 1, 1, 2, 5.00, '2026-06-19 12:13:29', '2026-06-14 09:09:54', '2026-06-19 12:13:29'),
(5, 1, 1, 1, 5, 1, 1, 2, 65.00, '2026-06-19 12:13:29', '2026-06-14 09:09:54', '2026-06-19 12:13:29'),
(6, 1, 4, 3, 3, 1, 1, 2, 10.00, '2026-06-16 19:20:38', '2026-06-16 19:20:38', '2026-06-16 19:20:38'),
(7, 1, 4, 3, 4, 1, 1, 2, 8.00, '2026-06-16 19:20:38', '2026-06-16 19:20:38', '2026-06-16 19:20:38'),
(8, 1, 4, 3, 1, 1, 1, 2, 3.00, '2026-06-16 19:20:38', '2026-06-16 19:20:38', '2026-06-16 19:20:38'),
(9, 1, 4, 3, 2, 1, 1, 2, 5.00, '2026-06-16 19:20:38', '2026-06-16 19:20:38', '2026-06-16 19:20:38'),
(10, 1, 4, 3, 5, 1, 1, 2, 70.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(11, 1, 1, 3, 3, 1, 1, 2, 8.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(12, 1, 1, 3, 4, 1, 1, 2, 7.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(13, 1, 1, 3, 1, 1, 1, 2, 3.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(14, 1, 1, 3, 2, 1, 1, 2, 4.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(15, 1, 1, 3, 5, 1, 1, 2, 68.00, '2026-06-16 19:20:39', '2026-06-16 19:20:39', '2026-06-16 19:20:39'),
(16, 1, 4, 1, 3, 1, 1, 2, 6.00, '2026-06-19 12:13:29', '2026-06-19 12:13:29', '2026-06-19 12:13:29'),
(17, 1, 4, 1, 4, 1, 1, 2, 8.00, '2026-06-19 12:13:29', '2026-06-19 12:13:29', '2026-06-19 12:13:29'),
(18, 1, 4, 1, 1, 1, 1, 2, 3.00, '2026-06-19 12:13:29', '2026-06-19 12:13:29', '2026-06-19 12:13:29'),
(19, 1, 4, 1, 2, 1, 1, 2, 5.00, '2026-06-19 12:13:29', '2026-06-19 12:13:29', '2026-06-19 12:13:29'),
(20, 1, 4, 1, 5, 1, 1, 2, 55.00, '2026-06-19 12:13:29', '2026-06-19 12:13:29', '2026-06-19 12:13:29'),
(21, 1, 7, 7, 3, 1, 1, 4, 4.00, '2026-06-22 03:43:46', '2026-06-22 03:43:46', '2026-06-22 03:43:46'),
(22, 1, 7, 7, 4, 1, 1, 4, 6.00, '2026-06-22 03:43:46', '2026-06-22 03:43:46', '2026-06-22 03:43:46'),
(23, 1, 7, 7, 1, 1, 1, 4, 3.00, '2026-06-22 03:43:46', '2026-06-22 03:43:46', '2026-06-22 03:43:46'),
(24, 1, 7, 7, 2, 1, 1, 4, 5.00, '2026-06-22 03:43:46', '2026-06-22 03:43:46', '2026-06-22 03:43:46'),
(25, 1, 7, 7, 5, 1, 1, 4, 62.00, '2026-06-22 03:43:46', '2026-06-22 03:43:46', '2026-06-22 03:43:46'),
(26, 1, 4, 6, 1, 1, 1, 2, 3.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(27, 1, 4, 6, 2, 1, 1, 2, 3.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(28, 1, 4, 6, 3, 1, 1, 2, 8.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(29, 1, 4, 6, 4, 1, 1, 2, 6.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(30, 1, 4, 6, 5, 1, 1, 2, 56.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(31, 1, 1, 6, 1, 1, 1, 2, 5.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(32, 1, 1, 6, 2, 1, 1, 2, 5.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(33, 1, 1, 6, 3, 1, 1, 2, 5.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(34, 1, 1, 6, 4, 1, 1, 2, 5.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(35, 1, 1, 6, 5, 1, 1, 2, 45.00, '2026-06-24 20:44:38', '2026-06-24 20:44:38', '2026-06-24 20:44:38'),
(36, 1, 4, 6, 6, 2, 1, 2, 5.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(37, 1, 4, 6, 7, 2, 1, 2, 5.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(38, 1, 4, 6, 8, 2, 1, 2, 6.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(39, 1, 4, 6, 9, 2, 1, 2, 7.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(40, 1, 4, 6, 10, 2, 1, 2, 54.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(41, 1, 1, 6, 6, 2, 1, 2, 4.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(42, 1, 1, 6, 7, 2, 1, 2, 4.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(43, 1, 1, 6, 8, 2, 1, 2, 6.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(44, 1, 1, 6, 9, 2, 1, 2, 7.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(45, 1, 1, 6, 10, 2, 1, 2, 45.00, '2026-06-24 20:45:31', '2026-06-24 20:45:31', '2026-06-24 20:45:31'),
(46, 1, 4, 6, 11, 3, 1, 2, 5.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(47, 1, 4, 6, 13, 3, 1, 2, 5.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(48, 1, 4, 6, 14, 3, 1, 2, 8.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(49, 1, 4, 6, 15, 3, 1, 2, 9.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(50, 1, 4, 6, 16, 3, 1, 2, 65.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(51, 1, 1, 6, 11, 3, 1, 2, 5.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(52, 1, 1, 6, 13, 3, 1, 2, 4.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(53, 1, 1, 6, 14, 3, 1, 2, 6.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(54, 1, 1, 6, 15, 3, 1, 2, 6.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(55, 1, 1, 6, 16, 3, 1, 2, 66.00, '2026-06-24 20:46:24', '2026-06-24 20:46:24', '2026-06-24 20:46:24'),
(56, 1, 4, 1, 11, 3, 1, 2, 5.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(57, 1, 4, 1, 13, 3, 1, 2, 4.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(58, 1, 4, 1, 14, 3, 1, 2, 6.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(59, 1, 4, 1, 15, 3, 1, 2, 8.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(60, 1, 4, 1, 16, 3, 1, 2, 54.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(61, 1, 1, 1, 11, 3, 1, 2, 4.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(62, 1, 1, 1, 13, 3, 1, 2, 4.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(63, 1, 1, 1, 14, 3, 1, 2, 7.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(64, 1, 1, 1, 15, 3, 1, 2, 6.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(65, 1, 1, 1, 16, 3, 1, 2, 45.00, '2026-06-24 20:47:03', '2026-06-24 20:47:03', '2026-06-24 20:47:03'),
(66, 1, 4, 3, 11, 3, 1, 2, 5.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(67, 1, 4, 3, 13, 3, 1, 2, 5.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(68, 1, 4, 3, 14, 3, 1, 2, 4.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(69, 1, 4, 3, 15, 3, 1, 2, 4.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(70, 1, 4, 3, 16, 3, 1, 2, 34.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(71, 1, 1, 3, 11, 3, 1, 2, 5.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(72, 1, 1, 3, 13, 3, 1, 2, 4.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(73, 1, 1, 3, 14, 3, 1, 2, 4.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(74, 1, 1, 3, 15, 3, 1, 2, 5.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(75, 1, 1, 3, 16, 3, 1, 2, 54.00, '2026-06-24 20:47:55', '2026-06-24 20:47:55', '2026-06-24 20:47:55'),
(76, 1, 4, 1, 6, 2, 1, 2, 4.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(77, 1, 4, 1, 7, 2, 1, 2, 4.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(78, 1, 4, 1, 8, 2, 1, 2, 5.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(79, 1, 4, 1, 9, 2, 1, 2, 7.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(80, 1, 4, 1, 10, 2, 1, 2, 55.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(81, 1, 1, 1, 6, 2, 1, 2, 2.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(82, 1, 1, 1, 7, 2, 1, 2, 5.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(83, 1, 1, 1, 8, 2, 1, 2, 6.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(84, 1, 1, 1, 9, 2, 1, 2, 8.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(85, 1, 1, 1, 10, 2, 1, 2, 66.00, '2026-06-24 20:48:43', '2026-06-24 20:48:43', '2026-06-24 20:48:43'),
(86, 1, 4, 3, 6, 2, 1, 2, 5.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(87, 1, 4, 3, 7, 2, 1, 2, 5.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(88, 1, 4, 3, 8, 2, 1, 2, 5.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(89, 1, 4, 3, 9, 2, 1, 2, 5.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(90, 1, 4, 3, 10, 2, 1, 2, 65.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(91, 1, 1, 3, 6, 2, 1, 2, 4.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(92, 1, 1, 3, 7, 2, 1, 2, 5.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(93, 1, 1, 3, 8, 2, 1, 2, 6.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(94, 1, 1, 3, 9, 2, 1, 2, 7.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38'),
(95, 1, 1, 3, 10, 2, 1, 2, 60.00, '2026-06-24 20:49:38', '2026-06-24 20:49:38', '2026-06-24 20:49:38');

-- --------------------------------------------------------

--
-- Table structure for table `score_imports`
--

CREATE TABLE `score_imports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `rows_imported` int(11) NOT NULL DEFAULT 0,
  `rows_failed` int(11) NOT NULL DEFAULT 0,
  `errors` text DEFAULT NULL,
  `status` enum('processing','done','failed') NOT NULL DEFAULT 'processing',
  `imported_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('agODT2rUD3C6TmyOMDt8cN6gK1EnCHXHOXfNHaY9', 6, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiQTJRdGhCcGFlZzlnRzhnNmg4RGRRb3JZZ0tFWjdnOVJYVm53Vm1NZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xNzIuMTcuMTg3LjEzOC9zdHVkZW50L2xvZ2luIjtzOjU6InJvdXRlIjtzOjEzOiJzdHVkZW50LmxvZ2luIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjtzOjk6InRlbmFudF9pZCI7aToxO3M6MTE6InRlbmFudF9zbHVnIjtzOjE4OiJncmVlbmZpZWxkLWFjYWRlbXkiO30=', 1782296996),
('C9OeFeZzXySXKDfdLaOaF9cO48t2mgycdhRT6yDu', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiZmphdlRTYXFTRDJXMkJtZlhERGhMQkxXRHRnVGJEQllSNVVRSVo3VCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjIzOiJpbXBlcnNvbmF0aW5nX3RlbmFudF9pZCI7aToxO3M6MTQ6InN1cGVyX2FkbWluX2lkIjtpOjE7fQ==', 1782296761),
('G349bgLgcuF50nPFjMV4kVkf9YV2lyZDpUtVSmDN', 6, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoid0VnNmNodTVOQ3dLeEUyMXdocWlMbTFVYnFpNktXd3h0dkJySXRweCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xNzIuMTcuMTg3LjEzOC9zdHVkZW50L2xvZ2luIjtzOjU6InJvdXRlIjtzOjEzOiJzdHVkZW50LmxvZ2luIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjtzOjk6InRlbmFudF9pZCI7aToxO3M6MTE6InRlbmFudF9zbHVnIjtzOjE4OiJncmVlbmZpZWxkLWFjYWRlbXkiO30=', 1782293779),
('inOkRCdTxbHHUEjosBFfA64lJITP2KKiKcBx9IjC', 2, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoidU05Wk5JZWJEc2lUNjdPZzZGMlVrV0gzdWIxYmZaMXZORHZCUEhPQiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xNzIuMTcuMTg3LjEzOC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoxMToiYWRtaW4ubG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6OToidGVuYW50X2lkIjtpOjE7czoxMToidGVuYW50X3NsdWciO3M6MTg6ImdyZWVuZmllbGQtYWNhZGVteSI7fQ==', 1782292974),
('lyVxMpQPzrAHB93Ao9kEF54rLA58nXk1NcAzZayn', NULL, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiaVpWd2x6QzhhNDk2NEdyMXRNbkdYTEE1bW8xOEU2T01hWlFKdVZTcCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1782297259),
('yn9jlHQx8I62vm5SJgF3qnDczOZG9cMCfTx7w3md', 1, '172.17.187.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMlBIUEhQdTlsTEpab0hkVWdxa2pTeFZSSlhVSjFFYlljM2VtaGFrZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xNzIuMTcuMTg3LjEzOC9wbGF0Zm9ybS9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1782293185);

-- --------------------------------------------------------

--
-- Table structure for table `skill_definitions`
--

CREATE TABLE `skill_definitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `category` enum('psychomotor','affective') NOT NULL,
  `name` varchar(255) NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_definitions`
--

INSERT INTO `skill_definitions` (`id`, `tenant_id`, `category`, `name`, `order_index`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'psychomotor', 'Handwriting', 1, 1, '2026-06-14 02:57:17', '2026-06-14 02:57:17'),
(2, 1, 'psychomotor', 'Drawing & Painting', 2, 1, '2026-06-14 02:57:17', '2026-06-14 02:57:17'),
(3, 1, 'psychomotor', 'Sports & Games', 3, 1, '2026-06-14 02:57:17', '2026-06-14 02:57:17'),
(4, 1, 'psychomotor', 'Verbal Fluency', 4, 1, '2026-06-14 02:57:17', '2026-06-14 02:57:17'),
(5, 1, 'psychomotor', 'Handling of Tools', 5, 1, '2026-06-14 02:57:17', '2026-06-14 02:57:17'),
(6, 1, 'affective', 'Punctuality', 1, 1, '2026-06-14 02:57:18', '2026-06-14 02:57:18'),
(7, 1, 'affective', 'Attentiveness', 2, 1, '2026-06-14 02:57:18', '2026-06-14 02:57:18'),
(8, 1, 'affective', 'Neatness', 3, 1, '2026-06-14 02:57:18', '2026-06-14 02:57:18'),
(9, 1, 'affective', 'Honesty', 4, 1, '2026-06-14 02:57:18', '2026-06-14 02:57:18'),
(10, 1, 'affective', 'Relationship with Others', 5, 1, '2026-06-14 02:57:18', '2026-06-14 02:57:18');

-- --------------------------------------------------------

--
-- Table structure for table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `audience` enum('all_parents','all_staff','class_parents','custom') NOT NULL DEFAULT 'all_parents',
  `class_arm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','scheduled','sent','failed') NOT NULL DEFAULT 'draft',
  `schedule_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `status` enum('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance_records`
--

CREATE TABLE `staff_attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('early','present','late','absent') NOT NULL DEFAULT 'absent',
  `clock_in_time` time DEFAULT NULL,
  `clock_out_time` time DEFAULT NULL,
  `clock_in_method` enum('qr','proxy','manual','offline') DEFAULT NULL,
  `clocked_in_by` bigint(20) UNSIGNED DEFAULT NULL,
  `clock_in_lat` decimal(10,7) DEFAULT NULL,
  `clock_in_lng` decimal(10,7) DEFAULT NULL,
  `geo_verified` tinyint(1) NOT NULL DEFAULT 0,
  `clock_in_photo` varchar(255) DEFAULT NULL,
  `proxy_photo` varchar(255) DEFAULT NULL,
  `proxy_verified` tinyint(1) NOT NULL DEFAULT 0,
  `proxy_pin_used` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_offline_upload` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_attendance_records`
--

INSERT INTO `staff_attendance_records` (`id`, `tenant_id`, `user_id`, `attendance_date`, `status`, `clock_in_time`, `clock_out_time`, `clock_in_method`, `clocked_in_by`, `clock_in_lat`, `clock_in_lng`, `geo_verified`, `clock_in_photo`, `proxy_photo`, `proxy_verified`, `proxy_pin_used`, `notes`, `is_offline_upload`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026-06-21', 'late', '12:46:06', NULL, 'offline', 2, 9.0140005, 7.5923515, 0, NULL, NULL, 0, 0, NULL, 1, '2026-06-21 18:22:44', '2026-06-21 18:22:44');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance_settings`
--

CREATE TABLE `staff_attendance_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `resumption_time` time NOT NULL DEFAULT '08:00:00',
  `grace_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 15,
  `closing_time` time NOT NULL DEFAULT '15:00:00',
  `geo_lat` decimal(10,7) DEFAULT NULL,
  `geo_lng` decimal(10,7) DEFAULT NULL,
  `geo_radius_meters` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `geo_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `qr_secret` varchar(64) DEFAULT NULL,
  `qr_secret_date` date DEFAULT NULL,
  `permanent_qr_secret` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_attendance_settings`
--

INSERT INTO `staff_attendance_settings` (`id`, `tenant_id`, `resumption_time`, `grace_minutes`, `closing_time`, `geo_lat`, `geo_lng`, `geo_radius_meters`, `geo_enabled`, `qr_secret`, `qr_secret_date`, `permanent_qr_secret`, `created_at`, `updated_at`) VALUES
(1, 1, '07:30:00', 10, '15:00:00', NULL, NULL, 50, 1, '2091e85e134d5279d9b126b091bb649a', '2026-06-21', '039a010b70291c11a6938f0349d05825', '2026-06-18 00:44:38', '2026-06-21 10:43:14');

-- --------------------------------------------------------

--
-- Table structure for table `staff_deductions`
--

CREATE TABLE `staff_deductions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_deduction_template_id` bigint(20) UNSIGNED NOT NULL,
  `custom_amount` decimal(10,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_offline_clockins`
--

CREATE TABLE `staff_offline_clockins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `clocked_by` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in_time` time NOT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `status` enum('pending','applied','rejected') NOT NULL DEFAULT 'pending',
  `reject_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_offline_clockins`
--

INSERT INTO `staff_offline_clockins` (`id`, `tenant_id`, `user_id`, `clocked_by`, `attendance_date`, `clock_in_time`, `qr_token`, `lat`, `lng`, `status`, `reject_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 2, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'applied', NULL, '2026-06-21 18:21:52', '2026-06-21 18:22:45'),
(2, 1, 2, 2, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-21 18:23:02', '2026-06-21 18:23:02'),
(3, 1, 2, 2, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-21 18:23:02', '2026-06-21 18:23:02'),
(4, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-21 19:58:48', '2026-06-21 19:58:48'),
(5, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-21 19:58:48', '2026-06-21 19:58:48'),
(6, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-21 19:59:59', '2026-06-21 19:59:59'),
(7, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-21 19:59:59', '2026-06-21 19:59:59'),
(8, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 01:57:00', '2026-06-22 01:57:00'),
(9, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 01:57:00', '2026-06-22 01:57:00'),
(10, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 01:57:21', '2026-06-22 01:57:21'),
(11, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 01:57:21', '2026-06-22 01:57:21'),
(12, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:13:49', '2026-06-22 02:13:49'),
(13, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:13:49', '2026-06-22 02:13:49'),
(14, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:16:38', '2026-06-22 02:16:38'),
(15, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:16:38', '2026-06-22 02:16:38'),
(16, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:16:48', '2026-06-22 02:16:48'),
(17, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:16:48', '2026-06-22 02:16:48'),
(18, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:16:56', '2026-06-22 02:16:56'),
(19, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:16:56', '2026-06-22 02:16:56'),
(20, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:49:31', '2026-06-22 02:49:31'),
(21, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:49:31', '2026-06-22 02:49:31'),
(22, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:49:50', '2026-06-22 02:49:50'),
(23, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:49:50', '2026-06-22 02:49:50'),
(24, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 02:59:56', '2026-06-22 02:59:56'),
(25, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 02:59:56', '2026-06-22 02:59:56'),
(26, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 03:39:17', '2026-06-22 03:39:17'),
(27, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 03:39:17', '2026-06-22 03:39:17'),
(28, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 03:47:38', '2026-06-22 03:47:38'),
(29, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 03:47:38', '2026-06-22 03:47:38'),
(30, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 03:58:52', '2026-06-22 03:58:52'),
(31, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 03:58:52', '2026-06-22 03:58:52'),
(32, 1, 2, 4, '2026-06-21', '12:46:06', NULL, 9.0140005, 7.5923515, 'pending', NULL, '2026-06-22 08:22:07', '2026-06-22 08:22:07'),
(33, 1, 2, 4, '2026-06-21', '20:22:21', NULL, 10.0000000, 8.0000000, 'pending', NULL, '2026-06-22 08:22:07', '2026-06-22 08:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `staff_permissions`
--

CREATE TABLE `staff_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(60) NOT NULL,
  `type` enum('grant','deny') NOT NULL DEFAULT 'grant',
  `granted_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_proxy_requests`
--

CREATE TABLE `staff_proxy_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `target_user_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in_time` time NOT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `friend_photo_path` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `verification_method` enum('pin','otp') NOT NULL DEFAULT 'pin',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `pin_attempts` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `reject_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_salary_settings`
--

CREATE TABLE `staff_salary_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `annual_rent_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `tax_identification_number` varchar(255) DEFAULT NULL,
  `bvn` varchar(11) DEFAULT NULL,
  `nin` varchar(11) DEFAULT NULL,
  `bank_details_locked` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_salary_settings`
--

INSERT INTO `staff_salary_settings` (`id`, `tenant_id`, `staff_id`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `annual_rent_paid`, `bank_name`, `account_number`, `account_name`, `tax_identification_number`, `bvn`, `nin`, `bank_details_locked`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 160000.00, 4000.00, 2000.00, 3000.00, 0.00, 'Access Bank', 'eyJpdiI6IjFiUFY0bzRDRWFERUNkYUNUU3NBeHc9PSIsInZhbHVlIjoiTnBFeWMyTEp0Rm9ZWk1DV3dRNXFJUT09IiwibWFjIjoiMDYxZGE1Njg4M2QxNWEyMmE1MGY4NWIxYzM0ODE1YWY4MWE0NzU2MTZmOGI5MDkwZWJkYTNjNTExNDhhOTU3YyIsInRhZyI6IiJ9', NULL, NULL, NULL, NULL, 0, 1, '2026-06-21 10:59:30', '2026-06-23 04:32:03'),
(2, 1, 2, 0.00, 0.00, 0.00, 0.00, 0.00, 'Access Bank', '0089187965', 'HARUNA SHAABA ABUBAKAR', '1111111111111', '00000111222', '14212222929', 1, 1, '2026-06-22 02:31:33', '2026-06-23 14:32:19');

-- --------------------------------------------------------

--
-- Table structure for table `staff_status_histories`
--

CREATE TABLE `staff_status_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` varchar(40) DEFAULT NULL,
  `new_status` varchar(40) NOT NULL,
  `effective_date` date NOT NULL,
  `last_working_date` date DEFAULT NULL,
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_work_histories`
--

CREATE TABLE `staff_work_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `position_title` varchar(255) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `employment_type` varchar(255) DEFAULT NULL,
  `functional_role` varchar(255) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `appointment_type` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `change_type` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `current_class_arm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admission_number` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `state_of_origin` varchar(255) DEFAULT NULL,
  `lga_of_origin` varchar(255) DEFAULT NULL,
  `religion` varchar(255) DEFAULT NULL,
  `blood_group` varchar(255) DEFAULT NULL,
  `genotype` varchar(255) DEFAULT NULL,
  `passport_photo_path` varchar(255) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'applicant',
  `admission_date` date DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `tenant_id`, `user_id`, `current_class_arm_id`, `admission_number`, `first_name`, `last_name`, `middle_name`, `gender`, `email`, `date_of_birth`, `state_of_origin`, `lga_of_origin`, `religion`, `blood_group`, `genotype`, `passport_photo_path`, `status`, `admission_date`, `graduation_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 5, 14, 'STU0001', 'Musa', 'Suleiman', NULL, 'male', NULL, '2016-02-20', 'Nasarawa', 'Karu', 'Islam', 'A+', 'AA', NULL, 'active', '2026-06-14', NULL, '2026-06-14 09:08:24', '2026-06-17 07:34:12', NULL),
(4, 1, 6, 14, 'STU0002', 'ASMAU', 'ISHAQ', NULL, 'female', NULL, '2014-03-20', 'TARABA', NULL, 'Islam', NULL, NULL, NULL, 'active', '2026-06-16', NULL, '2026-06-16 14:48:18', '2026-06-21 17:54:37', NULL),
(7, 1, NULL, 1, 'STU0003', 'Amina', 'Bello', NULL, 'female', NULL, NULL, 'Kano', 'Kano Municipal', 'Islam', NULL, NULL, NULL, 'active', '2026-06-21', NULL, '2026-06-21 09:55:23', '2026-06-21 09:55:23', NULL),
(8, 1, NULL, 5, 'STU0005', 'Emeka', 'Okafor', 'Chukwu', 'male', NULL, NULL, 'Anambra', 'Onitsha', 'Christianity', NULL, NULL, NULL, 'active', '2026-06-21', NULL, '2026-06-21 09:55:23', '2026-06-21 09:55:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_class_transfers`
--

CREATE TABLE `student_class_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `academic_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `from_class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `to_class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_class_transfers`
--

INSERT INTO `student_class_transfers` (`id`, `tenant_id`, `student_id`, `academic_session_id`, `term_id`, `from_class_arm_id`, `to_class_arm_id`, `effective_date`, `reason`, `status`, `requested_by`, `approved_by`, `approved_at`, `completed_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `cancelled_by`, `cancelled_at`, `cancellation_reason`, `supporting_document`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1, 1, 14, 15, '2026-06-19', 'Change of course', 'completed', 2, 2, '2026-06-19 21:01:55', '2026-06-19 21:01:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 21:01:36', '2026-06-19 21:01:55');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `ended_by` bigint(20) UNSIGNED DEFAULT NULL,
  `ended_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `tenant_id`, `student_id`, `class_arm_id`, `session_id`, `term_id`, `start_date`, `end_date`, `is_current`, `status`, `created_by`, `ended_by`, `ended_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 14, 1, 1, '2025-09-08', NULL, 1, 'active', NULL, NULL, NULL, '2026-06-19 09:19:24', '2026-06-19 09:19:24'),
(2, 1, 4, 14, 1, 1, '2025-09-08', '2026-06-19', 0, 'transferred', NULL, 2, 'Interclass transfer #1', '2026-06-19 09:20:01', '2026-06-19 21:01:55'),
(3, 1, 4, 15, 1, 1, '2026-06-19', '2026-06-16', 0, 'left', 2, 2, 'Left the school', '2026-06-19 21:01:55', '2026-06-19 21:04:21');

-- --------------------------------------------------------

--
-- Table structure for table `student_health_records`
--

CREATE TABLE `student_health_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `genotype` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `disability` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(255) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `doctor_phone` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_health_records`
--

INSERT INTO `student_health_records` (`id`, `tenant_id`, `student_id`, `blood_group`, `genotype`, `allergies`, `chronic_conditions`, `current_medications`, `disability`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `doctor_name`, `doctor_phone`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 7, 'A+', 'AA', 'Egg', 'Ulcer', NULL, 'Nil', 'Mustapha', '000000000', NULL, NULL, NULL, NULL, '2026-06-21 10:22:25', '2026-06-21 10:22:25');

-- --------------------------------------------------------

--
-- Table structure for table `student_risk_flags`
--

CREATE TABLE `student_risk_flags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `academic_risk` tinyint(4) NOT NULL DEFAULT 0,
  `attendance_risk` tinyint(4) NOT NULL DEFAULT 0,
  `fee_risk` tinyint(4) NOT NULL DEFAULT 0,
  `subjects_failed` tinyint(4) NOT NULL DEFAULT 0,
  `composite_risk` tinyint(4) NOT NULL DEFAULT 0,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flags`)),
  `status` enum('open','acknowledged','resolved') NOT NULL DEFAULT 'open',
  `intervention_note` text DEFAULT NULL,
  `acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_risk_flags`
--

INSERT INTO `student_risk_flags` (`id`, `tenant_id`, `student_id`, `term_id`, `class_arm_id`, `academic_risk`, `attendance_risk`, `fee_risk`, `subjects_failed`, `composite_risk`, `risk_level`, `flags`, `status`, `intervention_note`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `computed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 14, 0, 100, 70, 0, 53, 'high', '[\"critical_absenteeism\",\"fees_overdue\"]', 'open', NULL, NULL, NULL, NULL, NULL, '2026-06-16 13:10:05', '2026-06-16 13:10:05', '2026-06-16 13:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `student_skill_ratings`
--

CREATE TABLE `student_skill_ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `skill_definition_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `rated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_skill_ratings`
--

INSERT INTO `student_skill_ratings` (`id`, `tenant_id`, `student_id`, `skill_definition_id`, `term_id`, `session_id`, `rated_by`, `rating`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 1, 1, 2, 3, '2026-06-14 13:58:34', '2026-06-14 13:58:34'),
(2, 1, 1, 5, 1, 1, 2, 2, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(3, 1, 1, 6, 1, 1, 2, 2, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(4, 1, 1, 7, 1, 1, 2, 3, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(5, 1, 1, 8, 1, 1, 2, 3, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(6, 1, 1, 9, 1, 1, 2, 1, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(7, 1, 1, 10, 1, 1, 2, 1, '2026-06-14 13:58:35', '2026-06-14 13:58:35'),
(8, 1, 4, 1, 1, 1, 2, 2, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(9, 1, 4, 2, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(10, 1, 4, 3, 1, 1, 2, 3, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(11, 1, 4, 4, 1, 1, 2, 2, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(12, 1, 4, 5, 1, 1, 2, 3, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(13, 1, 4, 6, 1, 1, 2, 3, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(14, 1, 4, 7, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(15, 1, 4, 8, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(16, 1, 4, 9, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(17, 1, 4, 10, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(18, 1, 1, 1, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(19, 1, 1, 2, 1, 1, 2, 4, '2026-06-24 20:28:58', '2026-06-24 20:28:58'),
(20, 1, 1, 3, 1, 1, 2, 3, '2026-06-24 20:28:58', '2026-06-24 20:28:58');

-- --------------------------------------------------------

--
-- Table structure for table `student_status_histories`
--

CREATE TABLE `student_status_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` varchar(40) DEFAULT NULL,
  `new_status` varchar(40) NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `destination_school` varchar(255) DEFAULT NULL,
  `transfer_certificate_number` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_status_histories`
--

INSERT INTO `student_status_histories` (`id`, `tenant_id`, `student_id`, `old_status`, `new_status`, `effective_date`, `reason`, `destination_school`, `transfer_certificate_number`, `document_path`, `changed_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'active', 'left', '2026-06-16', 'Left the school', NULL, NULL, NULL, 2, 2, '2026-06-19 21:04:21', '2026-06-19 21:04:21', '2026-06-19 21:04:21'),
(2, 1, 4, 'left', 'active', '2026-06-21', 'Readmission', NULL, NULL, NULL, 2, 2, '2026-06-21 17:54:37', '2026-06-21 17:54:37', '2026-06-21 17:54:37');

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_selections`
--

CREATE TABLE `student_subject_selections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `class_level_id` bigint(20) UNSIGNED NOT NULL,
  `academic_track_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `selection_type` enum('compulsory','elective') NOT NULL DEFAULT 'elective',
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subject_selections`
--

INSERT INTO `student_subject_selections` (`id`, `tenant_id`, `student_id`, `class_level_id`, `academic_track_id`, `subject_id`, `selection_type`, `session_id`, `term_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 6, NULL, 1, 'compulsory', 1, NULL, 1, '2026-06-19 21:01:55', '2026-06-19 21:01:55');

-- --------------------------------------------------------

--
-- Table structure for table `student_transfers`
--

CREATE TABLE `student_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_tenant_id` bigint(20) UNSIGNED NOT NULL,
  `to_tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `admission_number` varchar(255) NOT NULL,
  `status` enum('requested','approved','rejected','completed') NOT NULL DEFAULT 'requested',
  `reason` text DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `tenant_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Biology', 'BIO', 1, '2026-06-14 03:53:32', '2026-06-14 03:53:32', NULL),
(2, 1, 'Mathematics', 'MTH', 1, '2026-06-16 19:15:32', '2026-06-16 19:15:32', NULL),
(3, 1, 'English', 'ENG', 1, '2026-06-16 19:15:56', '2026-06-16 19:15:56', NULL),
(4, 1, 'Chemistry', 'CHM', 1, '2026-06-19 12:14:38', '2026-06-19 12:14:38', NULL),
(5, 1, 'Physics', 'PHY', 1, '2026-06-19 12:14:54', '2026-06-19 12:14:54', NULL),
(6, 1, 'Further Mathematics', 'F/MTH', 1, '2026-06-19 12:15:26', '2026-06-19 12:15:26', NULL),
(7, 1, 'Computer', 'CMP', 1, '2026-06-19 12:15:54', '2026-06-19 12:15:54', NULL),
(8, 1, 'Technical Drawing', 'TD', 1, '2026-06-19 12:16:14', '2026-06-19 12:16:14', NULL),
(9, 1, 'Geography', 'GEO', 1, '2026-06-19 12:16:29', '2026-06-19 12:16:29', NULL),
(10, 1, 'Civic Education', 'CIV', 1, '2026-06-19 12:17:52', '2026-06-19 12:17:52', NULL),
(11, 1, 'Islamic Studies', 'ISL', 1, '2026-06-19 12:18:11', '2026-06-19 12:18:11', NULL),
(12, 1, 'Arabic', 'ARA', 1, '2026-06-21 09:18:49', '2026-06-21 09:18:49', NULL),
(13, 1, 'Hausa', 'HAU', 1, '2026-06-21 09:19:04', '2026-06-21 09:19:04', NULL),
(14, 1, 'Yoruba', 'YOR', 1, '2026-06-21 09:19:24', '2026-06-21 09:19:24', NULL),
(15, 1, 'Physical and Health Education', 'PHE', 1, '2026-06-21 09:21:06', '2026-06-21 09:21:06', NULL),
(16, 1, 'Basic Science', 'B/Sc', 1, '2026-06-21 09:23:24', '2026-06-21 09:23:24', NULL),
(17, 1, 'Basic Technology', 'B/Tech', 1, '2026-06-21 09:23:53', '2026-06-21 09:23:53', NULL),
(18, 1, 'Home Economics', 'H/E', 1, '2026-06-21 09:31:04', '2026-06-21 09:31:04', NULL),
(19, 1, 'Agric', 'AGR', 1, '2026-06-21 09:48:28', '2026-06-21 09:48:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_frequencies`
--

CREATE TABLE `subject_frequencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `periods_per_week` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_frequencies`
--

INSERT INTO `subject_frequencies` (`id`, `tenant_id`, `class_arm_id`, `subject_id`, `session_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES
(1, 1, 14, 1, 1, 4, '2026-06-14 07:50:30', '2026-06-14 07:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `annual_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_students` int(11) NOT NULL DEFAULT 500,
  `max_staff` int(11) NOT NULL DEFAULT 50,
  `has_cbt` tinyint(1) NOT NULL DEFAULT 0,
  `has_sms` tinyint(1) NOT NULL DEFAULT 0,
  `has_paystack` tinyint(1) NOT NULL DEFAULT 0,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `description`, `monthly_price`, `annual_price`, `max_students`, `max_staff`, `has_cbt`, `has_sms`, `has_paystack`, `features`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 'basic', 'Access to core modules', 10000.00, 100000.00, 200, 20, 0, 0, 0, '[\"dashboard\",\"students\",\"student_transfer\",\"student_archive\",\"staff\",\"staff_archive\",\"classes\",\"subjects\",\"academic_cycle\",\"promotion\",\"curriculum\",\"school_setup\",\"admissions\",\"scores\",\"assessment_types\",\"report_cards\",\"broadsheet\",\"skill_ratings\",\"gradebook\",\"staff_attendance\",\"staff_id_cards\",\"fees\",\"invoices\",\"payment_plans\",\"fee_reminders\",\"online_payments\",\"payroll\"]', 1, 1, '2026-06-15 09:42:09', '2026-06-25 22:22:14'),
(2, 'Standard', 'standard', 'All core + Timetable + Student Attendance + Staff Attendance', 30000.00, 250000.00, 1000, 200, 0, 0, 0, '[\"dashboard\",\"students\",\"student_transfer\",\"student_archive\",\"staff\",\"staff_archive\",\"classes\",\"subjects\",\"curriculum\",\"academic_cycle\",\"promotion\",\"school_setup\",\"timetable\",\"scores\",\"report_cards\",\"broadsheet\",\"skill_ratings\",\"gradebook\",\"assessment_types\",\"student_attendance\",\"staff_attendance\",\"staff_id_cards\",\"admissions\"]', 1, 2, '2026-06-15 09:42:09', '2026-06-24 09:49:11'),
(3, 'Premium', 'premium', 'All features in Standard + CBT + Finance', 45000.00, 450000.00, 9999, 999, 1, 0, 1, '[\"dashboard\",\"students\",\"student_transfer\",\"student_archive\",\"staff\",\"staff_archive\",\"classes\",\"subjects\",\"curriculum\",\"academic_cycle\",\"promotion\",\"school_setup\",\"timetable\",\"scores\",\"report_cards\",\"broadsheet\",\"skill_ratings\",\"gradebook\",\"assessment_types\",\"student_attendance\",\"staff_attendance\",\"staff_id_cards\",\"cbt\",\"cbt_essay\",\"cbt_results\",\"fees\",\"invoices\",\"payment_plans\",\"fee_reminders\",\"online_payments\",\"expenses\",\"payroll\",\"financial_report\",\"admissions\"]', 1, 3, '2026-06-15 09:42:09', '2026-06-24 09:49:11'),
(5, 'Free Trial', 'free-trial', '10-day trial — core modules, up to 50 students', 0.00, 0.00, 20, 10, 0, 0, 0, '[\"dashboard\",\"students\",\"classes\",\"subjects\",\"academic_cycle\",\"timetable\",\"scores\",\"report_cards\",\"broadsheet\",\"skill_ratings\",\"student_attendance\",\"fees\",\"calendar\"]', 1, 0, '2026-06-24 08:47:55', '2026-06-24 09:16:01');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `subdomain` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `theme_primary` varchar(7) NOT NULL DEFAULT '#071E45',
  `theme_accent` varchar(7) NOT NULL DEFAULT '#D79A21',
  `theme_sidebar` varchar(7) NOT NULL DEFAULT '#071E45',
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','subscription_expired','pending') NOT NULL DEFAULT 'pending',
  `subscription_expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `motto` varchar(255) DEFAULT NULL,
  `custom_domain` varchar(255) DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `domain_verified` tinyint(1) NOT NULL DEFAULT 0,
  `primary_color` varchar(10) NOT NULL DEFAULT '#2563EB',
  `secondary_color` varchar(10) NOT NULL DEFAULT '#1E40AF',
  `referred_by_agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referral_code_used` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `slug`, `subdomain`, `logo_path`, `theme_primary`, `theme_accent`, `theme_sidebar`, `address`, `phone`, `email`, `status`, `subscription_expires_at`, `created_at`, `updated_at`, `deleted_at`, `motto`, `custom_domain`, `agent_id`, `domain_verified`, `primary_color`, `secondary_color`, `referred_by_agent_id`, `referral_code_used`) VALUES
(1, 'Greenfield Academy', 'greenfield-academy', 'greenfield', 'logos/1/5DAJXjnYwKhjKYKAlCnUgot1cnSAw1G46zyZurry.jpg', '#071E45', '#D79A21', '#071E45', '12 School Road, Maitama, Abuja, FCT', '08012345678', 'info@greenfieldacademy.ng', 'active', '2027-11-14', '2026-06-14 00:37:19', '2026-06-23 19:35:33', NULL, NULL, NULL, NULL, 0, '#2563EB', '#1E40AF', NULL, NULL),
(2, 'Blueray Academy', 'bluerayacademy', NULL, NULL, '#071E45', '#D79A21', '#071E45', 'Ruga Juli, Mararaba, Karu', '08012340095', 'info@bluerayacademy.ng', 'active', '2027-07-19', '2026-06-19 03:42:56', '2026-06-23 19:38:14', NULL, NULL, NULL, NULL, 0, '#2563EB', '#1E40AF', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tenant_subscriptions`
--

CREATE TABLE `tenant_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','expired','cancelled','trial') NOT NULL DEFAULT 'trial',
  `billing_cycle` enum('monthly','annual') NOT NULL DEFAULT 'annual',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `starts_at` date NOT NULL,
  `expires_at` date NOT NULL,
  `next_billing_date` date DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenant_subscriptions`
--

INSERT INTO `tenant_subscriptions` (`id`, `tenant_id`, `plan_id`, `status`, `billing_cycle`, `amount_paid`, `starts_at`, `expires_at`, `next_billing_date`, `payment_reference`, `payment_method`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'active', 'monthly', 15000.00, '2026-06-19', '2027-06-19', '2027-06-19', NULL, NULL, NULL, 1, '2026-06-19 03:42:56', '2026-06-19 03:42:56');

-- --------------------------------------------------------

--
-- Table structure for table `termly_summaries`
--

CREATE TABLE `termly_summaries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `total_score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(8,2) NOT NULL DEFAULT 0.00,
  `final_average` decimal(5,2) NOT NULL DEFAULT 0.00,
  `position_in_class` int(11) DEFAULT NULL,
  `total_students_in_class` int(11) DEFAULT NULL,
  `class_highest_avg` decimal(5,2) DEFAULT NULL,
  `class_lowest_avg` decimal(5,2) DEFAULT NULL,
  `subjects_offered` int(11) NOT NULL DEFAULT 0,
  `subjects_failed` int(11) NOT NULL DEFAULT 0,
  `subject_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subject_breakdown`)),
  `promotion_status` enum('pending','promoted','repeat','graduated') NOT NULL DEFAULT 'pending',
  `form_tutor_remark` text DEFAULT NULL,
  `principal_remark` text DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `termly_summaries`
--

INSERT INTO `termly_summaries` (`id`, `tenant_id`, `student_id`, `class_arm_id`, `term_id`, `session_id`, `total_score`, `grand_total`, `final_average`, `position_in_class`, `total_students_in_class`, `class_highest_avg`, `class_lowest_avg`, `subjects_offered`, `subjects_failed`, `subject_breakdown`, `promotion_status`, `form_tutor_remark`, `principal_remark`, `computed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 14, 1, 1, 185.00, 0.00, 92.50, 2, 2, 92.50, 86.50, 2, 0, '{\"1\":{\"subject_name\":\"Biology\",\"scores\":{\"3\":10,\"4\":10,\"1\":5,\"2\":5,\"5\":65},\"total\":95,\"grade\":\"A1\",\"remark\":\"Excellent\",\"is_pass\":true}}', 'pending', 'Suleiman is a responsible boy.', 'An outstanding result! Musa has shown remarkable dedication and intellectual strength. This level of performance is highly commendable. Well done!', '2026-06-14 09:15:45', '2026-06-14 09:10:37', '2026-06-24 20:29:26'),
(2, 1, 4, 14, 1, 1, 173.00, 0.00, 86.50, 1, 2, 92.50, 86.50, 2, 0, NULL, 'pending', 'Asmau is a responsible girl', 'Asmau has demonstrated exceptional academic excellence this term. A truly outstanding performance that reflects diligence and intellectual ability. Keep soaring!', NULL, '2026-06-16 14:49:30', '2026-06-24 20:29:26'),
(3, 1, 4, 14, 5, 2, 0.00, 0.00, 0.00, 1, 2, 0.00, 0.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, '2026-06-24 13:02:11', '2026-06-24 13:02:11'),
(4, 1, 1, 14, 5, 2, 0.00, 0.00, 0.00, 2, 2, 0.00, 0.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, '2026-06-24 13:02:12', '2026-06-24 13:02:12'),
(5, 1, 4, 14, 2, 1, 237.00, 0.00, 79.00, 1, 2, 79.00, 78.33, 3, 0, NULL, 'pending', NULL, 'Asmau has demonstrated exceptional academic excellence this term. A truly outstanding performance that reflects diligence and intellectual ability. Keep soaring!', NULL, '2026-06-24 20:49:54', '2026-06-24 21:23:17'),
(6, 1, 1, 14, 2, 1, 235.00, 0.00, 78.33, 2, 2, 79.00, 78.33, 3, 0, NULL, 'pending', NULL, NULL, NULL, '2026-06-24 20:49:54', '2026-06-24 21:23:17'),
(7, 1, 4, 14, 3, 1, 221.00, 0.00, 73.67, 1, 2, 75.00, 73.67, 3, 0, NULL, 'pending', NULL, 'Asmau has performed very well this term, demonstrating strong academic commitment. A little more effort will push this student to the very top.', NULL, '2026-06-24 20:52:39', '2026-06-24 22:26:33'),
(8, 1, 1, 14, 3, 1, 225.00, 0.00, 75.00, 2, 2, 75.00, 73.67, 3, 0, NULL, 'pending', NULL, NULL, NULL, '2026-06-24 20:52:39', '2026-06-24 22:26:33');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_term_begins` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `tenant_id`, `session_id`, `name`, `start_date`, `end_date`, `next_term_begins`, `is_current`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '1st Term', '2025-09-08', '2025-12-12', NULL, 1, '2026-06-14 00:37:19', '2026-06-21 18:14:46'),
(2, 1, 1, '2nd Term', '2026-01-12', '2026-04-03', NULL, 0, '2026-06-14 00:37:19', '2026-06-21 17:52:53'),
(3, 1, 1, '3rd Term', '2026-04-27', '2026-07-17', NULL, 0, '2026-06-14 00:37:19', '2026-06-21 17:52:53'),
(5, 1, 2, '1st Term', '2026-09-12', '2026-12-13', NULL, 0, '2026-06-23 11:16:50', '2026-06-23 11:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_configs`
--

CREATE TABLE `timetable_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `school_start` time NOT NULL DEFAULT '07:30:00',
  `school_end` time NOT NULL DEFAULT '14:30:00',
  `periods_per_day` tinyint(3) UNSIGNED NOT NULL DEFAULT 8,
  `period_duration` tinyint(3) UNSIGNED NOT NULL DEFAULT 40,
  `breaks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breaks`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable_configs`
--

INSERT INTO `timetable_configs` (`id`, `tenant_id`, `session_id`, `school_start`, `school_end`, `periods_per_day`, `period_duration`, `breaks`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '08:35:00', '15:00:00', 9, 35, '[{\"after_period\":\"2\",\"duration\":\"30\",\"label\":\"Long Break\"},{\"after_period\":\"5\",\"duration\":\"10\",\"label\":\"Short Break\"},{\"after_period\":\"7\",\"duration\":\"30\",\"label\":\"Prayer\"}]', '2026-06-14 07:49:09', '2026-06-14 07:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_periods`
--

CREATE TABLE `timetable_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `class_arm_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable_periods`
--

INSERT INTO `timetable_periods` (`id`, `tenant_id`, `class_arm_id`, `subject_id`, `teacher_id`, `session_id`, `day_of_week`, `start_time`, `end_time`, `venue`, `created_at`, `updated_at`) VALUES
(1, 1, 14, 1, NULL, 1, 'monday', '08:35:00', '09:10:00', NULL, '2026-06-14 07:55:18', '2026-06-14 07:55:18'),
(2, 1, 14, 1, NULL, 1, 'monday', '09:10:00', '09:45:00', NULL, '2026-06-14 07:55:18', '2026-06-14 07:55:18'),
(3, 1, 14, 1, NULL, 1, 'monday', '10:15:00', '10:50:00', NULL, '2026-06-14 07:55:18', '2026-06-14 07:55:18'),
(4, 1, 14, 1, NULL, 1, 'monday', '10:50:00', '11:25:00', NULL, '2026-06-14 07:55:18', '2026-06-14 07:55:18');

-- --------------------------------------------------------

--
-- Table structure for table `transport_assignments`
--

CREATE TABLE `transport_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `route_id` bigint(20) UNSIGNED NOT NULL,
  `pickup_stop` varchar(150) DEFAULT NULL,
  `direction` enum('both','morning','evening') NOT NULL DEFAULT 'both',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transport_assignments`
--

INSERT INTO `transport_assignments` (`id`, `tenant_id`, `student_id`, `route_id`, `pickup_stop`, `direction`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1, NULL, 'both', '2026-06-17 23:12:03', '2026-06-17 23:12:03'),
(2, 1, 7, 1, 'Main Junction', 'both', '2026-06-21 10:17:50', '2026-06-21 10:17:50'),
(3, 1, 8, 1, 'Main Junction', 'both', '2026-06-21 10:18:25', '2026-06-21 10:18:25');

-- --------------------------------------------------------

--
-- Table structure for table `transport_buses`
--

CREATE TABLE `transport_buses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `plate_number` varchar(30) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `year` smallint(5) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transport_buses`
--

INSERT INTO `transport_buses` (`id`, `tenant_id`, `plate_number`, `model`, `capacity`, `year`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'ABC-213-ABJ', 'Hummer Bus', 18, 2026, 1, '2026-06-21 10:17:11', '2026-06-21 10:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `transport_routes`
--

CREATE TABLE `transport_routes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `morning_time` varchar(10) DEFAULT NULL,
  `evening_time` varchar(10) DEFAULT NULL,
  `bus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assistant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transport_routes`
--

INSERT INTO `transport_routes` (`id`, `tenant_id`, `name`, `description`, `fare`, `morning_time`, `evening_time`, `bus_id`, `driver_id`, `assistant_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gwarimpa', NULL, 50000.00, '06:00', '05:30', NULL, 8, NULL, 1, '2026-06-17 23:11:16', '2026-06-17 23:33:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `staff_id` varchar(40) DEFAULT NULL,
  `attendance_pin` varchar(255) DEFAULT NULL,
  `qr_secret` varchar(64) DEFAULT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `role` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `employment_status` varchar(40) NOT NULL DEFAULT 'active',
  `employment_started_at` date DEFAULT NULL,
  `employment_ended_at` date DEFAULT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `exit_reason` text DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_parent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `name`, `staff_id`, `attendance_pin`, `qr_secret`, `student_id`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_confirmed_at`, `is_super_admin`, `role`, `is_active`, `employment_status`, `employment_started_at`, `employment_ended_at`, `status_changed_at`, `exit_reason`, `last_login_at`, `phone`, `date_of_birth`, `address`, `passport_photo`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `is_parent`) VALUES
(1, NULL, 'Super Administrator', NULL, NULL, NULL, NULL, 'superadmin@sms.ng', NULL, '$2y$12$KOVRGWaM9mfUmhYYBPpos.z05dXOXKOkdo6g80UlVbX5G9KH0rd92', NULL, NULL, 1, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UAe3qvNkQ53MC5sUbgaasvt2DR7JQDeZaUilgutdcmuiUoJGhmahwMkiChUh', '2026-06-14 00:37:19', '2026-06-14 00:37:19', NULL, 0),
(2, 1, 'Haruna Abubakar', NULL, '$2y$12$bWrurRrgt6O8qlR3NthD2eVh9HCIeDXu.MM.YxxNsINvyp4bWyeZy', '7c357a9bb7f029ebe9bf250cf10981f9', NULL, 'admin@greenfieldacademy.ng', NULL, '$2y$12$k21F/nB/.CsifryF/i4jpeQrCpgjorDrZf1OhM1uc3J0.d3ZFg14S', NULL, NULL, 0, 'admin', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-27 05:17:01', NULL, '1988-03-23', NULL, 'passports/hYMRBFdyCVWaG0eV7HicyBTuTDB7E3KiLpiAMj4F.jpg', 'Iu6t8sZq9HT4QihCv1SoRLNICJZnahb2KVROHMMuLffztq3aFLW9yWD7Bfvt', '2026-06-14 00:37:19', '2026-06-27 05:17:01', NULL, 0),
(3, 1, 'Musa Salawudeen', NULL, NULL, NULL, NULL, 'abcd@gmail.com', NULL, '$2y$12$qFZwXFqUyHgMQWQih/fhqOoAcMQ3pWwlvnXpidHu83JIcn2yBr0J6', NULL, NULL, 0, 'form_subject_teacher', 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 13:07:41', '2026-06-14 13:07:41', NULL, 0),
(4, 1, 'Ayatu Abuh', 'STF1001', NULL, '518c3f836aeff0d5f95e9588ac58759e', NULL, 'bbbb@gmail.com', NULL, '$2y$12$ZUVB6Y4S1LDDEU5nlJJi7ejiKcxbCxcTsWWuvfx4g6QBrkOSokGfq', NULL, NULL, 0, 'form_subject_teacher', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-25 23:28:36', NULL, NULL, NULL, NULL, NULL, '2026-06-17 04:47:29', '2026-06-25 23:28:36', NULL, 0),
(5, 1, 'Musa  Suleiman', NULL, NULL, NULL, NULL, 'musa@gmail.com', NULL, '$2y$12$3xrAvfinPRfk6PyBPz8RKuG9GPW1qNSVxKgfhuHQaE69vi5FGF3OO', NULL, NULL, 0, 'student', 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-17 07:34:12', '2026-06-21 13:03:42', NULL, 0),
(6, 1, 'ASMAU  ISHAQ', NULL, NULL, NULL, NULL, 'asmau@gmail.com', NULL, '$2y$12$wsrETfYKuEZ0LqTq3z/41uJt/f.pG9tIeSmy78etYqNdaOL5GzC16', NULL, NULL, 0, 'student', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-25 16:32:59', NULL, NULL, NULL, NULL, NULL, '2026-06-17 07:37:06', '2026-06-25 16:32:59', NULL, 0),
(7, 1, 'ISHAK SALEH', NULL, NULL, NULL, NULL, 'ishak@gmail.com', NULL, '$2y$12$W1Dy5uccZHkVZHElDjEoFekPAmkqB.u5ckCy7BHK7RGR7phfhoODK', NULL, NULL, 0, 'parent', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-25 23:22:01', NULL, NULL, NULL, NULL, NULL, '2026-06-17 20:01:33', '2026-06-25 23:22:01', NULL, 0),
(8, 1, 'Muhammad Hussain', 'STF1002', NULL, NULL, NULL, 'ghn@gmail.com', NULL, '$2y$12$S3k4egbUi6UInxn7BUXVY.6uv2/Roo7J1Yay/fuwjW2791.RWi6JC', NULL, NULL, 0, 'driver', 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-17 23:22:49', '2026-06-17 23:22:49', NULL, 0),
(9, 1, 'Fatima Abubakar', 'STF1003', NULL, NULL, NULL, 'def@gmail.com', NULL, '$2y$12$ojO7aZtQngJ1OduNu0UgnuvR/5ZyunufPKiftcdUc3eaK5YAz25kK', NULL, NULL, 0, 'admission_officer', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-18 18:27:06', NULL, NULL, NULL, NULL, NULL, '2026-06-18 18:13:28', '2026-06-18 18:27:06', NULL, 0),
(10, 1, 'Kabiru Musa', 'STF1004', NULL, NULL, NULL, 'ghgj@gmail.com', NULL, '$2y$12$CiZB1DUFFs9t0xjSFn4x2.9nPbF6kcbcbjxQJPCfLqKUNzLTk50hW', NULL, NULL, 0, 'subject_teacher', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-18 19:38:02', NULL, NULL, NULL, NULL, NULL, '2026-06-18 18:30:31', '2026-06-18 19:38:02', NULL, 0),
(11, 1, 'Kudirat Raji', 'STF1005', NULL, NULL, NULL, 'adc@gmail.com', NULL, '$2y$12$IOqwXd8MOnn0m4XlCsIAFesGETgb5OonoprB/x6h8If0QOQFgyvEC', NULL, NULL, 0, 'accountant', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-18 20:52:42', NULL, NULL, NULL, NULL, NULL, '2026-06-18 20:51:06', '2026-06-18 20:52:42', NULL, 0),
(12, 2, 'Yunusa Abubakar', NULL, NULL, NULL, NULL, 'yoonus@gmail.com', NULL, '$2y$12$OnpXy8H/HtN8c.9p9iGfzO3JSO4wGDBdWjnj9OfllkAh32pSyEEu6', NULL, NULL, 0, 'admin', 1, 'active', NULL, NULL, NULL, NULL, '2026-06-21 07:12:10', NULL, NULL, NULL, NULL, NULL, '2026-06-19 03:42:57', '2026-06-21 07:12:10', NULL, 0),
(13, 1, 'Ibrahim Bello', NULL, NULL, NULL, NULL, 'Bello@gmail.com', NULL, '$2y$12$mJdVRCQXNBArxbDjpnAcxO.0ql1KSRRNfF8ZmRK4tyutx8P3HzTM6', NULL, NULL, 0, 'parent', 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 16:46:18', '2026-06-25 16:46:18', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_acadsessions_tenant_current` (`tenant_id`,`is_current`);

--
-- Indexes for table `academic_tracks`
--
ALTER TABLE `academic_tracks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_tracks_slug_unique` (`slug`),
  ADD KEY `at_tid_active` (`tenant_id`,`is_active`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admissions_application_number_unique` (`application_number`),
  ADD KEY `admissions_tenant_id_index` (`tenant_id`),
  ADD KEY `admissions_tenant_id_status_index` (`tenant_id`,`status`);

--
-- Indexes for table `admission_documents`
--
ALTER TABLE `admission_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admission_documents_admission_id_document_type_index` (`admission_id`,`document_type`);

--
-- Indexes for table `admission_portal_settings`
--
ALTER TABLE `admission_portal_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_portal_settings_tenant_id_unique` (`tenant_id`);

--
-- Indexes for table `agent_messages`
--
ALTER TABLE `agent_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent_message_reads`
--
ALTER TABLE `agent_message_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_message_reads_message_id_agent_id_unique` (`message_id`,`agent_id`);

--
-- Indexes for table `agent_payouts`
--
ALTER TABLE `agent_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_payouts_agent_id_foreign` (`agent_id`);

--
-- Indexes for table `agent_referrals`
--
ALTER TABLE `agent_referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_referrals_agent_id_index` (`agent_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcements_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `assessment_types`
--
ALTER TABLE `assessment_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_asstypes_term` (`term_id`),
  ADD KEY `idx_asstypes_tenant_term` (`tenant_id`,`term_id`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_daily_attendance` (`tenant_id`,`student_id`,`attendance_date`),
  ADD KEY `fk_attendance_student` (`student_id`),
  ADD KEY `fk_attendance_classarm` (`class_arm_id`),
  ADD KEY `fk_attendance_term` (`term_id`),
  ADD KEY `fk_attendance_markedby` (`marked_by`),
  ADD KEY `idx_attendance_arm_date` (`tenant_id`,`class_arm_id`,`attendance_date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditlogs_tenant` (`tenant_id`),
  ADD KEY `idx_auditlogs_actor` (`actor_user_id`),
  ADD KEY `idx_auditlogs_auditable` (`auditable_type`,`auditable_id`),
  ADD KEY `idx_auditlogs_action` (`action`),
  ADD KEY `idx_auditlogs_created` (`created_at`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calendar_events_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `cbt_exams`
--
ALTER TABLE `cbt_exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cbtexams_bank` (`question_bank_id`),
  ADD KEY `fk_cbtexams_term` (`term_id`),
  ADD KEY `fk_cbtexams_classarm` (`class_arm_id`),
  ADD KEY `idx_cbtexams_arm_term` (`tenant_id`,`class_arm_id`,`term_id`),
  ADD KEY `idx_cbtexams_status` (`tenant_id`,`status`);

--
-- Indexes for table `cbt_questions`
--
ALTER TABLE `cbt_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cbtqs_bank` (`question_bank_id`),
  ADD KEY `idx_cbtqs_bank` (`tenant_id`,`question_bank_id`);

--
-- Indexes for table `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cbtbanks_subject` (`subject_id`),
  ADD KEY `fk_cbtbanks_classlevel` (`class_level_id`),
  ADD KEY `idx_cbtbanks_subject_level` (`tenant_id`,`subject_id`,`class_level_id`);

--
-- Indexes for table `cbt_student_sessions`
--
ALTER TABLE `cbt_student_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cbtsession` (`tenant_id`,`cbt_exam_id`,`student_id`),
  ADD KEY `fk_cbtsessions_exam` (`cbt_exam_id`),
  ADD KEY `fk_cbtsessions_student` (`student_id`),
  ADD KEY `idx_cbtsessions_exam_status` (`tenant_id`,`cbt_exam_id`,`status`);

--
-- Indexes for table `class_arms`
--
ALTER TABLE `class_arms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_classarms_classlevel` (`class_level_id`),
  ADD KEY `fk_classarms_formtutor` (`form_tutor_id`),
  ADD KEY `idx_classarms_tenant_level` (`tenant_id`,`class_level_id`),
  ADD KEY `ca_track_id` (`academic_track_id`);

--
-- Indexes for table `class_arm_subjects`
--
ALTER TABLE `class_arm_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_armsubject_session` (`tenant_id`,`class_arm_id`,`subject_id`,`session_id`),
  ADD KEY `fk_armsubjects_arm` (`class_arm_id`),
  ADD KEY `fk_armsubjects_subject` (`subject_id`),
  ADD KEY `fk_armsubjects_teacher` (`teacher_id`),
  ADD KEY `fk_armsubjects_session` (`session_id`);

--
-- Indexes for table `class_levels`
--
ALTER TABLE `class_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_classlevels_tenant_section` (`tenant_id`,`section`);

--
-- Indexes for table `class_level_subjects`
--
ALTER TABLE `class_level_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cls_unique` (`tenant_id`,`class_level_id`,`academic_track_id`,`subject_id`),
  ADD KEY `cls_tid_lvl_trk` (`tenant_id`,`class_level_id`,`academic_track_id`),
  ADD KEY `cls_tid_subj` (`tenant_id`,`subject_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feecats_subaccount` (`school_bank_subaccount_id`),
  ADD KEY `idx_feecats_tenant` (`tenant_id`);

--
-- Indexes for table `fee_installments`
--
ALTER TABLE `fee_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_installments_invoice_id_index` (`invoice_id`),
  ADD KEY `fee_installments_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `fee_installments_tenant_id_due_date_index` (`tenant_id`,`due_date`);

--
-- Indexes for table `fee_payment_plans`
--
ALTER TABLE `fee_payment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_payment_plans_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_reminders_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_feestruct` (`tenant_id`,`fee_category_id`,`class_level_id`,`term_id`),
  ADD KEY `fk_feestructs_feecat` (`fee_category_id`),
  ADD KEY `fk_feestructs_classlevel` (`class_level_id`),
  ADD KEY `fk_feestructs_term` (`term_id`),
  ADD KEY `idx_feestructs_level_term` (`tenant_id`,`class_level_id`,`term_id`);

--
-- Indexes for table `grading_systems`
--
ALTER TABLE `grading_systems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grading_classlevel` (`class_level_id`),
  ADD KEY `idx_grading_tenant_level` (`tenant_id`,`class_level_id`);

--
-- Indexes for table `guardians`
--
ALTER TABLE `guardians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_guardians_user` (`user_id`),
  ADD KEY `idx_guardians_tenant` (`tenant_id`);

--
-- Indexes for table `guardian_student`
--
ALTER TABLE `guardian_student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_guardstudt_pair` (`guardian_id`,`student_id`),
  ADD KEY `fk_guardstudt_tenant` (`tenant_id`),
  ADD KEY `fk_guardstudt_student` (`student_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invoice` (`tenant_id`,`student_id`,`term_id`,`session_id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `fk_invoices_student` (`student_id`),
  ADD KEY `fk_invoices_term` (`term_id`),
  ADD KEY `fk_invoices_session` (`session_id`),
  ADD KEY `idx_invoices_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_invoices_tenant_student` (`tenant_id`,`student_id`);

--
-- Indexes for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_invdisc_tenant` (`tenant_id`),
  ADD KEY `fk_invdisc_invoice` (`invoice_id`),
  ADD KEY `fk_invdisc_approvedby` (`approved_by`);

--
-- Indexes for table `invoice_discount_templates`
--
ALTER TABLE `invoice_discount_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_discount_templates_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `invoice_generation_batches`
--
ALTER TABLE `invoice_generation_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_generation_batches_tenant_id_index` (`tenant_id`),
  ADD KEY `invoice_generation_batches_term_id_index` (`term_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_invitems_invoice` (`invoice_id`),
  ADD KEY `fk_invitems_feecat` (`fee_category_id`),
  ADD KEY `idx_invitems_invoice` (`tenant_id`,`invoice_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_payments_payment_reference_unique` (`payment_reference`),
  ADD KEY `invoice_payments_tenant_id_invoice_id_index` (`tenant_id`,`invoice_id`),
  ADD KEY `invoice_payments_tenant_id_student_id_index` (`tenant_id`,`student_id`),
  ADD KEY `invoice_payments_payment_date_index` (`payment_date`),
  ADD KEY `invoice_payments_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_payments_student_id_foreign` (`student_id`),
  ADD KEY `invoice_payments_received_by_foreign` (`received_by`);

--
-- Indexes for table `invoice_payment_plans`
--
ALTER TABLE `invoice_payment_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_payment_plans_invoice_id_unique` (`invoice_id`),
  ADD KEY `invoice_payment_plans_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lesson_plans`
--
ALTER TABLE `lesson_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_plans_tenant_id_teacher_id_index` (`tenant_id`,`teacher_id`),
  ADD KEY `lesson_plans_tenant_id_subject_id_class_level_id_index` (`tenant_id`,`subject_id`,`class_level_id`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `library_books_tenant_id_index` (`tenant_id`),
  ADD KEY `library_books_tenant_id_category_index` (`tenant_id`,`category`);

--
-- Indexes for table `library_loans`
--
ALTER TABLE `library_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `library_loans_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `message_threads`
--
ALTER TABLE `message_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_threads_tenant_id_index` (`tenant_id`),
  ADD KEY `message_threads_student_id_index` (`student_id`);

--
-- Indexes for table `message_thread_replies`
--
ALTER TABLE `message_thread_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_thread_replies_thread_id_index` (`thread_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_student` (`student_id`),
  ADD KEY `fk_notif_guardian` (`guardian_id`),
  ADD KEY `idx_notif_channel_status` (`tenant_id`,`channel`,`status`),
  ADD KEY `idx_notif_sent_at` (`tenant_id`,`sent_at`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_queue_tenant_id_status_index` (`tenant_id`,`status`);

--
-- Indexes for table `notification_queues`
--
ALTER TABLE `notification_queues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_queues_tenant_id_status_index` (`tenant_id`,`status`);

--
-- Indexes for table `notification_triggers`
--
ALTER TABLE `notification_triggers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_triggers_tenant_id_event_unique` (`tenant_id`,`event`);

--
-- Indexes for table `notification_trigger_logs`
--
ALTER TABLE `notification_trigger_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_trigger_logs_tenant_id_event_index` (`tenant_id`,`event`);

--
-- Indexes for table `online_payment_logs`
--
ALTER TABLE `online_payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `online_payment_logs_reference_unique` (`reference`),
  ADD KEY `online_payment_logs_tenant_id_index` (`tenant_id`),
  ADD KEY `online_payment_logs_reference_index` (`reference`);

--
-- Indexes for table `parent_messages`
--
ALTER TABLE `parent_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_messages_tenant_id_index` (`tenant_id`),
  ADD KEY `parent_messages_to_user_id_is_read_index` (`to_user_id`,`is_read`);

--
-- Indexes for table `parent_portal_accounts`
--
ALTER TABLE `parent_portal_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_portal_accounts_email_unique` (`email`),
  ADD KEY `parent_portal_accounts_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_gateway_configs`
--
ALTER TABLE `payment_gateway_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_gateway_configs_tenant_id_unique` (`tenant_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_transactions_gateway_reference_unique` (`gateway_reference`),
  ADD KEY `fk_payments_invoice` (`invoice_id`),
  ADD KEY `fk_payments_student` (`student_id`),
  ADD KEY `idx_payments_invoice` (`tenant_id`,`invoice_id`),
  ADD KEY `idx_payments_status` (`tenant_id`,`status`),
  ADD KEY `idx_payments_paid_at` (`tenant_id`,`paid_at`);

--
-- Indexes for table `payroll_deduction_templates`
--
ALTER TABLE `payroll_deduction_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_deduction_templates_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_items_tenant_id_index` (`tenant_id`),
  ADD KEY `payroll_items_payroll_period_id_staff_id_index` (`payroll_period_id`,`staff_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_periods_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `payroll_role_templates`
--
ALTER TABLE `payroll_role_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_role_templates_tenant_id_role_unique` (`tenant_id`,`role`);

--
-- Indexes for table `payroll_tax_bands`
--
ALTER TABLE `payroll_tax_bands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_tax_bands_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `platform_agents`
--
ALTER TABLE `platform_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_agents_email_unique` (`email`),
  ADD UNIQUE KEY `platform_agents_referral_code_unique` (`referral_code`);

--
-- Indexes for table `platform_invoices`
--
ALTER TABLE `platform_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `platform_invoices_tenant_id_index` (`tenant_id`),
  ADD KEY `platform_invoices_status_index` (`status`);

--
-- Indexes for table `platform_payments`
--
ALTER TABLE `platform_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_payments_reference_unique` (`reference`),
  ADD KEY `fk_ppay_tenant` (`tenant_id`),
  ADD KEY `fk_ppay_sub` (`subscription_id`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_settings_key_unique` (`key`);

--
-- Indexes for table `promotion_rules`
--
ALTER TABLE `promotion_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_promrules_tenant_level` (`tenant_id`,`class_level_id`),
  ADD KEY `fk_promrules_classlevel` (`class_level_id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  ADD KEY `push_subscriptions_user_id_index` (`user_id`);

--
-- Indexes for table `report_card_publications`
--
ALTER TABLE `report_card_publications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_card_publications_class_arm_id_term_id_unique` (`class_arm_id`,`term_id`),
  ADD KEY `report_card_publications_tenant_id_term_id_status_index` (`tenant_id`,`term_id`,`status`);

--
-- Indexes for table `risk_threshold_configs`
--
ALTER TABLE `risk_threshold_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `risk_threshold_configs_tenant_id_unique` (`tenant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `school_bank_subaccounts`
--
ALTER TABLE `school_bank_subaccounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banksubaccts_active` (`tenant_id`,`is_active`);

--
-- Indexes for table `school_expenses`
--
ALTER TABLE `school_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_expenses_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `school_groups`
--
ALTER TABLE `school_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_groups_slug_unique` (`slug`);

--
-- Indexes for table `school_group_members`
--
ALTER TABLE `school_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_group_members_group_id_tenant_id_unique` (`group_id`,`tenant_id`),
  ADD KEY `school_group_members_group_id_index` (`group_id`);

--
-- Indexes for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_settings_tenant_id_key_unique` (`tenant_id`,`key`),
  ADD KEY `school_settings_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_score_entry` (`tenant_id`,`student_id`,`subject_id`,`assessment_type_id`,`term_id`),
  ADD KEY `fk_scores_student` (`student_id`),
  ADD KEY `fk_scores_subject` (`subject_id`),
  ADD KEY `fk_scores_asstype` (`assessment_type_id`),
  ADD KEY `fk_scores_term` (`term_id`),
  ADD KEY `fk_scores_session` (`session_id`),
  ADD KEY `fk_scores_enteredby` (`entered_by`),
  ADD KEY `idx_scores_term_subject` (`tenant_id`,`term_id`,`subject_id`),
  ADD KEY `idx_scores_student_term` (`tenant_id`,`student_id`,`term_id`);

--
-- Indexes for table `score_imports`
--
ALTER TABLE `score_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `score_imports_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `skill_definitions`
--
ALTER TABLE `skill_definitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_skilldef_tenant_cat` (`tenant_id`,`category`);

--
-- Indexes for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sms_campaigns_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sms_logs_campaign_id_index` (`campaign_id`);

--
-- Indexes for table `staff_attendance_records`
--
ALTER TABLE `staff_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_att_unique` (`tenant_id`,`user_id`,`attendance_date`);

--
-- Indexes for table `staff_attendance_settings`
--
ALTER TABLE `staff_attendance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_attendance_settings_tenant_id_unique` (`tenant_id`);

--
-- Indexes for table `staff_deductions`
--
ALTER TABLE `staff_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_deductions_tenant_id_index` (`tenant_id`),
  ADD KEY `staff_deductions_staff_id_is_active_index` (`staff_id`,`is_active`);

--
-- Indexes for table `staff_offline_clockins`
--
ALTER TABLE `staff_offline_clockins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_permissions_tenant_id_user_id_module_unique` (`tenant_id`,`user_id`,`module`),
  ADD KEY `staff_permissions_tenant_id_user_id_index` (`tenant_id`,`user_id`);

--
-- Indexes for table `staff_proxy_requests`
--
ALTER TABLE `staff_proxy_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_salary_settings`
--
ALTER TABLE `staff_salary_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_salary_settings_staff_id_unique` (`staff_id`),
  ADD KEY `staff_salary_settings_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `staff_status_histories`
--
ALTER TABLE `staff_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staffstatushist_user` (`user_id`),
  ADD KEY `fk_staffstatushist_changed_by` (`changed_by`),
  ADD KEY `fk_staffstatushist_approved_by` (`approved_by`),
  ADD KEY `idx_staffstatushist_tenant_user` (`tenant_id`,`user_id`),
  ADD KEY `idx_staffstatushist_tenant_old` (`tenant_id`,`old_status`),
  ADD KEY `idx_staffstatushist_tenant_new` (`tenant_id`,`new_status`),
  ADD KEY `idx_staffstatushist_tenant_effective` (`tenant_id`,`effective_date`);

--
-- Indexes for table `staff_work_histories`
--
ALTER TABLE `staff_work_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staffworkhist_user` (`user_id`),
  ADD KEY `fk_staffworkhist_recorded_by` (`recorded_by`),
  ADD KEY `fk_staffworkhist_approved_by` (`approved_by`),
  ADD KEY `idx_staffworkhist_tenant_user` (`tenant_id`,`user_id`),
  ADD KEY `idx_staffworkhist_tenant_change` (`tenant_id`,`change_type`),
  ADD KEY `idx_staffworkhist_tenant_start` (`tenant_id`,`start_date`),
  ADD KEY `idx_staffworkhist_tenant_end` (`tenant_id`,`end_date`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_tenant_admno` (`tenant_id`,`admission_number`),
  ADD KEY `fk_students_user` (`user_id`),
  ADD KEY `fk_students_classarm` (`current_class_arm_id`),
  ADD KEY `idx_students_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_students_tenant_arm` (`tenant_id`,`current_class_arm_id`);

--
-- Indexes for table `student_class_transfers`
--
ALTER TABLE `student_class_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stuclasstrans_student` (`student_id`),
  ADD KEY `fk_stuclasstrans_session` (`academic_session_id`),
  ADD KEY `fk_stuclasstrans_term` (`term_id`),
  ADD KEY `fk_stuclasstrans_from_arm` (`from_class_arm_id`),
  ADD KEY `fk_stuclasstrans_to_arm` (`to_class_arm_id`),
  ADD KEY `fk_stuclasstrans_requested_by` (`requested_by`),
  ADD KEY `fk_stuclasstrans_approved_by` (`approved_by`),
  ADD KEY `fk_stuclasstrans_rejected_by` (`rejected_by`),
  ADD KEY `fk_stuclasstrans_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_stuclasstrans_tenant_student` (`tenant_id`,`student_id`),
  ADD KEY `idx_stuclasstrans_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_stuclasstrans_tenant_session` (`tenant_id`,`academic_session_id`),
  ADD KEY `idx_stuclasstrans_tenant_term` (`tenant_id`,`term_id`),
  ADD KEY `idx_stuclasstrans_tenant_from` (`tenant_id`,`from_class_arm_id`),
  ADD KEY `idx_stuclasstrans_tenant_to` (`tenant_id`,`to_class_arm_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_enrollments_student` (`student_id`),
  ADD KEY `fk_enrollments_classarm` (`class_arm_id`),
  ADD KEY `fk_enrollments_session` (`session_id`),
  ADD KEY `fk_enrollments_term` (`term_id`),
  ADD KEY `idx_enrollment_arm_session` (`tenant_id`,`class_arm_id`,`session_id`),
  ADD KEY `fk_enrollments_created_by` (`created_by`),
  ADD KEY `fk_enrollments_ended_by` (`ended_by`),
  ADD KEY `idx_enroll_tenant_student` (`tenant_id`,`student_id`),
  ADD KEY `idx_enroll_tenant_student_current` (`tenant_id`,`student_id`,`is_current`),
  ADD KEY `idx_enroll_tenant_arm_current` (`tenant_id`,`class_arm_id`,`is_current`),
  ADD KEY `idx_enroll_tenant_student_session_term` (`tenant_id`,`student_id`,`session_id`,`term_id`),
  ADD KEY `idx_enroll_tenant_status` (`tenant_id`,`status`);

--
-- Indexes for table `student_health_records`
--
ALTER TABLE `student_health_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_health_records_tenant_id_student_id_unique` (`tenant_id`,`student_id`),
  ADD KEY `student_health_records_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `student_risk_flags`
--
ALTER TABLE `student_risk_flags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_risk_flags_student_id_term_id_unique` (`student_id`,`term_id`),
  ADD KEY `student_risk_flags_tenant_id_term_id_risk_level_index` (`tenant_id`,`term_id`,`risk_level`),
  ADD KEY `student_risk_flags_student_id_term_id_index` (`student_id`,`term_id`);

--
-- Indexes for table `student_skill_ratings`
--
ALTER TABLE `student_skill_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_skill_rating` (`tenant_id`,`student_id`,`skill_definition_id`,`term_id`),
  ADD KEY `fk_skillrating_student` (`student_id`),
  ADD KEY `fk_skillrating_skilldef` (`skill_definition_id`),
  ADD KEY `fk_skillrating_term` (`term_id`),
  ADD KEY `fk_skillrating_session` (`session_id`),
  ADD KEY `fk_skillrating_ratedby` (`rated_by`),
  ADD KEY `idx_skillrating_student_term` (`tenant_id`,`student_id`,`term_id`);

--
-- Indexes for table `student_status_histories`
--
ALTER TABLE `student_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stustatushist_student` (`student_id`),
  ADD KEY `fk_stustatushist_changed_by` (`changed_by`),
  ADD KEY `fk_stustatushist_approved_by` (`approved_by`),
  ADD KEY `idx_stustatushist_tenant_student` (`tenant_id`,`student_id`),
  ADD KEY `idx_stustatushist_tenant_old` (`tenant_id`,`old_status`),
  ADD KEY `idx_stustatushist_tenant_new` (`tenant_id`,`new_status`),
  ADD KEY `idx_stustatushist_tenant_effective` (`tenant_id`,`effective_date`);

--
-- Indexes for table `student_subject_selections`
--
ALTER TABLE `student_subject_selections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sss_unique` (`tenant_id`,`student_id`,`subject_id`,`session_id`),
  ADD KEY `sss_tid_stu` (`tenant_id`,`student_id`),
  ADD KEY `sss_tid_lvl_trk` (`tenant_id`,`class_level_id`,`academic_track_id`);

--
-- Indexes for table `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_transfers_from_tenant_id_index` (`from_tenant_id`),
  ADD KEY `student_transfers_to_tenant_id_index` (`to_tenant_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subjects_tenant_code` (`tenant_id`,`code`),
  ADD KEY `idx_subjects_tenant_active` (`tenant_id`,`is_active`);

--
-- Indexes for table `subject_frequencies`
--
ALTER TABLE `subject_frequencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subject_frequency` (`tenant_id`,`class_arm_id`,`subject_id`,`session_id`),
  ADD KEY `fk_subfreq_classarm` (`class_arm_id`),
  ADD KEY `fk_subfreq_subject` (`subject_id`),
  ADD KEY `fk_subfreq_session` (`session_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_plans_slug_unique` (`slug`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_slug_unique` (`slug`),
  ADD UNIQUE KEY `tenants_subdomain_unique` (`subdomain`);

--
-- Indexes for table `tenant_subscriptions`
--
ALTER TABLE `tenant_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tsub_plan` (`plan_id`),
  ADD KEY `idx_tsub_status` (`tenant_id`,`status`);

--
-- Indexes for table `termly_summaries`
--
ALTER TABLE `termly_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_termsummary` (`tenant_id`,`student_id`,`term_id`,`session_id`),
  ADD KEY `fk_termsummary_student` (`student_id`),
  ADD KEY `fk_termsummary_classarm` (`class_arm_id`),
  ADD KEY `fk_termsummary_term` (`term_id`),
  ADD KEY `fk_termsummary_session` (`session_id`),
  ADD KEY `idx_termsummary_arm_term` (`tenant_id`,`class_arm_id`,`term_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_terms_session` (`session_id`),
  ADD KEY `idx_terms_tenant_session` (`tenant_id`,`session_id`),
  ADD KEY `idx_terms_tenant_current` (`tenant_id`,`is_current`);

--
-- Indexes for table `timetable_configs`
--
ALTER TABLE `timetable_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ttconfig` (`tenant_id`,`session_id`),
  ADD KEY `fk_ttconfig_session` (`session_id`);

--
-- Indexes for table `timetable_periods`
--
ALTER TABLE `timetable_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_timetable_arm` (`class_arm_id`),
  ADD KEY `fk_timetable_subject` (`subject_id`),
  ADD KEY `fk_timetable_teacher` (`teacher_id`),
  ADD KEY `fk_timetable_session` (`session_id`),
  ADD KEY `idx_timetable_arm_day` (`tenant_id`,`class_arm_id`,`day_of_week`),
  ADD KEY `idx_timetable_teacher_day` (`tenant_id`,`teacher_id`,`day_of_week`);

--
-- Indexes for table `transport_assignments`
--
ALTER TABLE `transport_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transport_assignments_tenant_id_student_id_unique` (`tenant_id`,`student_id`),
  ADD KEY `transport_assignments_tenant_id_route_id_index` (`tenant_id`,`route_id`);

--
-- Indexes for table `transport_buses`
--
ALTER TABLE `transport_buses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transport_buses_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `transport_routes`
--
ALTER TABLE `transport_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transport_routes_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_staff_id_unique` (`staff_id`),
  ADD UNIQUE KEY `users_student_id_unique` (`student_id`),
  ADD KEY `users_tenant_id_role_index` (`tenant_id`,`role`),
  ADD KEY `users_tenant_id_email_index` (`tenant_id`,`email`),
  ADD KEY `idx_users_tenant_employment_status` (`tenant_id`,`employment_status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `academic_tracks`
--
ALTER TABLE `academic_tracks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admission_documents`
--
ALTER TABLE `admission_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admission_portal_settings`
--
ALTER TABLE `admission_portal_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agent_messages`
--
ALTER TABLE `agent_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_message_reads`
--
ALTER TABLE `agent_message_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_payouts`
--
ALTER TABLE `agent_payouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_referrals`
--
ALTER TABLE `agent_referrals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_types`
--
ALTER TABLE `assessment_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=437;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cbt_exams`
--
ALTER TABLE `cbt_exams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cbt_questions`
--
ALTER TABLE `cbt_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cbt_student_sessions`
--
ALTER TABLE `cbt_student_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_arms`
--
ALTER TABLE `class_arms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `class_arm_subjects`
--
ALTER TABLE `class_arm_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `class_levels`
--
ALTER TABLE `class_levels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `class_level_subjects`
--
ALTER TABLE `class_level_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_categories`
--
ALTER TABLE `fee_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_installments`
--
ALTER TABLE `fee_installments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_payment_plans`
--
ALTER TABLE `fee_payment_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grading_systems`
--
ALTER TABLE `grading_systems`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `guardians`
--
ALTER TABLE `guardians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guardian_student`
--
ALTER TABLE `guardian_student`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_discount_templates`
--
ALTER TABLE `invoice_discount_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_generation_batches`
--
ALTER TABLE `invoice_generation_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_payment_plans`
--
ALTER TABLE `invoice_payment_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_plans`
--
ALTER TABLE `lesson_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_loans`
--
ALTER TABLE `library_loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_threads`
--
ALTER TABLE `message_threads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_thread_replies`
--
ALTER TABLE `message_thread_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification_queues`
--
ALTER TABLE `notification_queues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_triggers`
--
ALTER TABLE `notification_triggers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_trigger_logs`
--
ALTER TABLE `notification_trigger_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_payment_logs`
--
ALTER TABLE `online_payment_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parent_messages`
--
ALTER TABLE `parent_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_portal_accounts`
--
ALTER TABLE `parent_portal_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateway_configs`
--
ALTER TABLE `payment_gateway_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deduction_templates`
--
ALTER TABLE `payroll_deduction_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_items`
--
ALTER TABLE `payroll_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_role_templates`
--
ALTER TABLE `payroll_role_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_tax_bands`
--
ALTER TABLE `payroll_tax_bands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `platform_agents`
--
ALTER TABLE `platform_agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `platform_invoices`
--
ALTER TABLE `platform_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `platform_payments`
--
ALTER TABLE `platform_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `promotion_rules`
--
ALTER TABLE `promotion_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card_publications`
--
ALTER TABLE `report_card_publications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_threshold_configs`
--
ALTER TABLE `risk_threshold_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `school_bank_subaccounts`
--
ALTER TABLE `school_bank_subaccounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_expenses`
--
ALTER TABLE `school_expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_groups`
--
ALTER TABLE `school_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_group_members`
--
ALTER TABLE `school_group_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `score_imports`
--
ALTER TABLE `score_imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_definitions`
--
ALTER TABLE `skill_definitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_attendance_records`
--
ALTER TABLE `staff_attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_attendance_settings`
--
ALTER TABLE `staff_attendance_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_deductions`
--
ALTER TABLE `staff_deductions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_offline_clockins`
--
ALTER TABLE `staff_offline_clockins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_proxy_requests`
--
ALTER TABLE `staff_proxy_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_salary_settings`
--
ALTER TABLE `staff_salary_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_status_histories`
--
ALTER TABLE `staff_status_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_work_histories`
--
ALTER TABLE `staff_work_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_class_transfers`
--
ALTER TABLE `student_class_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_health_records`
--
ALTER TABLE `student_health_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_risk_flags`
--
ALTER TABLE `student_risk_flags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_skill_ratings`
--
ALTER TABLE `student_skill_ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_status_histories`
--
ALTER TABLE `student_status_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_subject_selections`
--
ALTER TABLE `student_subject_selections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_transfers`
--
ALTER TABLE `student_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `subject_frequencies`
--
ALTER TABLE `subject_frequencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tenant_subscriptions`
--
ALTER TABLE `tenant_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `termly_summaries`
--
ALTER TABLE `termly_summaries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `timetable_configs`
--
ALTER TABLE `timetable_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable_periods`
--
ALTER TABLE `timetable_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transport_assignments`
--
ALTER TABLE `transport_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transport_buses`
--
ALTER TABLE `transport_buses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transport_routes`
--
ALTER TABLE `transport_routes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD CONSTRAINT `fk_acadsessions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_payouts`
--
ALTER TABLE `agent_payouts`
  ADD CONSTRAINT `agent_payouts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `platform_agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_types`
--
ALTER TABLE `assessment_types`
  ADD CONSTRAINT `fk_asstypes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asstypes_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `fk_attendance_classarm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_markedby` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_auditlogs_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_auditlogs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cbt_exams`
--
ALTER TABLE `cbt_exams`
  ADD CONSTRAINT `fk_cbtexams_bank` FOREIGN KEY (`question_bank_id`) REFERENCES `cbt_question_banks` (`id`),
  ADD CONSTRAINT `fk_cbtexams_classarm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtexams_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtexams_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cbt_questions`
--
ALTER TABLE `cbt_questions`
  ADD CONSTRAINT `fk_cbtqs_bank` FOREIGN KEY (`question_bank_id`) REFERENCES `cbt_question_banks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtqs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  ADD CONSTRAINT `fk_cbtbanks_classlevel` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtbanks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtbanks_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cbt_student_sessions`
--
ALTER TABLE `cbt_student_sessions`
  ADD CONSTRAINT `fk_cbtsessions_exam` FOREIGN KEY (`cbt_exam_id`) REFERENCES `cbt_exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtsessions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbtsessions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_arms`
--
ALTER TABLE `class_arms`
  ADD CONSTRAINT `fk_classarms_classlevel` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_classarms_formtutor` FOREIGN KEY (`form_tutor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_classarms_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_arm_subjects`
--
ALTER TABLE `class_arm_subjects`
  ADD CONSTRAINT `fk_armsubjects_arm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_armsubjects_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_armsubjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_armsubjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_armsubjects_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_levels`
--
ALTER TABLE `class_levels`
  ADD CONSTRAINT `fk_classlevels_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD CONSTRAINT `fk_feecats_subaccount` FOREIGN KEY (`school_bank_subaccount_id`) REFERENCES `school_bank_subaccounts` (`id`),
  ADD CONSTRAINT `fk_feecats_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fk_feestructs_classlevel` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feestructs_feecat` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feestructs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feestructs_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_systems`
--
ALTER TABLE `grading_systems`
  ADD CONSTRAINT `fk_grading_classlevel` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grading_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `guardians`
--
ALTER TABLE `guardians`
  ADD CONSTRAINT `fk_guardians_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `guardian_student`
--
ALTER TABLE `guardian_student`
  ADD CONSTRAINT `fk_guardstudt_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guardstudt_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guardstudt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  ADD CONSTRAINT `fk_invdisc_approvedby` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invdisc_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invdisc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invitems_feecat` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`),
  ADD CONSTRAINT `fk_invitems_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitems_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `fk_notif_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notif_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `platform_payments`
--
ALTER TABLE `platform_payments`
  ADD CONSTRAINT `fk_ppay_sub` FOREIGN KEY (`subscription_id`) REFERENCES `tenant_subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ppay_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_rules`
--
ALTER TABLE `promotion_rules`
  ADD CONSTRAINT `fk_promrules_classlevel` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_promrules_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_bank_subaccounts`
--
ALTER TABLE `school_bank_subaccounts`
  ADD CONSTRAINT `fk_banksubaccts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `fk_scores_asstype` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_enteredby` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scores_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_definitions`
--
ALTER TABLE `skill_definitions`
  ADD CONSTRAINT `fk_skilldef_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_status_histories`
--
ALTER TABLE `staff_status_histories`
  ADD CONSTRAINT `fk_staffstatushist_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staffstatushist_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_staffstatushist_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_staffstatushist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `staff_work_histories`
--
ALTER TABLE `staff_work_histories`
  ADD CONSTRAINT `fk_staffworkhist_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staffworkhist_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_staffworkhist_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_staffworkhist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_classarm` FOREIGN KEY (`current_class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_students_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_class_transfers`
--
ALTER TABLE `student_class_transfers`
  ADD CONSTRAINT `fk_stuclasstrans_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stuclasstrans_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stuclasstrans_from_arm` FOREIGN KEY (`from_class_arm_id`) REFERENCES `class_arms` (`id`),
  ADD CONSTRAINT `fk_stuclasstrans_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stuclasstrans_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_stuclasstrans_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`),
  ADD CONSTRAINT `fk_stuclasstrans_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `fk_stuclasstrans_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stuclasstrans_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stuclasstrans_to_arm` FOREIGN KEY (`to_class_arm_id`) REFERENCES `class_arms` (`id`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `fk_enrollments_classarm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollments_ended_by` FOREIGN KEY (`ended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enrollments_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollments_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_skill_ratings`
--
ALTER TABLE `student_skill_ratings`
  ADD CONSTRAINT `fk_skillrating_ratedby` FOREIGN KEY (`rated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_skillrating_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skillrating_skilldef` FOREIGN KEY (`skill_definition_id`) REFERENCES `skill_definitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skillrating_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skillrating_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skillrating_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_status_histories`
--
ALTER TABLE `student_status_histories`
  ADD CONSTRAINT `fk_stustatushist_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stustatushist_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_stustatushist_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `fk_stustatushist_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_frequencies`
--
ALTER TABLE `subject_frequencies`
  ADD CONSTRAINT `fk_subfreq_classarm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subfreq_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subfreq_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subfreq_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tenant_subscriptions`
--
ALTER TABLE `tenant_subscriptions`
  ADD CONSTRAINT `fk_tsub_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tsub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `termly_summaries`
--
ALTER TABLE `termly_summaries`
  ADD CONSTRAINT `fk_termsummary_classarm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_termsummary_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_termsummary_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_termsummary_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_termsummary_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `fk_terms_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_terms_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_configs`
--
ALTER TABLE `timetable_configs`
  ADD CONSTRAINT `fk_ttconfig_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ttconfig_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_periods`
--
ALTER TABLE `timetable_periods`
  ADD CONSTRAINT `fk_timetable_arm` FOREIGN KEY (`class_arm_id`) REFERENCES `class_arms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_timetable_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_timetable_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_timetable_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_timetable_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
