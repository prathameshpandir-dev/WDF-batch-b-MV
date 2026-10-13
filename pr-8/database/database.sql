-- ========================================================
-- Practical 9: Secure User Registration Database Schema
-- Database Name: student_portal
-- Technology: MySQL (MariaDB)
-- Purpose: User registration with password hashing (bcrypt)
-- ========================================================

-- Step 1: Create Database
CREATE DATABASE IF NOT EXISTS student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

-- Step 2: Create users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mobile VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(100) NOT NULL,
    academic_year VARCHAR(30) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Insert Sample Verified Records (Hashed Passwords)
-- Note: All sample passwords below are hashed using PHP password_hash() for 'Password@123'
INSERT INTO users (full_name, email, mobile, password, course, academic_year, gender) VALUES
('Rahul Sharma', 'rahul.sharma@backbenchers.edu.in', '9876543210', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'Male'),
('Priya Patil', 'priya.patil@backbenchers.edu.in', '9811223344', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'Second Year (SE)', 'Female'),
('Amit Verma', 'amit.verma@backbenchers.edu.in', '9823456789', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'Male')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
