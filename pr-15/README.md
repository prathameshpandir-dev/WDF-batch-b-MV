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
* **Practical 9:** Secure User Registration with MySQL Insert, Duplicate Checks, and Password Hashing (`process_registration.php`, `run_practical9_tests.php`).
* **Practical 10:** Secure Login/Logout with Sessions, Role-Based Access Control (RBAC), 15-Minute Timeout, and Remember-Me Persistent Token (`login.html`, `php/login_process.php`, `php/auth_check.php`, `dashboard.php`, `admin_dashboard.php`, `php/logout.php`, `run_practical10_tests.php`).

---

## 📁 Project Folder Structure

```text
StudentHub/
│
├── index.html                  # University Homepage & FAQ Knowledge Base (P6)
├── about.html                  # About Institution & Accreditation
├── registration.html           # Registration Form (P5 Client & P7/P9 Server)
├── login.html                  # Login Gateway with Role Selection & Remember-Me (P10)
├── login.php                   # Root Login Processor Wrapper (P10)
├── dashboard.php               # Protected Student Academic Dashboard (P10)
├── admin_dashboard.php         # Protected Admin Control Center (P10)
├── logout.php                  # Root Logout Wrapper (P10)
├── records.php                 # Admin Student Directory (Protected with auth_check.php)
│
├── php/
│   ├── login_process.php       # Practical 10: Authentication, password_verify(), & RBAC routing
│   ├── auth_check.php          # Practical 10: Session validation, 15m timeout, & RBAC enforcement
│   ├── logout.php              # Practical 10: Session destroy, cookie deletion, & token revocation
│   └── process_registration.php# Practical 9: User registration handler
│
├── config/
│   └── db.php                  # Central Database Connection (MySQLi & PDO to `studenthub`)
│
├── database/
│   └── studenthub.sql          # MySQL Schema with `students`, `events`, `registrations`, `remember_tokens`
│
├── run_practical9_tests.php    # Automated test suite for Practical 9 (15 test cases)
└── run_practical10_tests.php   # Automated test suite for Practical 10 (11 test cases)
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

### Practical 9 Demonstration:
1. **Frontend Registration:** `registration.html` (Form posting to `process_registration.php`).
2. **Server-Side Validation & Bcrypt Hashing:** `process_registration.php` (`password_hash()`, duplicate check).
3. **Automated Test Suite:** Run `php run_practical9_tests.php` in terminal (15/15 tests passing).

### Practical 10 Demonstration:
1. **Login Form:** `login.html` (Native POST submission to `php/login_process.php`, Role selector, Remember-Me checkbox).
2. **Authentication Processor:** `php/login_process.php` (`password_verify()`, `session_regenerate_id(true)`, role routing, token creation).
3. **Session & RBAC Guard:** `php/auth_check.php` (Session validation, 15-minute inactivity timeout, `check_auth('admin')`, cache-control headers).
4. **Student Dashboard:** `dashboard.php` (Dynamic session student details, timeout badge, sign out link).
5. **Admin Dashboard:** `admin_dashboard.php` (Admin role check, live database counters, manage records link).
6. **Logout Handler:** `php/logout.php` (Session destroy, cookie clearing, token revocation).
7. **Automated Test Suite:** Run `php run_practical10_tests.php` in terminal (11/11 tests passing).

---

## 🔒 Practical 10: Secure Login/Logout & RBAC Setup & Testing Guide

### 1. Database Accounts for Testing:
* **Student Account:**
  * **Email / Username:** `rahul.sharma@backbenchers.edu.in`
  * **Password:** `Password@123`
  * **Role:** `student` (Redirects to `dashboard.php`)
* **Administrator Account:**
  * **Email / Username:** `admin@backbenchers.edu.in`
  * **Password:** `Password@123`
  * **Role:** `admin` (Redirects to `admin_dashboard.php`)

### 2. Manual Testing Checklist (XAMPP in Browser):
1. **Valid Student Login:**
   * Open `http://localhost/Student_Portal_HTML_Pages/login.html`.
   * Enter `rahul.sharma@backbenchers.edu.in` and `Password@123`.
   * Click **Sign In & Unlock Portal**.
   * Success alert appears: redirects to `dashboard.php`.
2. **Valid Admin Login:**
   * Open `http://localhost/Student_Portal_HTML_Pages/login.html`.
   * Enter `admin@backbenchers.edu.in` and `Password@123`.
   * Click **Sign In & Unlock Portal**.
   * Success alert appears: redirects to `admin_dashboard.php`.
3. **Invalid Password:**
   * Enter valid email with wrong password `WrongPass123`.
   * Displays friendly generic error: *"Invalid email/username or password."*
4. **Empty Form Submission:**
   * Leave fields empty and submit.
   * Client & server prompt: *"Please enter your email/username and password."*
5. **Direct Protected Access without Login:**
   * Open `http://localhost/Student_Portal_HTML_Pages/dashboard.php` in an Incognito window.
   * Server redirects directly to `login.html?error=Authentication+required`.
6. **Role-Based Access Enforcement (Student -> Admin):**
   * Log in as student `rahul.sharma@backbenchers.edu.in`.
   * Try opening `http://localhost/Student_Portal_HTML_Pages/admin_dashboard.php` directly in browser.
   * `auth_check.php` intercepts the request: displays *"Access Denied"* alert and redirects student back to `dashboard.php`.
7. **Logout & Back-Button Protection:**
   * Click **Sign Out** button on dashboard.
   * Redirects to `login.html?msg=logged_out` with green logout confirmation badge.
   * Press browser **Back** button: server cache-control headers prevent cached view, and server redirects to login.
8. **Inactivity Timeout (15 Minutes):**
   * Leave dashboard inactive for 15 minutes.
   * On next request/click, session is destroyed and user is redirected to `login.html?msg=timeout`.
9. **Remember-Me Persistent Login:**
   * Log in with **Remember my login session** checked.
   * A 64-character SHA-256 token is saved in MySQL `remember_tokens` table and HttpOnly cookie `bbu_remember_token` is set.
   * If session expires or browser restarts, opening `dashboard.php` restores the session and rotates the token.
   * Logging out revokes and deletes the token from database and browser.

---

## 🎓 Practical 10 Viva Questions & Answers (2nd-Year B.Tech IT Level)

### Q1. What is the difference between Authentication and Authorization?
**Answer:**
* **Authentication:** The process of verifying *who* the user is (verifying email and password against stored bcrypt hash using `password_verify()`).
* **Authorization:** The process of verifying *what* the user is allowed to do based on their assigned role (e.g., student vs. admin access in `auth_check.php`).

### Q2. Why should we never store plain-text passwords in a database?
**Answer:** Plain-text passwords can be leaked if a database backup is misplaced or an SQL injection occurs. Storing irreversible cryptographic hashes generated with `password_hash($pass, PASSWORD_DEFAULT)` (bcrypt) ensures even if the database is compromised, passwords cannot be recovered directly.

### Q3. Why do we call `session_regenerate_id(true)` upon successful login?
**Answer:** To prevent **Session Fixation attacks**. Without regeneration, an attacker could plant a predetermined session ID in a user's browser, wait for them to authenticate, and then hijack their authenticated session. `session_regenerate_id(true)` generates a completely new session ID and deletes the old unauthenticated session file.

### Q4. How does PHP's `password_verify()` work with bcrypt hashes?
**Answer:** Bcrypt hashes generated by `password_hash()` embed the algorithm, cost factor, and a unique cryptographic salt into the hash string itself (e.g. `$2y$10$...`). When `password_verify($password, $hash)` is executed, PHP extracts the salt and cost from the stored hash, hashes the candidate password using those identical parameters, and compares the results in constant time to prevent timing attacks.

### Q5. Where is PHP session data stored compared to cookies?
**Answer:**
* **Sessions:** Stored on the **server** (in the server's temporary filesystem), making them secure from client-side tampering.
* **Cookies:** Stored on the **client's browser**. The browser only stores a small session identifier cookie (`PHPSESSID`) that points to the corresponding session file on the server.

### Q6. How is the 15-minute inactivity timeout implemented in PHP?
**Answer:** We save the current timestamp `$_SESSION['last_activity'] = time()` during each authenticated request. On subsequent requests, `auth_check.php` checks if `(time() - $_SESSION['last_activity']) > 900` (900 seconds = 15 minutes). If true, it wipes `$_SESSION`, destroys the session with `session_destroy()`, and redirects to `login.html?msg=timeout`. Otherwise, it updates `$_SESSION['last_activity'] = time()`.

### Q7. How does our project prevent access to protected pages when the user clicks the browser Back button after logout?
**Answer:** By sending HTTP cache-control headers on protected pages:
```php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
```
This forces the browser to request a fresh copy from the server every time the user navigates, where `auth_check.php` rejects unauthenticated requests.

### Q8. How does the Remember-Me feature work securely without storing passwords in cookies?
**Answer:**
1. A cryptographically random 32-byte token is generated using `random_bytes(32)` and converted to hex.
2. Only the SHA-256 hash of the token is stored in the database table `remember_tokens` with an expiration date.
3. The raw token is sent to the client browser in a secure, `HttpOnly`, `SameSite=Lax` cookie.
4. When restoring login, the token is hashed and verified against the database.
5. The token is **rotated** (new token generated, old token deleted) after each successful use to prevent replay attacks.
6. Upon logout, the token is revoked and deleted from the database.

### Q9. Why must role checks be enforced on the server rather than the client?
**Answer:** Client-side checks (such as hiding buttons or redirecting in JavaScript) can easily be disabled, inspected, or bypassed using browser developer tools or direct HTTP requests. Server-side checks in PHP (`check_auth('admin')`) run on the server and cannot be bypassed by the client.

### Q10. What is the difference between `session_unset()` and `session_destroy()` in PHP?
**Answer:**
* `$_SESSION = []` / `session_unset()`: Clears all session variables from memory.
* `session_destroy()`: Completely destroys the session file and data registered to the current session on the server disk. In a secure logout handler, both should be executed along with deleting the session cookie.

