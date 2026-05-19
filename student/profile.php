<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';
$show_success_modal = false;

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $course = $conn->real_escape_string($_POST['course']);
    $year_level = $conn->real_escape_string($_POST['year_level']);

    $update = $conn->query("UPDATE students SET full_name = '$full_name', course = '$course', year_level = '$year_level' WHERE user_id = '$user_id'");
    
    if ($update) {
        $message = "Your profile details have been updated successfully.";
        $show_success_modal = true;
    } else {
        $error = "An error occurred while updating your profile.";
    }
}

// Handle Profile Photo Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $target_dir = "../uploads/profile_photos/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
    $new_filename = "AVATAR_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
        $error = "Invalid image type. Only JPG, JPEG, and PNG are allowed.";
    } else {
        $old_photo = $conn->query("SELECT profile_photo FROM students WHERE user_id = '$user_id'")->fetch_assoc()['profile_photo'];
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            if ($old_photo && file_exists($target_dir . $old_photo)) unlink($target_dir . $old_photo);
            $conn->query("UPDATE students SET profile_photo = '$new_filename' WHERE user_id = '$user_id'");
            header("Location: profile.php?success=photo");
            exit();
        }
    }
}

// Handle Valid ID Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['valid_id'])) {
    $target_dir = "../uploads/ids/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_extension = strtolower(pathinfo($_FILES["valid_id"]["name"], PATHINFO_EXTENSION));
    $new_filename = "ID_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (!in_array($file_extension, ["jpg", "jpeg", "png", "pdf"])) {
        $error = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
    } else {
        $old_id = $conn->query("SELECT resume_path FROM students WHERE user_id = '$user_id'")->fetch_assoc()['resume_path'];
        if (move_uploaded_file($_FILES["valid_id"]["tmp_name"], $target_file)) {
            if ($old_id && file_exists($target_dir . $old_id)) unlink($target_dir . $old_id);
            $conn->query("UPDATE students SET resume_path = '$new_filename', is_verified = 0 WHERE user_id = '$user_id'");
            $message = "Your Valid ID has been uploaded. Please wait for admin verification.";
            $show_success_modal = true;
        }
    }
}

// HANDLE ACCOUNT DELETION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['confirm_password'];
    
    // Fetch user password hash
    $user_data = $conn->query("SELECT password FROM users WHERE id = '$user_id'")->fetch_assoc();
    
    if (password_verify($password, $user_data['password'])) {
        // Delete related files if they exist (Photo and ID)
        $student_data = $conn->query("SELECT profile_photo, resume_path FROM students WHERE user_id = '$user_id'")->fetch_assoc();
        
        if ($student_data['profile_photo']) @unlink("../uploads/profile_photos/" . $student_data['profile_photo']);
        if ($student_data['resume_path']) @unlink("../uploads/ids/" . $student_data['resume_path']);

        // Delete from users table
        $conn->query("DELETE FROM users WHERE id = '$user_id'");
        
        session_destroy();
        header("Location: ../index.php?msg=account_deleted");
        exit();
    } else {
        $error = "Incorrect password. Account deletion cancelled.";
    }
}

// Get student info
$student = $conn->query("
    SELECT s.*, u.email, u.username
    FROM students s
    JOIN users u ON u.id = s.user_id
    WHERE u.id = '$user_id'
")->fetch_assoc();

if(isset($_GET['success']) && $_GET['success'] == 'photo') {
    $message = "Profile photo updated successfully!";
    $show_success_modal = true;
}

$page_title = "Account Settings";
$current_page = "profile";
$header_title = "My Profile";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .profile-avatar-container { position: relative; width: 120px; height: 120px; }
    .profile-avatar-img { width: 100%; height: 100%; object-fit: cover; border: 4px solid #fff; }
    .photo-upload-btn { position: absolute; bottom: 0; right: 0; background: #0d6efd; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; }
    .danger-zone { border: 1px solid #fee2e2; background-color: #fffafb; }
    
    /* Document Inline Viewer Styles */
    .id-embed-frame { width: 100%; height: 450px; border: none; border-radius: 12px; }
    .id-img-preview { max-width: 100%; max-height: 450px; object-fit: contain; border-radius: 12px; }

    /* Stacking Layers Over Sidebar Control Context */
    .modal { z-index: 2000 !important; }
    .modal-backdrop { z-index: 1990 !important; }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 px-4 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile Form Card -->
            <div class="col-lg-7">
                <div class="card p-4 border-0 shadow-sm rounded-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="profile-avatar-container me-4">
                            <?php if($student['profile_photo']): ?>
                                <img src="../uploads/profile_photos/<?= $student['profile_photo'] ?>" class="profile-avatar-img rounded-circle shadow-sm">
                            <?php else: ?>
                                <div class="profile-avatar-img rounded-circle shadow-sm bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-1">
                                    <?= strtoupper(substr($student['full_name'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <label class="photo-upload-btn shadow-sm" for="photoUpload">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <form id="photoForm" action="" method="POST" enctype="multipart/form-data" class="d-none">
                                <input type="file" id="photoUpload" name="profile_photo" accept="image/*" onchange="document.getElementById('photoForm').submit()">
                            </form>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-1">
                                <?= htmlspecialchars($student['full_name']) ?>
                                <?php if($student['is_verified'] == 1): ?>
                                    <i class="bi bi-patch-check-fill text-primary ms-1" title="Verified Student"></i>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0"><?= htmlspecialchars($student['email']) ?></p>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <div class="row g-3 text-dark">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">LEGAL FULL NAME</label>
                                <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($student['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">DEGREE / COURSE</label>
                                <input type="text" name="course" class="form-control rounded-3" value="<?= htmlspecialchars($student['course']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">YEAR LEVEL</label>
                                <select name="year_level" class="form-select rounded-3">
                                    <option value="1st Year" <?= $student['year_level'] == '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2nd Year" <?= $student['year_level'] == '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3rd Year" <?= $student['year_level'] == '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4th Year" <?= $student['year_level'] == '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="col-12 mt-4 pt-2">
                                <button type="submit" name="update_profile" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">Update Profile Details</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Side Cards -->
            <div class="col-lg-5">
                <!-- Identity Verification Card -->
                <div class="card p-4 mb-4 border-0 shadow-sm rounded-4">
                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-shield-check-fill me-2 text-primary"></i>Identity Verification</h6>
                    <?php if ($student['resume_path']): ?>
                        <div class="p-3 bg-light rounded-4 mb-4 border shadow-sm">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-white p-2 rounded-3 me-3 shadow-sm text-primary">
                                    <i class="bi bi-card-heading fs-2"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Valid ID Submitted</div>
                                    <span class="<?= $student['is_verified'] == 1 ? 'text-success' : 'text-warning' ?> small fw-bold">
                                        <i class="bi bi-<?= $student['is_verified'] == 1 ? 'check2-circle' : 'clock-history' ?> me-1"></i>
                                        <?= $student['is_verified'] == 1 ? 'Verified' : 'Pending Review' ?>
                                    </span>
                                </div>
                            </div>
                            <!-- Inline Modal Toggle Button -->
                            <button type="button" class="btn btn-sm btn-white border px-3 rounded-pill fw-bold w-100 shadow-sm mb-1" data-bs-toggle="modal" data-bs-target="#viewStudentIdModal">
                                <i class="bi bi-eye-fill me-1"></i> View Submitted ID
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if($student['is_verified'] == 0): ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Upload ID (JPG/PNG/PDF)</label>
                                <input class="form-control form-control-sm rounded-3 shadow-none" type="file" name="valid_id" required>
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm w-100 rounded-pill fw-bold py-2">Submit for Verification</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- DANGER ZONE (Delete Account) -->
                <div class="card p-4 border-0 shadow-sm rounded-4 danger-zone">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h6>
                    <p class="text-muted small">Once you delete your account, there is no going back. Please be certain.</p>
                    <button type="button" class="btn pointer btn-outline-danger btn-sm rounded-pill fw-bold px-4" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Submitted ID Document Preview Modal -->
<?php if ($student['resume_path']): ?>
<div class="modal fade" id="viewStudentIdModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 text-dark mb-0"><i class="bi bi-card-image text-primary me-2"></i>My Verification Document</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <?php 
                    $file_ext = strtolower(pathinfo($student['resume_path'], PATHINFO_EXTENSION));
                    if ($file_ext === 'pdf'): 
                ?>
                    <iframe src="../uploads/ids/<?= htmlspecialchars($student['resume_path']) ?>" class="id-embed-frame"></iframe>
                <?php else: ?>
                    <img src="../uploads/ids/<?= htmlspecialchars($student['resume_path']) ?>" class="id-img-preview shadow-sm border" alt="Identity Verification Document">
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 justify-content-end pb-4 px-4 pt-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold small shadow-sm" data-bs-dismiss="modal">Close View</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="" method="POST">
                <div class="modal-body p-5 text-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-trash3-fill fs-1"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Are you absolutely sure?</h4>
                    <p class="text-muted mb-4">To confirm deletion, please enter your password below. This action cannot be undone.</p>
                    
                    <div class="text-start mb-4">
                        <label class="form-label small fw-bold text-muted">CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" class="form-control rounded-3" placeholder="Enter your password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="delete_account" class="btn btn-danger py-3 rounded-pill fw-bold shadow-sm">Permanently Delete Account</button>
                        <button type="button" class="btn btn-light py-3 rounded-pill fw-bold text-muted" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success display-2 mb-3 d-block"></i>
                <h4 class="fw-bold text-dark">Success!</h4>
                <p class="text-muted mb-4"><?= $message ?></p>
                <button type="button" class="btn btn-primary px-5 rounded-pill fw-bold" data-bs-dismiss="modal">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if ($show_success_modal): ?>
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    <?php endif; ?>

    // Sidebar Logic
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');

    function toggleSidebar() { 
        if(sidebar && overlay) {
            sidebar.classList.toggle('show'); 
            overlay.classList.toggle('show'); 
        }
    }
    if(openBtn) openBtn.addEventListener('click', toggleSidebar);
    if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
</script>
</body>
</html>