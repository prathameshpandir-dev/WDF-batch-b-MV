<?php
// ========================================================
// Practical 11 & Practical 12 Database Seed & Migration Script
// Database Name: studenthub
// The Backbenchers University - B.Tech IT 2nd Year
// ========================================================

require_once dirname(__DIR__) . "/config/db.php";

echo "--- STEP 1: Inspect and update 'events' table schema ---\n";
$colRes = $conn->query("SHOW COLUMNS FROM events LIKE 'poster'");
if ($colRes && $colRes->num_rows === 0) {
    $alter = $conn->query("ALTER TABLE events ADD COLUMN poster VARCHAR(255) NULL DEFAULT NULL AFTER event_date");
    if ($alter) {
        echo "[SUCCESS] Added 'poster' column to 'events' table.\n";
    } else {
        echo "[ERROR] Failed to add 'poster' column: " . $conn->error . "\n";
    }
} else {
    echo "[INFO] 'poster' column already exists in 'events' table.\n";
}

echo "\n--- STEP 2: Create uploads/event_posters directory and protection ---\n";
$uploadDir = dirname(__DIR__) . "/uploads/event_posters";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "[SUCCESS] Created directory: " . $uploadDir . "\n";
} else {
    echo "[INFO] Upload directory already exists.\n";
}

// Create .htaccess inside uploads directory to disable script execution
$htaccessFile = dirname(__DIR__) . "/uploads/.htaccess";
$htaccessContent = <<<HTACCESS
# Block execution of any uploaded scripts in uploads directory
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|phar|cgi|pl|py|sh|asp|aspx|js)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
Options -ExecCGI -Indexes
php_flag engine off
HTACCESS;
file_put_contents($htaccessFile, $htaccessContent);

// Add index.html in uploads to prevent directory browsing
file_put_contents(dirname(__DIR__) . "/uploads/index.html", "<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h3>403 Forbidden - Direct directory browsing is disallowed.</h3></body></html>");

// Generate sample poster images for existing events if not already present
$defaultPosters = [
    1 => "poster_hackathon.png",
    2 => "poster_symposium.png",
    3 => "poster_ai_expo.png",
    4 => "poster_cybersecurity.png",
    5 => "poster_tarang.png"
];

// Create simple placeholder PNG images using GD if GD is available, or SVG fallback
foreach ($defaultPosters as $eventId => $posterName) {
    $posterPath = $uploadDir . "/" . $posterName;
    if (!file_exists($posterPath)) {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(600, 360);
            $colors = [
                1 => [15, 23, 42],
                2 => [22, 50, 79],
                3 => [30, 41, 59],
                4 => [67, 56, 202],
                5 => [124, 45, 18]
            ];
            $bg = $colors[$eventId] ?? [20, 30, 40];
            $bgColor = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
            $textColor = imagecolorallocate($img, 255, 255, 255);
            $subColor = imagecolorallocate($img, 245, 158, 11);
            imagefill($img, 0, 0, $bgColor);
            imagestring($img, 5, 40, 140, "Campus Event #" . $eventId, $subColor);
            imagestring($img, 4, 40, 180, "The Backbenchers University", $textColor);
            imagepng($img, $posterPath);
            imagedestroy($img);
        } else {
            // SVG fallback renamed as png
            file_put_contents($posterPath, '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360"><rect width="100%" height="100%" fill="#1e293b"/><text x="50%" y="50%" fill="#fbbf24" font-size="24" text-anchor="middle">Event Poster</text></svg>');
        }
        echo "[SUCCESS] Created sample poster: " . $posterName . "\n";
    }

    // Update existing event to reference this poster if currently NULL
    $stmt = $conn->prepare("UPDATE events SET poster = ? WHERE id = ? AND (poster IS NULL OR poster = '')");
    if ($stmt) {
        $relPath = "uploads/event_posters/" . $posterName;
        $stmt->bind_param("si", $relPath, $eventId);
        $stmt->execute();
        $stmt->close();
    }
}

echo "\n--- STEP 3: Seed sample students to reach at least 20 records ---\n";
$currentCountRes = $conn->query("SELECT COUNT(*) AS total FROM students");
$currentCount = $currentCountRes ? (int)$currentCountRes->fetch_assoc()["total"] : 0;
echo "[INFO] Current student records count: " . $currentCount . "\n";

$sampleStudents = [
    [
        'name' => 'Kavita Rao',
        'email' => 'kavita.rao@backbenchers.edu.in',
        'mobile' => '9812345670',
        'course' => 'B.Tech Information Technology',
        'year' => 'Third Year (TE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Aditya Joshi',
        'email' => 'aditya.j@backbenchers.edu.in',
        'mobile' => '9823456781',
        'course' => 'B.Tech Computer Engineering',
        'year' => 'First Year (FE)',
        'role' => 'student',
        'gender' => 'Male'
    ],
    [
        'name' => 'Pooja Hegde',
        'email' => 'pooja.h@backbenchers.edu.in',
        'mobile' => '9834567892',
        'course' => 'B.Tech AI & Data Science',
        'year' => 'Second Year (SE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Siddharth Mehra',
        'email' => 'siddharth.m@backbenchers.edu.in',
        'mobile' => '9845678903',
        'course' => 'B.Tech Information Technology',
        'year' => 'Final Year (BE)',
        'role' => 'student',
        'gender' => 'Male'
    ],
    [
        'name' => 'Neha Kulkarni',
        'email' => 'neha.k@backbenchers.edu.in',
        'mobile' => '9856789014',
        'course' => 'MCA',
        'year' => 'Second Year (SE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Vikramaditya Shinde',
        'email' => 'vikram.s@backbenchers.edu.in',
        'mobile' => '9867890125',
        'course' => 'B.Tech Mechanical Engineering',
        'year' => 'Third Year (TE)',
        'role' => 'student',
        'gender' => 'Male'
    ],
    [
        'name' => 'Ritu Sengupta',
        'email' => 'ritu.s@backbenchers.edu.in',
        'mobile' => '9878901236',
        'course' => 'B.Tech Electronics & Telecom',
        'year' => 'Second Year (SE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Manish Tiwari',
        'email' => 'manish.t@backbenchers.edu.in',
        'mobile' => '9889012347',
        'course' => 'B.Tech Computer Engineering',
        'year' => 'Final Year (BE)',
        'role' => 'student',
        'gender' => 'Male'
    ],
    [
        'name' => 'Divya Sundaram',
        'email' => 'divya.s@backbenchers.edu.in',
        'mobile' => '9890123458',
        'course' => 'B.Tech AI & Data Science',
        'year' => 'First Year (FE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Harsh Vardhan',
        'email' => 'harsh.v@backbenchers.edu.in',
        'mobile' => '9801234569',
        'course' => 'B.Tech Information Technology',
        'year' => 'Second Year (SE)',
        'role' => 'student',
        'gender' => 'Male'
    ],
    [
        'name' => 'Anjali Desai',
        'email' => 'anjali.d@backbenchers.edu.in',
        'mobile' => '9811002233',
        'course' => 'B.Tech Computer Engineering',
        'year' => 'Third Year (TE)',
        'role' => 'student',
        'gender' => 'Female'
    ],
    [
        'name' => 'Kunal Kapoor',
        'email' => 'kunal.k@backbenchers.edu.in',
        'mobile' => '9822113344',
        'course' => 'MCA',
        'year' => 'First Year (FE)',
        'role' => 'student',
        'gender' => 'Male'
    ]
];

$defaultPasswordHash = password_hash('Password@123', PASSWORD_DEFAULT);
$inserted = 0;

foreach ($sampleStudents as $s) {
    // Check if email already exists
    $chk = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $chk->bind_param("s", $s['email']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        $ins = $conn->prepare("INSERT INTO students (name, email, mobile, password, course, year, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("ssssssss", $s['name'], $s['email'], $s['mobile'], $defaultPasswordHash, $s['course'], $s['year'], $s['role'], $s['gender']);
        if ($ins->execute()) {
            $inserted++;
            echo " [ADDED] " . $s['name'] . " (" . $s['email'] . ")\n";
        }
        $ins->close();
    } else {
        $chk->close();
        echo " [SKIP] " . $s['email'] . " already exists.\n";
    }
}

$newCountRes = $conn->query("SELECT COUNT(*) AS total FROM students");
$newCount = $newCountRes ? (int)$newCountRes->fetch_assoc()["total"] : 0;
echo "\n[SUMMARY] Seeded $inserted new students. Total student records now: $newCount\n";
?>
