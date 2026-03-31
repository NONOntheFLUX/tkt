-- ============================================================
-- Simple CRM for Small Businesses - Database Schema + Seed Data
-- Target: MySQL 5.7+ / MariaDB 10+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50)  NOT NULL,
    `last_name`  VARCHAR(50)  NOT NULL,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('visitor','user','sales_rep','sales_manager','admin') NOT NULL DEFAULT 'user',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CATEGORIES (reference / tags / industries)
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `type`       VARCHAR(50)  NOT NULL DEFAULT 'industry',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. COMPANIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `companies` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(150) NOT NULL,
    `industry`   VARCHAR(100) DEFAULT NULL,
    `website`    VARCHAR(255) DEFAULT NULL,
    `address`    TEXT         DEFAULT NULL,
    `created_by` INT          DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. CONTACTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT          DEFAULT NULL,
    `first_name` VARCHAR(50)  NOT NULL,
    `last_name`  VARCHAR(50)  NOT NULL,
    `email`      VARCHAR(100) DEFAULT NULL,
    `phone`      VARCHAR(30)  DEFAULT NULL,
    `created_by` INT          DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. LEADS
-- ============================================================
CREATE TABLE IF NOT EXISTS `leads` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(200) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `status`      ENUM('new','contacted','qualified','won','lost') NOT NULL DEFAULT 'new',
    `company_id`  INT          DEFAULT NULL,
    `contact_id`  INT          DEFAULT NULL,
    `assigned_to` INT          DEFAULT NULL,
    `category_id` INT          DEFAULT NULL,
    `created_by`  INT          DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`contact_id`)  REFERENCES `contacts`(`id`)   ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)      ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. DEALS
-- ============================================================
CREATE TABLE IF NOT EXISTS `deals` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id`    INT            DEFAULT NULL,
    `title`      VARCHAR(200)   NOT NULL DEFAULT '',
    `amount`     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    `stage`      ENUM('prospecting','proposal','negotiation','won','lost') NOT NULL DEFAULT 'prospecting',
    `created_by` INT            DEFAULT NULL,
    `created_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`lead_id`)    REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ACTIVITIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `activities` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id`     INT          DEFAULT NULL,
    `type`        ENUM('call','email','meeting','task','other') NOT NULL DEFAULT 'task',
    `description` TEXT         DEFAULT NULL,
    `due_date`    DATE         DEFAULT NULL,
    `status`      ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_by`  INT          DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lead_id`)    REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. NOTES (polymorphic: object_type + object_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notes` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `object_type` VARCHAR(50) NOT NULL,
    `object_id`   INT         NOT NULL,
    `content`     TEXT        NOT NULL,
    `created_by`  INT         DEFAULT NULL,
    `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_notes_object` (`object_type`, `object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT         NOT NULL,
    `message`    TEXT        NOT NULL,
    `link`       VARCHAR(255) DEFAULT NULL,
    `is_read`    TINYINT(1)  NOT NULL DEFAULT 0,
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. STATUS HISTORY LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `status_history` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `object_type` VARCHAR(50)  NOT NULL,
    `object_id`   INT          NOT NULL,
    `old_status`  VARCHAR(50)  DEFAULT NULL,
    `new_status`  VARCHAR(50)  NOT NULL,
    `changed_by`  INT          DEFAULT NULL,
    `changed_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_history_object` (`object_type`, `object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Demo users (passwords hashed with password_hash)
-- admin@example.com / Admin123!
-- rep@example.com   / Rep123!
-- user@example.com  / User123!
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `is_active`) VALUES
('Admin',  'User',    'admin@example.com', '$2y$10$YF1Yz3GFqQhOQkOeeL0pXu6fYHnLEEzRiXb6KJm9dGhS0F3mZqC2a', 'admin',         1),
('Sales',  'Rep',     'rep@example.com',   '$2y$10$BkRJ5Y5Gx3xJHCqFJkTrUeM1d3L0GZwZz3KJm9dGhS0F3mZqC2b', 'sales_rep',     1),
('John',   'Doe',     'user@example.com',  '$2y$10$CkRJ5Y5Gx3xJHCqFJkTrUeN2e4M1H0AwA4LKn0eHiT1G4nAqD3c', 'user',          1),
('Sara',   'Manager', 'manager@example.com','$2y$10$DkRJ5Y5Gx3xJHCqFJkTrUeO3f5N2I1BxB5MLo1fIjU2H5oBrE4d', 'sales_manager', 1);

-- Categories / Industries
INSERT INTO `categories` (`name`, `type`) VALUES
('Technology',    'industry'),
('Healthcare',    'industry'),
('Finance',       'industry'),
('Education',     'industry'),
('Manufacturing', 'industry'),
('Retail',        'industry'),
('Hot Lead',      'tag'),
('Referral',      'tag'),
('Cold Call',      'tag');

-- Companies
INSERT INTO `companies` (`name`, `industry`, `website`, `address`, `created_by`) VALUES
('Acme Corp',       'Technology',    'https://acme.example.com',    '123 Tech St, San Francisco, CA',  1),
('HealthFirst Inc', 'Healthcare',    'https://healthfirst.example.com', '456 Med Ave, Boston, MA',     1),
('FinanceHub LLC',  'Finance',       'https://financehub.example.com',  '789 Wall St, New York, NY',   2),
('EduLearn Co',     'Education',     'https://edulearn.example.com',    '321 Campus Dr, Austin, TX',   3);

-- Contacts
INSERT INTO `contacts` (`company_id`, `first_name`, `last_name`, `email`, `phone`, `created_by`) VALUES
(1, 'Alice',   'Johnson',  'alice@acme.example.com',        '555-0101', 1),
(1, 'Bob',     'Smith',    'bob@acme.example.com',          '555-0102', 2),
(2, 'Carol',   'Williams', 'carol@healthfirst.example.com', '555-0201', 1),
(3, 'Dave',    'Brown',    'dave@financehub.example.com',   '555-0301', 2),
(4, 'Eve',     'Davis',    'eve@edulearn.example.com',      '555-0401', 3);

-- Leads
INSERT INTO `leads` (`title`, `description`, `status`, `company_id`, `contact_id`, `assigned_to`, `category_id`, `created_by`) VALUES
('Acme Corp - Cloud Migration',     'Interested in moving to cloud infrastructure.',   'new',        1, 1, 2, 1, 1),
('HealthFirst EMR Integration',     'Looking for EMR integration solutions.',           'contacted',  2, 3, 2, 2, 1),
('FinanceHub Trading Platform',     'Need custom trading platform development.',        'qualified',  3, 4, 2, 3, 2),
('EduLearn LMS Setup',              'Setting up a new Learning Management System.',     'new',        4, 5, NULL, 4, 3),
('Acme Corp - Security Audit',      'Annual security audit and compliance review.',     'contacted',  1, 2, 2, 7, 1);

-- Deals
INSERT INTO `deals` (`lead_id`, `title`, `amount`, `stage`, `created_by`) VALUES
(1, 'Cloud Migration Phase 1',  50000.00, 'prospecting', 2),
(2, 'EMR Integration Package',  75000.00, 'proposal',    2),
(3, 'Trading Platform MVP',    120000.00, 'negotiation', 2);

-- Activities
INSERT INTO `activities` (`lead_id`, `type`, `description`, `due_date`, `status`, `created_by`) VALUES
(1, 'call',    'Initial discovery call with Acme Corp CTO.',           '2025-02-01', 'completed', 2),
(1, 'email',   'Send cloud migration proposal.',                       '2025-02-05', 'pending',   2),
(2, 'meeting', 'On-site demo of EMR integration.',                     '2025-02-10', 'pending',   2),
(3, 'task',    'Prepare trading platform requirements document.',       '2025-02-03', 'completed', 2),
(4, 'call',    'Intro call with EduLearn team.',                        '2025-02-15', 'pending',   3);

-- Notes
INSERT INTO `notes` (`object_type`, `object_id`, `content`, `created_by`) VALUES
('lead', 1, 'Client is very interested but budget needs approval from board.', 2),
('lead', 2, 'They already use a legacy EMR system; migration will be key concern.', 2),
('lead', 3, 'High-value opportunity. Need to move fast before competitor bids.', 2),
('lead', 4, 'Small team, looking for affordable LMS options.', 3);

-- Notifications
INSERT INTO `notifications` (`user_id`, `message`, `link`, `is_read`) VALUES
(2, 'You have been assigned to lead: Acme Corp - Cloud Migration.',        'lead_detail.php?id=1', 0),
(2, 'You have been assigned to lead: HealthFirst EMR Integration.',        'lead_detail.php?id=2', 0),
(3, 'Your lead "EduLearn LMS Setup" has a new note.',                      'lead_detail.php?id=4', 0);

-- Status history
INSERT INTO `status_history` (`object_type`, `object_id`, `old_status`, `new_status`, `changed_by`) VALUES
('lead', 2, 'new',       'contacted', 2),
('lead', 3, 'new',       'contacted', 2),
('lead', 3, 'contacted', 'qualified', 2),
('lead', 5, 'new',       'contacted', 1),
('deal', 2, 'prospecting', 'proposal',    2),
('deal', 3, 'prospecting', 'proposal',    2),
('deal', 3, 'proposal',    'negotiation', 2);

COMMIT;
