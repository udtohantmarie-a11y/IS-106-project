<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

/**
 * Buburahin lang natin ang notifications mula sa 'notifications' table.
 * Hindi natin idadamay ang 'applications' dahil legal record iyon ng kumpanya.
 */
$query = "DELETE FROM notifications WHERE user_id = '$user_id'";

if ($conn->query($query)) {
    echo json_encode(['status' => 'success', 'message' => 'Activity history cleared']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear history']);
}
exit();