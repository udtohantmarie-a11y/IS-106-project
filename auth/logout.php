<?php
require_once '../config/db.php';

// Simulan ang session kung hindi pa nagsisimula
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. I-unsets ang lahat ng session variables
$_SESSION = array();

// 2. Kung gustong burahin pati ang session cookie, gawin ito:
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Tuluyan nang sirain ang session
session_destroy();

// 4. I-redirect pabalik sa Home Page (index.php)
// Dahil ang logout.php ay nasa loob ng /auth/ folder, gagamit tayo ng ../ para lumabas
header("Location: ../index.php");
exit();
?>