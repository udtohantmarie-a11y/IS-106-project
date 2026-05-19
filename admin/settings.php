<?php
require_once '../config/db.php';
requireLogin('admin');

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// 1. FETCH ADMIN CURRENT DATA
$admin = $conn->query("SELECT * FROM users WHERE id = '$user_id'")->fetch_assoc();

// 2. HANDLE ACCOUNT UPDATE (Username & Email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);

    if (empty($username) || empty($email)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        $update = $conn->query("UPDATE users SET username = '$username', email = '$email' WHERE id = '$user_id'");
        if ($update) {
            $message = "Account information updated successfully!";
            $message_type = "success";
            $admin = $conn->query("SELECT * FROM users WHERE id = '$user_id'")->fetch_assoc(); // Refresh data
        } else {
            $message = "Error updating account. Email/Username might already exist.";
            $message_type = "danger";
        }
    }
}

// 3. HANDLE PASSWORD CHANGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (!password_verify($current_pass, $admin['password'])) {
        $message = "Current password is incorrect.";
        $message_type = "danger";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $message_type = "danger";
    } elseif (strlen($new_pass) < 6) {
        $message = "New password must be at least 6 characters.";
        $message_type = "danger";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hashed_pass' WHERE id = '$user_id'");
        $message = "Password changed successfully!";
        $message_type = "success";
    }
}

// 4. FETCH SYSTEM STATS FOR THE SETTINGS PAGE
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
    'banned' => $conn->query("SELECT COUNT(*) as c FROM users WHERE is_banned = 1")->fetch_assoc()['c'],
    'verified_students' => $conn->query("SELECT COUNT(*) as c FROM students WHERE is_verified = 1")->fetch_assoc()['c']
];

$page_title = "Admin Settings";
$page_subtitle = "Configure your account and system preferences.";
$current_page = "settings";

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Profile & Stats -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-4 mb-4 rounded-4 bg-white">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-3 shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-800 text-dark mb-1">System Administrator</h5>
                    <p class="text-muted small mb-0">Manage platform-wide configurations</p>
                    <hr class="my-4 opacity-10">
                    <div class="text-start">
                        <label class="small fw-bold text-muted text-uppercase mb-2">Platform Summary</label>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">Total Registered Users</span>
                            <span class="small fw-bold text-dark"><?= $stats['users'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">Verified Students</span>
                            <span class="small fw-bold text-success"><?= $stats['verified_students'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Banned Accounts</span>
                            <span class="small fw-bold text-danger"><?= $stats['banned'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS CARD -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-dark text-white">
                    <h6 class="fw-bold mb-3 small text-uppercase">System Maintenance</h6>
                    <p class="small text-white-50">Tools to keep the platform running smoothly.</p>
                    <div class="d-grid gap-2">
                        <a href="reports.php" class="btn btn-light btn-sm rounded-pill fw-bold">Generate System Report</a>
                        <button type="button" class="btn btn-outline-light btn-sm rounded-pill fw-bold" onclick="alert('Database backup initiated...')">Backup Database</button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings Form -->
            <div class="col-lg-8">
                <!-- Account Information -->
                <div class="card border-0 shadow-sm mb-4 rounded-4">
                    <div class="card-header bg-white border-0 py-3 pt-4 px-4">
                        <h6 class="fw-800 text-dark mb-0"><i class="bi bi-person-gear me-2 text-primary"></i>Admin Account Information</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                                    <input type="text" name="username" class="form-control rounded-3 shadow-none border-2" value="<?= htmlspecialchars($admin['username']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                                    <input type="email" name="email" class="form-control rounded-3 shadow-none border-2" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                </div>
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" name="update_account" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Update -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 py-3 pt-4 px-4">
                        <h6 class="fw-800 text-dark mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Update Security Credentials</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                                    <input type="password" name="current_password" class="form-control rounded-3 shadow-none border-2" placeholder="Enter current admin password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                                    <input type="password" name="new_password" class="form-control rounded-3 shadow-none border-2" placeholder="Min. 6 characters" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control rounded-3 shadow-none border-2" placeholder="Repeat new password" required>
                                </div>
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" name="change_password" class="btn btn-dark px-4 rounded-pill fw-bold shadow-sm">Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>