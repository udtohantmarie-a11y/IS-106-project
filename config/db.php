<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'job_board_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

/**
 * AUTO-BAN LOGIC
 * Iba-ban ang mga students na 1 week na ang account pero hindi pa verified.
 * Tumatakbo ito tuwing may naglo-load na page na gumagamit ng db.php.
 */
$conn->query("
    UPDATE users u
    JOIN students s ON u.id = s.user_id
    SET u.is_banned = 1, 
        u.ban_reason = 'Failure to verify identity within 7 days of registration.'
    WHERE s.is_verified = 0 
    AND u.role = 'student'
    AND u.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND u.is_banned = 0
");

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper: require login
 * Ngayon ay may kasama na itong check para sa BANNED users.
 */
function requireLogin($role = null) {
    global $conn;

    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }

    $user_id = $_SESSION['user_id'];
    
    // Check if the user is banned
    $check_ban = $conn->query("SELECT is_banned FROM users WHERE id = '$user_id'")->fetch_assoc();
    
    if ($check_ban && $check_ban['is_banned'] == 1) {
        // Huwag i-redirect ang admin kahit banned (optional) o i-logout agad
        if ($_SESSION['role'] !== 'admin') {
            redirect('../auth/banned.php');
        }
    }

    if ($role && $_SESSION['role'] !== $role) {
        // Redirect to their respective dashboards instead of login page if role mismatch
        if ($_SESSION['role'] === 'admin') redirect('../admin/dashboard.php');
        if ($_SESSION['role'] === 'company') redirect('../company/dashboard.php');
        if ($_SESSION['role'] === 'student') redirect('../student/dashboard.php');
        
        redirect('../auth/login.php');
    }
}

// Helper: sanitize input
function clean($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

/**
 * Helper: check if student is verified
 * Gamitin ito para harangan ang ilang features (e.g. Apply Job) hangga't hindi verified.
 */
function isVerified($student_id) {
    global $conn;
    $res = $conn->query("SELECT is_verified FROM students WHERE id = '$student_id'")->fetch_assoc();
    return ($res && $res['is_verified'] == 1);
}
?>