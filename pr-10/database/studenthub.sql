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
    role VARCHAR(20) NOT NULL DEFAULT 'student',
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
    event_date DATE NOT NULL,
    poster VARCHAR(255) NULL DEFAULT NULL
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
-- Table 4: remember_tokens (Practical 10: Secure Remember-Me)
-- Primary Key: id (Auto Increment)
-- Foreign Key: user_id references students(id)
-- Description: Stores SHA-256 hashed persistent login tokens
-- ========================================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_student FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Sample Data
-- Note: Student passwords below are hashed with bcrypt for 'Password@123'
-- ========================================================

-- Insert Sample Students (Students and Admin)
INSERT INTO students (id, name, email, mobile, password, course, year, role, gender) VALUES
(1, 'Rahul Sharma', 'rahul.sharma@backbenchers.edu.in', '9876543210', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'student', 'Male'),
(2, 'Amit Verma', 'amit.verma@backbenchers.edu.in', '9823456789', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'student', 'Male'),
(3, 'Priya Patil', 'priya.patil@backbenchers.edu.in', '9811223344', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'Second Year (SE)', 'student', 'Female'),
(4, 'Sneha Deshmukh', 'sneha.d@backbenchers.edu.in', '9890123456', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech AI & Data Science', 'Third Year (TE)', 'student', 'Female'),
(5, 'Rohan Joshi', 'rohan.j@backbenchers.edu.in', '9765432109', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Mechanical Engineering', 'Final Year (BE)', 'student', 'Male'),
(6, 'Ananya Nair', 'ananya.n@backbenchers.edu.in', '9744556677', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'MCA', 'First Year (FE)', 'student', 'Female'),
(7, 'Tanmay Kulkarni', 'tanmay.k@backbenchers.edu.in', '9855667788', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Electronics & Telecom', 'Second Year (SE)', 'student', 'Male'),
(8, 'System Administrator', 'admin@backbenchers.edu.in', '9999988888', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'Administration', 'Staff', 'admin', 'Other'),
(9, 'Kavita Rao', 'kavita.rao@backbenchers.edu.in', '9812345670', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Third Year (TE)', 'student', 'Female'),
(10, 'Aditya Joshi', 'aditya.j@backbenchers.edu.in', '9823456781', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'First Year (FE)', 'student', 'Male'),
(11, 'Pooja Hegde', 'pooja.h@backbenchers.edu.in', '9834567892', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech AI & Data Science', 'Second Year (SE)', 'student', 'Female'),
(12, 'Siddharth Mehra', 'siddharth.m@backbenchers.edu.in', '9845678903', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Final Year (BE)', 'student', 'Male'),
(13, 'Neha Kulkarni', 'neha.k@backbenchers.edu.in', '9856789014', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'MCA', 'Second Year (SE)', 'student', 'Female'),
(14, 'Vikramaditya Shinde', 'vikram.s@backbenchers.edu.in', '9867890125', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Mechanical Engineering', 'Third Year (TE)', 'student', 'Male'),
(15, 'Ritu Sengupta', 'ritu.s@backbenchers.edu.in', '9878901236', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Electronics & Telecom', 'Second Year (SE)', 'student', 'Female'),
(16, 'Manish Tiwari', 'manish.t@backbenchers.edu.in', '9889012347', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'Final Year (BE)', 'student', 'Male'),
(17, 'Divya Sundaram', 'divya.s@backbenchers.edu.in', '9890123458', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech AI & Data Science', 'First Year (FE)', 'student', 'Female'),
(18, 'Harsh Vardhan', 'harsh.v@backbenchers.edu.in', '9801234569', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Information Technology', 'Second Year (SE)', 'student', 'Male'),
(19, 'Anjali Desai', 'anjali.d@backbenchers.edu.in', '9811002233', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'B.Tech Computer Engineering', 'Third Year (TE)', 'student', 'Female'),
(20, 'Kunal Kapoor', 'kunal.k@backbenchers.edu.in', '9822113344', '$2y$10$A/Thw3POZ3V/iXRidpCTxegBTOzopyTg8zJkSgqsfyDrPx8sQfSFi', 'MCA', 'First Year (FE)', 'student', 'Male');

-- Insert Sample Events (Practical 8 & Practical 12 with Poster Paths)
INSERT INTO events (id, title, description, event_date, poster) VALUES
(1, 'National Hackathon 2026', '36-hour inter-college coding marathon focused on AI and Full Stack Development.', '2026-10-24', 'uploads/event_posters/poster_hackathon.png'),
(2, 'Web Development Symposium', 'Interactive technical seminar on modern PHP frameworks, REST APIs, and responsive design.', '2026-10-28', 'uploads/event_posters/poster_symposium.png'),
(3, 'AI & Robotics Expo', 'Annual exhibition displaying autonomous robotics projects, machine learning models, and IoT prototypes.', '2026-11-05', 'uploads/event_posters/poster_ai_expo.png'),
(4, 'Cyber Security & Ethical Hacking', 'Hands-on practical workshop covering network vulnerability assessment and secure software development.', '2026-11-12', 'uploads/event_posters/poster_cybersecurity.png'),
(5, 'Annual Cultural Fest - Tarang', 'Flagship university cultural celebration showcasing music, performing arts, and student talent.', '2026-11-20', 'uploads/event_posters/poster_tarang.png');

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
