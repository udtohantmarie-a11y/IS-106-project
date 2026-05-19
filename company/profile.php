<?php 
include 'components/header.php'; 
include 'components/navbar.php'; 

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// 1. HANDLE PROFILE & PHOTO UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $company_name   = mysqli_real_escape_string($conn, $_POST['company_name']);
    $industry       = mysqli_real_escape_string($conn, $_POST['industry']);
    $about          = mysqli_real_escape_string($conn, $_POST['about']); 
    $address        = mysqli_real_escape_string($conn, $_POST['address']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
    $website        = mysqli_real_escape_string($conn, $_POST['website']);

    // Check if a new logo is being uploaded
    $profile_photo_sql = "";
    if (!empty($_FILES['profile_photo']['name'])) {
        $target_dir = "../uploads/company_logos/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed)) {
            $new_filename = "LOGO_" . $user_id . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_dir . $new_filename)) {
                $profile_photo_sql = ", profile_photo = '$new_filename'";
            }
        } else {
            $message = "Invalid image type. Only JPG, JPEG, & PNG are allowed.";
            $message_type = "danger";
        }
    }

    if (empty($message)) {
        $update_sql = "UPDATE companies SET 
                        company_name = '$company_name', 
                        industry = '$industry', 
                        about = '$about', 
                        address = '$address', 
                        contact_person = '$contact_person', 
                        phone = '$phone', 
                        website = '$website' 
                        $profile_photo_sql
                      WHERE user_id = '$user_id'";

        if ($conn->query($update_sql)) {
            $message = "Profile updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating profile: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// 2. HANDLE PASSWORD CHANGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $user_data = $conn->query("SELECT password FROM users WHERE id = '$user_id'")->fetch_assoc();

    if (!password_verify($current_pass, $user_data['password'])) {
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
        if ($conn->query("UPDATE users SET password = '$hashed_pass' WHERE id = '$user_id'")) {
            $message = "Password changed successfully!";
            $message_type = "success";
        }
    }
}

// 3. HANDLE ACCOUNT DELETION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['confirm_password'];
    $user_data = $conn->query("SELECT password FROM users WHERE id = '$user_id'")->fetch_assoc();
    
    if (password_verify($password, $user_data['password'])) {
        if ($conn->query("DELETE FROM users WHERE id = '$user_id'")) {
            session_destroy();
            header("Location: ../index.php?msg=account_deleted");
            exit();
        }
    } else {
        $message = "Incorrect password. Account deletion cancelled.";
        $message_type = "danger";
    }
}

// Fetch Latest Data
$company = $conn->query("SELECT c.*, u.email, u.username FROM companies c JOIN users u ON u.id = c.user_id WHERE u.id = '$user_id'")->fetch_assoc();
?>

<style>
    .company-logo-preview {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 20%;
        border: 4px solid #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    .logo-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 20%;
        background: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 800;
        margin: 0 auto;
    }
    .main-card { border-radius: 1.2rem; }
</style>

<div class="container pb-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 fw-bold text-dark mb-0">Company Settings & Profile</h2>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">Account ID: #<?= $user_id ?></span>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm border-0 mb-4 rounded-4" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i><?= $message ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-md-4">
                    <!-- Identity Card -->
                    <div class="card border-0 shadow-sm text-center p-4 mb-4 main-card">
                        <div class="mb-3">
                            <?php if (!empty($company['profile_photo'])): ?>
                                <img src="../uploads/company_logos/<?= $company['profile_photo'] ?>" class="company-logo-preview" alt="Logo">
                            <?php else: ?>
                                <div class="logo-placeholder"><?= strtoupper(substr($company['company_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($company['company_name']) ?></h5>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($company['industry'] ?: 'Industry not set') ?></p>
                        <hr class="my-3 opacity-10">
                        <div class="text-start">
                            <small class="text-muted d-block">Username</small>
                            <p class="small fw-bold mb-2">@<?= $company['username'] ?></p>
                            <small class="text-muted d-block">Email Address</small>
                            <p class="small fw-bold mb-0"><?= $company['email'] ?></p>
                        </div>
                    </div>

                    <!-- Security Card -->
                    <div class="card border-0 shadow-sm p-4 mb-4 main-card">
                        <h6 class="fw-bold mb-3 text-uppercase small text-primary">Security Update</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Current Password</label>
                                <input type="password" name="current_password" class="form-control rounded-3 shadow-none" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">New Password</label>
                                <input type="password" name="new_password" class="form-control rounded-3 shadow-none" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control rounded-3 shadow-none" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">Update Password</button>
                        </form>
                    </div>

                    <!-- Danger Zone -->
                    <div class="card border-0 shadow-sm p-4 main-card" style="background-color: #fff5f5; border: 1px solid #ffe3e3 !important;">
                        <h6 class="fw-bold mb-2 text-uppercase small text-danger">Danger Zone</h6>
                        <p class="text-muted small mb-3">Deleting your account is permanent and will remove all listings.</p>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            Delete Account
                        </button>
                    </div>
                </div>

                <!-- Right Column (Profile Editor) -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm mb-4 main-card">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Company Information</h6>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <!-- Photo Upload -->
                                    <div class="col-12 mb-2">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Update Profile Logo</label>
                                        <input type="file" name="profile_photo" class="form-control rounded-3 shadow-none" accept="image/*">
                                        <small class="text-muted">Recommended: Square image, max 2MB.</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Official Company Name *</label>
                                        <input type="text" name="company_name" class="form-control rounded-3 shadow-none" value="<?= htmlspecialchars($company['company_name']) ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Industry</label>
                                        <input type="text" name="industry" class="form-control rounded-3 shadow-none" value="<?= htmlspecialchars($company['industry']) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Contact Person</label>
                                        <input type="text" name="contact_person" class="form-control rounded-3 shadow-none" value="<?= htmlspecialchars($company['contact_person']) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Official Phone</label>
                                        <input type="text" name="phone" class="form-control rounded-3 shadow-none" value="<?= htmlspecialchars($company['phone']) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Website URL</label>
                                        <input type="url" name="website" class="form-control rounded-3 shadow-none" value="<?= htmlspecialchars($company['website']) ?>" placeholder="https://...">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">About / Description</label>
                                        <textarea name="about" class="form-control rounded-3 shadow-none" rows="6" placeholder="Company mission, vision, history..."><?= htmlspecialchars($company['about']) ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Office Address</label>
                                        <textarea name="address" class="form-control rounded-3 shadow-none" rows="2"><?= htmlspecialchars($company['address']) ?></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" name="update_profile" class="btn btn-dark px-5 py-2 fw-bold rounded-pill shadow-sm">Save Profile Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DELETE ACCOUNT MODAL -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form method="POST">
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-exclamation-octagon-fill text-danger display-4 mb-3"></i>
                    <h5 class="fw-bold">Permanently Delete?</h5>
                    <p class="text-muted small">This will erase all your company data and listings. Enter password to confirm.</p>
                    <input type="password" name="confirm_password" class="form-control rounded-3 shadow-none mb-3" placeholder="Password" required>
                    <button type="submit" name="delete_account" class="btn btn-danger w-100 rounded-pill fw-bold mb-2">Confirm Delete</button>
                    <button type="button" class="btn btn-light w-100 rounded-pill fw-bold text-muted" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>