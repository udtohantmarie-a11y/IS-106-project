<?php
require_once '../config/db.php';

// Siguraduhin na naka-login ang user bago i-check ang ban status
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Kunin ang account details at ban info
$query = "SELECT u.is_banned, u.ban_reason, s.full_name, s.resume_path 
          FROM users u 
          LEFT JOIN students s ON u.id = s.user_id 
          WHERE u.id = '$user_id'";
$result = $conn->query($query);
$user = $result->fetch_assoc();

// Kung hindi naman pala banned, ibalik sa dashboard
if ($user['is_banned'] == 0) {
    if ($_SESSION['role'] === 'student') redirect('../student/dashboard.php');
    if ($_SESSION['role'] === 'company') redirect('../company/dashboard.php');
    if ($_SESSION['role'] === 'admin') redirect('../admin/dashboard.php');
}

$error = "";
$success = "";

// Logic para sa ID Re-upload / Appeal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['id_card'])) {
    $target_dir = "../uploads/ids/";
    
    // Siguraduhin na exist ang directory
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file = $_FILES['id_card'];
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($file_ext, $allowed)) {
        $new_filename = "RECOVERY_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            // I-update ang resume_path sa students table para makita ng Admin sa ID Validation
            $conn->query("UPDATE students SET resume_path = '$new_filename' WHERE user_id = '$user_id'");
            
            // I-update ang ban reason para malaman ni Admin na for review ito
            $conn->query("UPDATE users SET ban_reason = 'Review Request Submitted: ID uploaded for verification.' WHERE id = '$user_id'");
            
            $success = "Your verification document has been submitted! Please wait for the administrator to review your account.";
        } else {
            $error = "There was an error uploading your file.";
        }
    } else {
        $error = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Restricted | JobBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
        .card { border-radius: 24px; }
        .ban-icon { width: 80px; height: 80px; background: rgba(220, 53, 69, 0.1); color: #dc3545; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 p-3">

    <div class="card shadow-lg border-0 p-4 p-md-5 text-center" style="max-width: 550px;">
        <div class="ban-icon mb-4">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        
        <h2 class="fw-800 text-dark mb-2">Account Restricted</h2>
        <p class="text-muted mb-4">
            Hello, <strong><?= htmlspecialchars($user['full_name'] ?? 'User') ?></strong>. 
            Access to your account has been temporarily restricted by the system or administrator.
        </p>

        <div class="bg-light p-3 rounded-4 mb-4 text-start">
            <label class="small fw-bold text-danger text-uppercase letter-spacing-1">Reason for Restriction:</label>
            <p class="mb-0 small text-dark mt-1"><?= htmlspecialchars($user['ban_reason'] ?: 'No specific reason provided.') ?></p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm d-flex align-items-center text-start mb-0" role="alert">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div class="small"><?= $success ?></div>
            </div>
        <?php else: ?>
            <div class="appeal-section">
                <h6 class="fw-bold text-dark text-start mb-3">Request Account Review</h6>
                <p class="text-muted small text-start mb-3">
                    To regain access, please upload a clear photo or scanned copy of your <strong>Valid Student ID</strong>. Our team will review your submission shortly.
                </p>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="id_card" class="form-control rounded-pill shadow-none border-2" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Submit Document for Review
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3 rounded-pill py-2 small border-0"><?= $error ?></div>
        <?php endif; ?>

        <hr class="my-4 opacity-10">
        
        <div class="d-flex justify-content-between align-items-center">
            <a href="logout.php" class="text-decoration-none text-muted small fw-bold">
                <i class="bi bi-box-arrow-left me-1"></i> Sign Out
            </a>
            <span class="text-muted small" style="font-size: 0.7rem;">System ID: #<?= $user_id ?></span>
        </div>
    </div>

</body>
</html>