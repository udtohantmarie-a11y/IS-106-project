<?php
require_once '../config/db.php';
requireLogin('admin');

$id = (int)$_GET['id'];
$student = $conn->query("SELECT s.*, u.email, u.is_banned, u.username FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = $id")->fetch_assoc();

if (!$student) header("Location: students.php");

// Handle Ban/Unban Toggle
if (isset($_POST['toggle_ban'])) {
    $new_status = $student['is_banned'] ? 0 : 1;
    $conn->query("UPDATE users SET is_banned = $new_status WHERE id = {$student['user_id']}");
    header("Location: view_student.php?id=$id&msg=Status+Updated");
    exit();
}

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    <div class="container-fluid">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 class="fw-bold mb-0"><?= htmlspecialchars($student['full_name']) ?></h3>
                    <p class="text-muted mb-0"><?= $student['email'] ?> | <?= $student['course'] ?></p>
                </div>
                <div class="ms-auto">
                    <form method="POST">
                        <button type="submit" name="toggle_ban" class="btn <?= $student['is_banned'] ? 'btn-success' : 'btn-danger' ?> rounded-pill px-4 fw-bold">
                            <?= $student['is_banned'] ? 'Unban Account' : 'Ban Account' ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-uppercase small text-muted">Account Information</h6>
                    <table class="table table-sm">
                        <tr><td>Username:</td><td class="fw-bold"><?= $student['username'] ?></td></tr>
                        <tr><td>Year Level:</td><td class="fw-bold"><?= $student['year_level'] ?></td></tr>
                        <tr><td>Verification:</td><td><?= $student['is_verified'] ? '<span class="text-success">Verified</span>' : '<span class="text-danger">Pending</span>' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>