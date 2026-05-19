<?php
/**
 * api_mark_read.php
 * Ginagamit ito para i-update ang status ng notifications via AJAX.
 * Sinusuportahan nito ang individual mark as read o bulk mark as read.
 */

require_once '../config/db.php';

// Siguraduhin na may active session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// I-set ang response header sa JSON para mabasa nang tama ng JS fetch
header('Content-Type: application/json');

// I-verify kung ang user ay logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unauthorized access. Please login.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

/**
 * SQL LOGIC:
 * 1. Kung may 'id' na pinasa sa URL (GET), i-uupdate lang ang specific notification na iyon.
 *    Ito ang ginagamit para sa "-1" badge count logic sa navbar.
 * 2. Kung walang 'id', i-uupdate lahat ng unread (is_read = 0) ng user.
 */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $notif_id = (int)$_GET['id'];
    // Sinisiguro ng 'user_id' check na hindi mababago ang notif ng ibang user
    $query = "UPDATE notifications SET is_read = 1 WHERE id = '$notif_id' AND user_id = '$user_id'";
} else {
    // Bulk update para sa lahat ng unread notifications ng user
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0";
}

if ($conn->query($query)) {
    echo json_encode([
        'status' => 'success',
        'message' => isset($notif_id) ? 'Individual notification marked as read.' : 'All notifications marked as read.',
        'affected_rows' => $conn->affected_rows
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database update failed: ' . $conn->error
    ]);
}

// Tapusin ang script execution
exit();