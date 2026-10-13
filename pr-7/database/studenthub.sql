-- ========================================================
-- Practical 8 & Practical 9: MySQL Database Schema
-- Database Name: studenthub
-- College: The Backbenchers University (B.Tech IT - 2nd Year)
-- Tables: students (with password hashing & unique email), events, registrations
-- ========================================================

-- Step 1: Create Database
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- Step 2: Drop existing tables if re-importing (child table first)
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;

-- ========================================================
-- Table 1: students
-- Primary Key: id (Auto Increment)
-- Description: Stores enrolled students (Practical 8 & Practical 9)
-- ========================================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    password VARCHAR(255) NULL,
    course VARCHAR(100) NOT NULL,
    year VARCHAR(50) NOT NULL,
    gender VARCHAR(20) DEFAULT 'Unspecified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Table 2: events
-- Primary Key: id (Auto Increment)
-- Description: Stores campus events and workshops (Practical 8)
-- ========================================================
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Table 3: registrations
-- Primary Key: id (Auto Increment)
-- Foreign Keys:
--   - student_id references students(id)
--   - event_id references events(id)
-- Description: Junction table connecting students with events
-- ========================================================
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Sample Data
-- Note: Student passwords below are hashed with bcrypt for 'Password@123'
-- ========================================================

-- Insert Sample Students
INSERT INTO students (id, name, email, mobile, password, course, year, gender) VALUES
(1, 'Rahul Sharma', 'rahul.sharma@backbenchers.edu.in', '9876543210', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'Male'),
(2, 'Amit Verma', 'amit.verma@backbenchers.edu.in', '9823456789', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'Male'),
(3, 'Priya Patil', 'priya.patil@backbenchers.edu.in', '9811223344', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'Second Year (SE)', 'Female'),
(4, 'Sneha Deshmukh', 'sneha.d@backbenchers.edu.in', '9890123456', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech AI & Data Science', 'Third Year (TE)', 'Female'),
(5, 'Rohan Joshi', 'rohan.j@backbenchers.edu.in', '9765432109', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Mechanical Engineering', 'Final Year (BE)', 'Male'),
(6, 'Ananya Nair', 'ananya.n@backbenchers.edu.in', '9744556677', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'MCA', 'First Year (FE)', 'Female'),
(7, 'Tanmay Kulkarni', 'tanmay.k@backbenchers.edu.in', '9855667788', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Electronics & Telecom', 'Second Year (SE)', 'Male');

-- Insert Sample Events
INSERT INTO events (id, title, description, event_date) VALUES
(1, 'National Hackathon 2026', '36-hour inter-college coding marathon focused on AI and Full Stack Development.', '2026-10-24'),
(2, 'Web Development Symposium', 'Interactive technical seminar on modern PHP frameworks, REST APIs, and responsive design.', '2026-10-28'),
(3, 'AI & Robotics Expo', 'Annual exhibition displaying autonomous robotics projects, machine learning models, and IoT prototypes.', '2026-11-05'),
(4, 'Cyber Security & Ethical Hacking', 'Hands-on practical workshop covering network vulnerability assessment and secure software development.', '2026-11-12'),
(5, 'Annual Cultural Fest - Tarang', 'Flagship university cultural celebration showcasing music, performing arts, and student talent.', '2026-11-20');

-- Insert Sample Registrations (Event Registrations)
INSERT INTO registrations (id, student_id, event_id, registered_at) VALUES
(1, 1, 1, '2026-10-02 09:30:00'),
(2, 1, 2, '2026-10-02 10:15:00'),
(3, 2, 1, '2026-10-03 11:00:00'),
(4, 3, 2, '2026-10-03 14:20:00'),
(5, 4, 3, '2026-10-04 15:45:00'),
(6, 5, 5, '2026-10-05 16:10:00'),
(7, 6, 2, '2026-10-06 09:50:00'),
(8, 7, 4, '2026-10-06 13:00:00');
