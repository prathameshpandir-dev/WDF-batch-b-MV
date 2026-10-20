<?php
// Practical 7: Display Stored Registration Records (Read data/registrations.json)

$dataFile = __DIR__ . "/data/registrations.json";
$registrations = [];
$errorMsg = "";

if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    if ($content !== false && trim($content) !== "") {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $registrations = $decoded;
        } else {
            $errorMsg = "Unable to parse JSON registration data.";
        }
    }
} else {
    $errorMsg = "No registrations data file found yet. Submit a registration to create it.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Registrations (Practical 7) - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container header-container">
            <div class="logo-area">
                <img src="images/logo.svg" alt="University Crest" class="university-logo">
                <div class="brand-text">
                    <h1>The Backbenchers University</h1>
                    <p>Student Portal &bull; Practical 7 File Storage</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="theme-toggle-btn" aria-label="Toggle theme">
                    <span>Dark Mode</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label">
                <span>Navigation Menu</span>
            </div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="login.html">Login</a></li>
                    <li><a href="registration.html">Register</a></li>
                    <li><a href="events.html">Events</a></li>
                    <li class="nav-dropdown">
                        <button class="nav-dropdown-btn">Student Portal ▾</button>
                        <ul class="nav-dropdown-menu">
                            <li><a href="academics.html">Academics &amp; Grades</a></li>
                            <li><a href="registrations.php" class="active">Student Registrations</a></li>
                            <li><a href="records.php">Student Records</a></li>
                        </ul>
                    </li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Practical 7 Header Card -->
        <div class="card" style="margin-bottom: var(--space-lg);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2>📋 Stored Student Registrations</h2>
                    <p style="color: var(--text-muted); font-size: var(--font-xs); margin: 4px 0 0 0;">
                        Records processed by PHP and stored in <code>data/registrations.json</code> (Practical 7)
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <span class="badge badge-info">Total Records: <?php echo count($registrations); ?></span>
                    <a href="registration.html" class="btn btn-primary btn-sm">+ New Registration</a>
                    <a href="records.php" class="btn btn-outline btn-sm">Student Records &raquo;</a>
                </div>
            </div>
        </div>

        <?php if (!empty($errorMsg) && empty($registrations)): ?>
            <div class="card" style="text-align: center; padding: var(--space-xl);">
                <p style="color: var(--text-muted);"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, "UTF-8"); ?></p>
                <a href="registration.html" class="btn btn-accent" style="margin-top: 10px;">Register a Student Now</a>
            </div>
        <?php else: ?>
            <!-- Responsive Table of Stored Registrations -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--bg-alt); text-align: left;">
                                <th style="padding: 12px;">#</th>
                                <th style="padding: 12px;">Full Name</th>
                                <th style="padding: 12px;">PRN</th>
                                <th style="padding: 12px;">Email</th>
                                <th style="padding: 12px;">Mobile</th>
                                <th style="padding: 12px;">Course</th>
                                <th style="padding: 12px;">Academic Year</th>
                                <th style="padding: 12px;">Gender</th>
                                <th style="padding: 12px;">Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $student): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;"><b><?php echo htmlspecialchars($student["id"] ?? "-", ENT_QUOTES, "UTF-8"); ?></b></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student["fullname"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                    <td style="padding: 12px;"><code><?php echo htmlspecialchars($student["prn"] ?? "-", ENT_QUOTES, "UTF-8"); ?></code></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student["email"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student["phone"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student["course"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student["year"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                    <td style="padding: 12px;"><span class="badge badge-info"><?php echo htmlspecialchars($student["gender"] ?? "-", ENT_QUOTES, "UTF-8"); ?></span></td>
                                    <td style="padding: 12px; font-size: var(--font-xs); color: var(--text-muted);"><?php echo htmlspecialchars($student["registered_at"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container" style="text-align: center; font-size: var(--font-xs); color: var(--text-muted); padding: var(--space-md) 0;">
            <p>&copy; 2026 The Backbenchers University &bull; Practical 7: PHP Form Processing &amp; File Storage</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
