<?php
require_once '../config/db.php';
requireLogin('admin');

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = (int)$_GET['id'];

// 1. Fetch Student and User Account Details
$query = "SELECT s.*, u.email, u.username, u.is_banned, u.ban_reason, u.created_at AS joined_date 
          FROM students s 
          JOIN users u ON u.id = s.user_id 
          WHERE s.id = $student_id";
$result = $conn->query($query);
$student = $result->fetch_assoc();

if (!$student) {
    header("Location: students.php");
    exit();
}

// 2. Fetch Statistics
$app_count = $conn->query("SELECT COUNT(*) as total FROM applications WHERE student_id = $student_id")->fetch_assoc()['total'];
$review_count = $conn->query("SELECT COUNT(*) as total FROM company_reviews WHERE student_id = $student_id")->fetch_assoc()['total'];

// 3. Handle Ban/Unban Toggle via Modal Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ban'])) {
    $new_status = $student['is_banned'] ? 0 : 1;
    $reason = $new_status ? 'Manually banned by administrator' : NULL;
    
    $update = $conn->query("UPDATE users SET is_banned = $new_status, ban_reason = " . ($reason ? "'$reason'" : "NULL") . " WHERE id = {$student['user_id']}");
    
    if ($update) {
        header("Location: view_student.php?id=$student_id&msg=Account status updated successfully");
        exit();
    }
}

$page_title = "Student Profile: " . $student['full_name'];
$header_title = "Profile Management";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .transition { transition: all 0.3s ease; }
    .btn:active { transform: scale(0.98); }
    
    /* Student Profile Image Display Styles */
    .student-profile-avatar {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .avatar-xl-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 2.5rem;
        margin: 0 auto;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Document Viewer Container Frame */
    .doc-viewer-frame {
        width: 100%;
        height: 500px;
        border: none;
        border-radius: 12px;
    }
    .img-doc-preview {
        max-width: 100%;
        max-height: 500px;
        object-fit: contain;
        border-radius: 12px;
    }

    /* Z-INDEX LAYER FIX: Pinupuwersa ang modals at backdrops na lumitaw sa ibabaw ng kahit anong sidebar */
    .modal {
        z-index: 2000 !important;
    }
    .modal-backdrop {
        z-index: 1990 !important;
    }
</style>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <div class="mb-4">
            <a href="students.php" class="btn btn-link text-decoration-none p-0 text-muted small fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Back to Student Directory
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Side: Profile Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100">
                    <!-- PROFILE PHOTO LOGIC -->
                    <div class="mb-3">
                        <?php if (!empty($student['profile_photo'])): ?>
                            <img src="../uploads/profile_photos/<?= htmlspecialchars($student['profile_photo']) ?>" class="student-profile-avatar" alt="Profile Photo">
                        <?php else: ?>
                            <div class="avatar-xl-placeholder">
                                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h4 class="fw-800 text-dark mb-1"><?= htmlspecialchars($student['full_name']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($student['email']) ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <?php if ($student['is_verified']): ?>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 border border-success border-opacity-25 small">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 border border-warning border-opacity-25 small">Pending Verification</span>
                        <?php endif; ?>

                        <?php if ($student['is_banned']): ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-2 shadow-sm small">BANNED</span>
                        <?php endif; ?>
                    </div>

                    <hr class="opacity-10">

                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <h5 class="fw-bold mb-0"><?= $app_count ?></h5>
                            <small class="text-muted text-uppercase" style="font-size: 0.65rem;">Applications</small>
                        </div>
                        <div class="col-6">
                            <h5 class="fw-bold mb-0"><?= $review_count ?></h5>
                            <small class="text-muted text-uppercase" style="font-size: 0.65rem;">Reviews</small>
                        </div>
                    </div>

                    <!-- Trigger Status Modal Button -->
                    <div class="mt-4 pt-2">
                        <button type="button" 
                                class="btn <?= $student['is_banned'] ? 'btn-success' : 'btn-danger' ?> w-100 rounded-pill fw-bold py-2 shadow-sm transition"
                                data-bs-toggle="modal" 
                                data-bs-target="#banToggleModal">
                            <i class="bi bi-<?= $student['is_banned'] ? 'check-circle' : 'slash-circle' ?> me-2"></i>
                            <?= $student['is_banned'] ? 'Unban Account' : 'Ban Account' ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Details and Activity -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-800 text-dark mb-4"><i class="bi bi-person-badge text-primary me-2"></i>Personal Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Course</label>
                            <p class="fw-bold text-dark border-bottom pb-2"><?= htmlspecialchars($student['course']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Year Level</label>
                            <p class="fw-bold text-dark border-bottom pb-2"><?= htmlspecialchars($student['year_level']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Phone Number</label>
                            <p class="fw-bold text-dark border-bottom pb-2"><?= htmlspecialchars($student['phone'] ?: 'Not Provided') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Username</label>
                            <p class="fw-bold text-dark border-bottom pb-2">@<?= htmlspecialchars($student['username']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Member Since</label>
                            <p class="fw-bold text-dark border-bottom pb-2"><?= date('F d, Y', strtotime($student['joined_date'])) ?></p>
                        </div>
                        <?php if($student['is_banned']): ?>
                        <div class="col-12">
                            <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3">
                                <label class="small text-danger text-uppercase fw-bold d-block mb-1">Ban Reason</label>
                                <span class="text-danger small"><?= htmlspecialchars($student['ban_reason'] ?: 'No specific reason provided.') ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ID Card Viewer Container -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-800 text-dark mb-3"><i class="bi bi-card-image text-primary me-2"></i>Verification Document</h5>
                    <?php if ($student['resume_path']): ?>
                        <div class="p-3 bg-light rounded-4 border text-center">
                            <p class="small text-muted mb-3">The student submitted this document for identity validation.</p>
                            <!-- Trigger Document View Modal -->
                            <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#viewIdModal">
                                <i class="bi bi-eye-fill me-2"></i>View Submitted ID
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-x display-4 text-light"></i>
                            <p class="text-muted small mt-2">No verification document has been uploaded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BAN / UNBAN TOGGLE MODAL -->
<div class="modal fade" id="banToggleModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-end">
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4 pt-0 text-center text-dark">
                    <?php if ($student['is_banned']): ?>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                        </div>
                        <h5 class="fw-800 text-dark mb-2">Unban Account?</h5>
                        <p class="text-muted small mb-0">
                            Are you sure you want to restore access for <strong class="text-dark"><?= htmlspecialchars($student['full_name']) ?></strong>? This will re-enable all student platform privileges.
                        </p>
                    <?php else: ?>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-slash-circle-fill fs-2"></i>
                        </div>
                        <h5 class="fw-800 text-dark mb-2">Ban Account?</h5>
                        <p class="text-muted small mb-0">
                            Are you sure you want to restrict <strong class="text-dark"><?= htmlspecialchars($student['full_name']) ?></strong>? The student will be immediately blocked from accessing their account.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 d-grid gap-2 pb-4 px-4 pt-0">
                    <button type="submit" name="toggle_ban" class="btn <?= $student['is_banned'] ? 'btn-success' : 'btn-danger' ?> py-2 rounded-pill fw-bold shadow-sm">
                        <?= $student['is_banned'] ? 'Confirm Unban' : 'Confirm Ban' ?>
                    </button>
                    <button type="button" class="btn btn-light py-2 rounded-pill fw-bold text-secondary border" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SUBMITTED ID DOCUMENT VIEW MODAL -->
<?php if ($student['resume_path']): ?>
<div class="modal fade" id="viewIdModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 text-dark mb-0"><i class="bi bi-shield-identity text-primary me-2"></i>Verification Attachment Preview</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <?php 
                    $file_ext = strtolower(pathinfo($student['resume_path'], PATHINFO_EXTENSION));
                    if ($file_ext === 'pdf'): 
                ?>
                    <iframe src="../uploads/ids/<?= htmlspecialchars($student['resume_path']) ?>" class="doc-viewer-frame"></iframe>
                <?php else: ?>
                    <img src="../uploads/ids/<?= htmlspecialchars($student['resume_path']) ?>" class="img-doc-preview shadow-sm border" alt="Verification Identity Document">
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 justify-content-end pb-4 px-4 pt-0">
                <a href="../uploads/ids/<?= htmlspecialchars($student['resume_path']) ?>" download class="btn btn-light px-4 rounded-pill fw-bold text-dark border small shadow-none">
                    <i class="bi bi-download me-1"></i> Download File
                </a>
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold small shadow-sm" data-bs-dismiss="modal">Close View</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'components/footer.php'; ?>