# Practical 8: MySQL Schema Design, ER Model & Database Connectivity

## 1. Overview
This directory contains the MySQL database design for the **StudentHub** portal as part of the Second-Year B.Tech IT **Web Development Frameworks** practical syllabus.

---

## 2. Entity-Relationship (ER) Model

```
+------------------+             +--------------------+             +------------------+
|     STUDENTS     | 1         N |   REGISTRATIONS    | N         1 |      EVENTS      |
+------------------+-------------+--------------------+-------------+------------------+
| PK id            |             | PK id              |             | PK id            |
|    name          |             | FK student_id      |             |    title         |
|    email (UNIQUE)|             | FK event_id        |             |    description   |
|    mobile        |             |    registered_at   |             |    event_date    |
|    course        |             +--------------------+             +------------------+
|    year          |
+------------------+
```

### Relationship Explanation:
* **One Student can register for Many Events** (1 : N).
* **One Event can have Many Students registered** (1 : N).
* Therefore, the relationship between `students` and `events` is **Many-to-Many (M : N)**.
* In relational database design, a Many-to-Many relationship is broken down into two One-to-Many relationships using a bridge/junction table called **`registrations`**.

---

## 3. Database Schema & Tables

### A. `students` Table
Stores student biodata and enrolled academic program.
* **`id`** (`INT`, `AUTO_INCREMENT`, `PRIMARY KEY`): Uniquely identifies each student.
* **`name`** (`VARCHAR(100)`): Full name of the student.
* **`email`** (`VARCHAR(100)`, `UNIQUE`): University or personal email (must be unique).
* **`mobile`** (`VARCHAR(15)`): 10-digit contact mobile number.
* **`course`** (`VARCHAR(100)`): Enrolled degree (e.g., B.Tech IT, B.Tech CSE).
* **`year`** (`VARCHAR(50)`): Current academic year (e.g., Second Year SE).

### B. `events` Table
Stores university seminars, hackathons, and cultural fests.
* **`id`** (`INT`, `AUTO_INCREMENT`, `PRIMARY KEY`): Uniquely identifies each event.
* **`title`** (`VARCHAR(150)`): Event title or workshop name.
* **`description`** (`TEXT`): Detailed briefing of the event.
* **`event_date`** (`DATE`): Scheduled date of the event.

### C. `registrations` Table (Junction Table)
Connects students with the events they have registered for.
* **`id`** (`INT`, `AUTO_INCREMENT`, `PRIMARY KEY`): Uniquely identifies each registration entry.
* **`student_id`** (`INT`, `FOREIGN KEY`): References `students(id)`.
* **`event_id`** (`INT`, `FOREIGN KEY`): References `events(id)`.
* **`registered_at`** (`DATETIME`): Automatic timestamp when the registration was created.

---

## 4. Key Concepts for Viva Explanation

1. **What is a Primary Key (PK)?**
   A column (or set of columns) that uniquely identifies each row in a table. It cannot contain `NULL` values. Here, `id` is the primary key for all three tables.

2. **What is a Foreign Key (FK)?**
   A field in one table that links to the primary key of another table. It enforces **Referential Integrity**. In `registrations`, `student_id` references `students.id` and `event_id` references `events.id`.

3. **What is `ON DELETE CASCADE`?**
   If a student or event record is deleted from the parent table, all corresponding registration entries in the `registrations` child table will automatically be removed to prevent orphaned records.

4. **Why use PDO over mysqli in PHP?**
   PDO (PHP Data Objects) is lightweight, supports prepared statements, provides exception-based error handling, and works consistently across multiple database systems (MySQL, PostgreSQL, SQLite).

---

## 5. How to Import in phpMyAdmin

1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open your browser and navigate to: `http://localhost/phpmyadmin/`
3. Click on **Import** in the top navigation bar.
4. Click **Choose File** and select `database/studenthub.sql` from your project folder.
5. Scroll to the bottom and click **Import** (or **Go**).
6. Verify that the `studenthub` database appears in the left sidebar with three tables: `students`, `events`, and `registrations`.
