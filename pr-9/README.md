# StudentHub - College Web Development Practicals (B.Tech IT - 2nd Year)

StudentHub is a comprehensive college web development project developed by a Second-Year B.Tech Information Technology student at The Backbenchers University. The project demonstrates progressive mastery of frontend design, client-side scripting, asynchronous APIs, server-side PHP processing, and relational database management.

---

## 📚 Syllabus Practicals Index

* **Practical 1 & 2:** HTML5 Semantic Structure, Layout & Pure CSS Responsive Grid System (`index.html`, `style.css`).
* **Practical 3 & 4:** JavaScript DOM Interactivity, Modal Dialogs & Collapsible FAQ Accordion (`script.js`, `style.css`).
* **Practical 5:** Student Registration Form with Client-Side Regex Validation & Password Strength Meter (`registration.html`, `script.js`).
* **Practical 6:** Asynchronous Data Rendering using Fetch API with Live Search, Category Filtering, Multi-Field Sorting & Pagination (`events.html`, `academics.html`, `events.json`, `students.json`, `faqs.json`).
* **Practical 7:** PHP Form Processing with Server-Side Validation and JSON File Storage (`process_registration.php`, `data/registrations.json`, `registrations.php`).
* **Practical 8:** MySQL Relational Schema Design, ER Model, PHP PDO Database Connectivity & Prepared Statements (`database/studenthub.sql`, `config/db.php`, `database_test.php`, `database/README.md`).

---

## 📁 Project Folder Structure

```text
StudentHub/
│
├── index.html                  # University Homepage & FAQ Knowledge Base (P6)
├── about.html                  # About Institution & Accreditation
├── registration.html           # Registration Form (P5 Client & P7 Server)
├── login.html                  # Student & Faculty Portal Authentication
├── events.html                 # Campus Events with Search, Filter & Pagination (P6)
├── academics.html              # Academic Performance & Student Rank Directory (P6)
├── contact.html                # Campus Helpdesk & Inquiry Form
├── feedback.html               # Course & Faculty Evaluation Form
├── dashboard.html              # Student Academic Dashboard
│
├── style.css                   # Responsive CSS3 Stylesheet & Theme Variables
├── script.js                   # Client-side JavaScript (DOM, Validation, Fetch API)
│
├── events.json                 # Campus events dataset (18 records) (P6)
├── students.json               # Student academic ranks dataset (16 records) (P6)
├── faqs.json                   # FAQ knowledge base dataset (16 records) (P6)
│
├── process_registration.php    # Practical 7: PHP Form Handler & Server Validation
├── registrations.php           # Practical 7: View Stored JSON Registrations
├── database_test.php           # Practical 8: MySQL Connection Test & Prepared Statement Demo
│
├── config/
│   └── db.php                  # Practical 8: PHP PDO Database Connection
│
├── data/
│   └── registrations.json      # Practical 7: Saved Registration Records (JSON File)
│
├── database/
│   ├── studenthub.sql          # Practical 8: MySQL Database Schema & Seed Data
│   └── README.md               # Practical 8: ER Model Documentation & Viva Notes
│
└── README.md                   # Complete Project Documentation & Viva Guide
```

---

## ⚙️ Practical 7 & 8 Setup Guide (XAMPP on Windows)

### 1. Requirements
* **XAMPP Control Panel** (Apache & MySQL modules).
* Web browser (Chrome, Edge, or Firefox).

### 2. Project Directory Placement
Place or copy this project folder into the `htdocs` directory of your XAMPP installation:
```text
C:\xampp\htdocs\Student_Portal_HTML_Pages\
```
*(Or rename folder to `StudentHub` inside `C:\xampp\htdocs\`)*

### 3. Start Apache & MySQL
1. Launch **XAMPP Control Panel**.
2. Click **Start** next to **Apache** (Status becomes green, Port 80, 443).
3. Click **Start** next to **MySQL** (Status becomes green, Port 3306).

### 4. Import the Database in phpMyAdmin
1. Open your web browser and visit:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab at the top.
3. Click **Choose File** (Browse) and select:
   ```text
   database/studenthub.sql
   ```
4. Click **Import** (or **Go**) at the bottom of the page.
5. You should see a green success banner: *"Import has been successfully finished"*.
6. Notice the new database named **`studenthub`** containing three tables:
   * `students` (7 sample records)
   * `events` (5 sample records)
   * `registrations` (8 sample records)

---

## 🧪 How to Test Practical 7 (PHP Form Processing & JSON File Storage)

### URL to Open:
```text
http://localhost/Student_Portal_HTML_Pages/registration.html
```

### Test Cases:
1. **Empty Form Test (Server Validation):**
   * Leave fields blank and click **"Register Account »"**.
   * The form submits directly via POST to `process_registration.php`. PHP executes server-side validation and displays a clean error card listing all missing required fields.
2. **Invalid Email & Mobile Test:**
   * Enter an invalid email (e.g., `rahul@xyz`) and an invalid 5-digit mobile number.
   * Click **"Register Account »"**.
   * PHP catches both errors using `filter_var()` and regular expressions, displaying user-friendly server error messages.
3. **Valid Registration Test:**
   * Fill valid information (Name: `Pooja Joshi`, Email: `pooja.j@backbenchers.edu.in`, Mobile: `9898989898`, PRN: `SYIT108`, Course: `B.Tech Information Technology`, Year: `Second Year (SE)`, Gender: `Female`, Password: `Password@123`).
   * Click **"Register Account »"**.
   * The submission succeeds and displays the confirmation card with record summary.
4. **Verify JSON Storage:**
   * Open `http://localhost/Student_Portal_HTML_Pages/registrations.php` or check the file `data/registrations.json`.
   * Notice the newly registered student appended to the list with timestamp.
5. **Duplicate Prevention:**
   * Try submitting the exact same PRN or email again.
   * PHP detects the duplicate entry and displays a duplicate warning message.

---

## 💾 How to Test Practical 8 (MySQL Database Connectivity & Prepared Statements)

### URL to Open:
```text
http://localhost/Student_Portal_HTML_Pages/database_test.php
```

### What to Verify:
1. **Connection Status Banner:**
   * Displays green banner: *"Database connected successfully! Successfully established PDO connection to MySQL server at localhost with database studenthub."*
2. **Student Records Table:**
   * Displays students fetched from the MySQL `students` table via PDO `SELECT` query.
3. **Prepared Statement Insert Demo:**
   * Fill out the form *"Insert Student (Prepared Statement Demo)"* with a new student name, email, mobile, course, and year.
   * Click **"Execute Prepared Statement »"**.
   * The record is inserted via `$pdo->prepare()` and `$statement->execute()`.
   * A green success alert appears and the student table instantly updates.
4. **Relational Join Demonstration:**
   * Bottom table demonstrates a SQL `JOIN` across `students`, `registrations`, and `events`, showing which student registered for which event using Foreign Keys.

---

## 🎓 Viva Questions & Answers (2nd-Year B.Tech IT Level)

### Q1. What is the difference between client-side and server-side validation?
**Answer:** Client-side validation (Practical 5) runs in the user's browser using JavaScript before sending data, providing instant feedback. Server-side validation (Practical 7) runs on the server in PHP after the form is submitted. Server-side validation is mandatory for security because client-side JavaScript can be disabled or bypassed by an attacker.

### Q2. How does PHP read and write JSON data in Practical 7?
**Answer:** 
* To read: `file_get_contents('data/registrations.json')` reads the file as a string, and `json_decode($json, true)` converts it into an associative PHP array.
* To write: `json_encode($array, JSON_PRETTY_PRINT)` converts the PHP array back into formatted JSON string, and `file_put_contents('data/registrations.json', $json, LOCK_EX)` saves it safely to disk.

### Q3. Why do we sanitize user input before storing it?
**Answer:** Sanitization cleanses dangerous characters from inputs to prevent Cross-Site Scripting (XSS) and injection attacks. In PHP, we use `htmlspecialchars($input, ENT_QUOTES, 'UTF-8')` to convert HTML special characters into safe HTML entities, and `filter_var($email, FILTER_SANITIZE_EMAIL)` to sanitize emails.

### Q4. What is PDO in PHP and why is it preferred over mysqli?
**Answer:** PDO stands for **PHP Data Objects**. It provides a lightweight, unified interface for accessing relational databases. PDO supports prepared statements, named parameters, and exception handling (`PDOException`). It can connect to 12 different database engines (MySQL, PostgreSQL, SQLite, etc.), whereas `mysqli` only connects to MySQL.

### Q5. What is a Prepared Statement and why is it used?
**Answer:** A prepared statement is a database feature where the SQL query template is compiled first with placeholders (e.g., `:name`, `:email`), and user values are bound and executed separately. This completely prevents **SQL Injection** because user inputs are treated strictly as data parameters, never as executable SQL code.

### Q6. What is a Primary Key and a Foreign Key?
**Answer:**
* **Primary Key (PK):** A column that uniquely identifies every record in a table and cannot be NULL (e.g., `students.id`).
* **Foreign Key (FK):** A column in a child table that references the Primary Key of a parent table (e.g., `registrations.student_id` referencing `students.id`), enforcing referential integrity.

### Q7. Explain the relationship between Students and Events in Practical 8.
**Answer:** The relationship is **Many-to-Many (M : N)** because one student can register for multiple events, and one event can have multiple students. In relational database design, this is implemented using a junction/bridge table called **`registrations`**, which contains foreign keys to both `students` and `events`.

### Q8. What does `ON DELETE CASCADE` do in MySQL?
**Answer:** It ensures that when a record in a parent table is deleted (e.g., a student is removed), all related child records in `registrations` are automatically deleted by the database, preventing broken or orphaned foreign key records.

### Q9. What HTTP methods are commonly used for forms and why did we use POST?
**Answer:** GET and POST. We use **POST** because registration forms contain sensitive information (like passwords) and large amounts of data. Unlike GET, POST does not expose form inputs in the browser URL address bar and does not have URL length limitations.

### Q10. What is `novalidate` in the HTML form tag?
**Answer:** The `novalidate` attribute tells the browser not to run default native HTML5 validation popups, allowing our custom JavaScript validation (Practical 5) and server-side PHP validation (Practical 7) to control error handling.

---

## 🎯 Which Files to Open to Demonstrate Each Practical

### Practical 7 Demonstration:
1. **Frontend Form:** `registration.html` (Form with `action="process_registration.php"` and `method="POST"`).
2. **PHP Processing Script:** `process_registration.php` (Input sanitization, regex & email validation, duplicate checks, `json_decode` & `json_encode`).
3. **Storage File:** `data/registrations.json` (Formatted JSON array of registered student records).
4. **Display Page:** `registrations.php` (HTML table rendering stored JSON registrations).

### Practical 8 Demonstration:
1. **SQL Schema & Seed File:** `database/studenthub.sql` (`CREATE TABLE` with PK, FK, and sample INSERT queries).
2. **ER Model Documentation:** `database/README.md` (Ascii ER diagram, table columns, PK/FK explanations).
3. **Database Configuration:** `config/db.php` (PDO connection with try-catch block).
4. **Database Test & Demo Page:** `database_test.php` (Connection test badge, SELECT table, Prepared Statement INSERT demo, and Relational JOIN query).
