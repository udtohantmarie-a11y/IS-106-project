<?php
require_once '../config/db.php';
$user_id = $_SESSION['user_id'];
$job_id = (int)$_POST['job_id'];

$check = $conn->query("SELECT id FROM wishlist WHERE user_id = '$user_id' AND job_id = '$job_id'");

if ($check->num_rows > 0) {
    $conn->query("DELETE FROM wishlist WHERE user_id = '$user_id' AND job_id = '$job_id'");
    echo json_encode(['status' => 'removed']);
} else {
    $conn->query("INSERT INTO wishlist (user_id, job_id) VALUES ('$user_id', '$job_id')");
    echo json_encode(['status' => 'added']);
}