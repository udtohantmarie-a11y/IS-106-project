<?php
require_once '../config/db.php';
requireLogin('admin');

// Handle Approval/Rejection Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $student_id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    // Kunin ang user_id ng student para sa notification at unban logic
    $student_info = $conn->query("SELECT user_id, full_name FROM students WHERE id = $student_id")->fetch_assoc();
    $student_user_id = $student_info['user_id'];

    if ($action === 'approve') {
        // 1. Mark as verified
        $conn->query("UPDATE students SET is_verified = 1 WHERE id = $student_id");
        
        // 2. AUTO-UNBAN: I-activate ang account at alisin ang ban reason
        $conn->query("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = $student_user_id");
        
        // 3. Notification para sa Student (Success)
        $msg_notif = "Your account identity has been verified! Your account restriction has been lifted.";
        $conn->query("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES ($student_user_id, 'system', '$msg_notif', 0, NOW())");
        
        $msg = "Student has been verified and account unbanned.";

    } elseif ($action === 'reject') {
        $reason = mysqli_real_escape_string($conn, $_POST['rejection_reason']);
        
        // I-reset ang verification status at burahin ang path para makapag-upload sila ulit
        $conn->query("UPDATE students SET resume_path = NULL, is_verified = 0 WHERE id = $student_id");
        
        // I-update ang ban reason para malaman nila sa Banned Page kung bakit nareject
        $conn->query("UPDATE users SET ban_reason = 'ID Rejected: $reason' WHERE id = $student_user_id");
        
        // Notification para sa Student (Rejection with Note)
        $msg_notif = "Your ID verification was rejected. Reason: $reason. Please upload a valid document.";
        $conn->query("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES ($student_user_id, 'system', '$msg_notif', 0, NOW())");
        
        $msg = "ID has been rejected and student was notified.";
    }
    
    header("Location: verify_users.php?msg=" . urlencode($msg));
    exit();
}

// Kunin ang mga students na nag-upload ng ID (Verified man o hindi)
$students = $conn->query("
    SELECT s.*, u.email 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE s.resume_path IS NOT NULL 
    ORDER BY s.is_verified ASC, s.created_at DESC
");

$page_title = "ID Validation";
$header_title = "Identity Verification"; 

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .student-avatar-sm {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .avatar-placeholder-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        background: #0d6efd;
        font-size: 0.9rem;
    }
    .transition { transition: all 0.2s ease-in-out; }
    .btn-circle-action:hover { transform: scale(1.08); }
    
    /* Inline Document View Embed Frames */
    .id-embed-frame {
        width: 100%;
        height: 480px;
        border: none;
        border-radius: 12px;
    }
    .id-img-preview {
        max-width: 100%;
        max-height: 480px;
        object-fit: contain;
        border-radius: 12px;
    }

    /* Stacking layers to guarantee modal overlay is above navigation modules */
    .modal { z-index: 2000 !important; }
    .modal-backdrop { z-index: 1990 !important; }
</style>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-800 text-dark mb-0">ID Validation</h3>
                <p class="text-muted small mb-0">Review and verify student identity documents to ensure system integrity.</p>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small fw-bold shadow-sm">
                <i class="bi bi-shield-check text-primary me-1"></i> Security Portal
            </span>
        </div>

        <!-- Success Message Alert -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                    <div><?= htmlspecialchars($_GET['msg']) ?></div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- ID Validation Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3 border-0 fw-bold">STUDENT DETAILS</th>
                            <th class="py-3 border-0 fw-bold">SUBMITTED DOCUMENT</th>
                            <th class="py-3 border-0 fw-bold">STATUS</th>
                            <th class="py-3 border-0 text-center fw-bold">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <!-- Profile Image Display -->
                                            <div class="me-3">
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" 
                                                         class="student-avatar-sm" alt="Profile">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder-sm">
                                                        <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark lh-1 mb-1"><?= htmlspecialchars($row['full_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- Open Verification Document Inline Modal Triggers -->
                                        <button type="button" 
                                                class="btn btn-sm btn-light border px-3 rounded-pill fw-bold text-primary shadow-sm transition btn-view-id-trigger"
                                                data-filepath="<?= htmlspecialchars($row['resume_path']) ?>"
                                                data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                            <i class="bi bi-file-earmark-text me-1"></i> View ID Card
                                        </button>
                                    </td>
                                    <td>
                                        <?php if ($row['is_verified'] == 1): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-bold border border-success">
                                                <i class="bi bi-patch-check-fill me-1"></i> Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 fw-bold border border-warning">
                                                <i class="bi bi-clock-history me-1"></i> Pending Review
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['is_verified'] == 0): ?>
                                            <div class="d-flex justify-content-center gap-2">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Verify identification record for this student?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center btn-circle-action transition" style="width: 32px; height: 32px;" title="Approve ID">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                
                                                <button type="button" class="btn btn-danger btn-sm rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center btn-circle-action transition" 
                                                        style="width: 32px; height: 32px;" title="Reject ID" 
                                                        onclick="openRejectModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small fw-bold">
                                                <i class="bi bi-shield-check me-1"></i> Completed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-shadows display-1 text-light opacity-50"></i>
                                        <p class="text-muted mt-3 mb-0">No student IDs waiting for verification.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Identity Document Preview Modal -->
<div class="modal fade" id="viewIdCardModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 text-dark mb-0"><i class="bi bi-card-image text-primary me-2"></i>Document Attachment Preview</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center" id="idCardModalBody">
                <!-- Content injected dynamically via javascript pipeline -->
            </div>
            <div class="modal-footer border-0 justify-content-end pb-4 px-4 pt-0">
                <a href="#" id="downloadIdBtn" download class="btn btn-light px-4 rounded-pill fw-bold text-dark border small shadow-none">
                    <i class="bi bi-download me-1"></i> Download Document
                </a>
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold small shadow-sm" data-bs-dismiss="modal">Close View</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Reject Identity Card</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted small mb-3">Provide a reason for rejecting the ID of <strong id="rejectStudentName"></strong>. This will be sent to the student's notifications.</p>
                    
                    <input type="hidden" name="id" id="rejectStudentId">
                    <input type="hidden" name="action" value="reject">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Reason for Rejection</label>
                        <select name="rejection_reason" class="form-select rounded-3 shadow-none border-2" required onchange="checkOtherReason(this)">
                            <option value="" disabled selected>Select a reason...</option>
                            <option value="The uploaded image is too blurry/unreadable.">Blurry / Unreadable Image</option>
                            <option value="The document provided is not a valid school ID.">Not a Valid ID</option>
                            <option value="The name on the ID does not match the registered profile name.">Name Mismatch</option>
                            <option value="The ID has already expired.">Expired ID</option>
                            <option value="Other">Other (Please specify)</option>
                        </select>
                    </div>

                    <div id="customReasonDiv" class="d-none">
                        <textarea id="customReasonText" class="form-control rounded-3 shadow-none border-2" rows="3" placeholder="Explain the reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-light px-4 rounded-pill fw-bold text-secondary border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rejectModal;
let viewIdModal;

document.addEventListener('DOMContentLoaded', function() {
    rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    viewIdModal = new bootstrap.Modal(document.getElementById('viewIdCardModal'));

    // Click handler connection for inline file preview orchestration
    document.querySelectorAll('.btn-view-id-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const filepath = this.getAttribute('data-filepath');
            const studentName = this.getAttribute('data-name');
            
            const modalBody = document.getElementById('idCardModalBody');
            const downloadBtn = document.getElementById('downloadIdBtn');
            
            const fullUrl = "../uploads/ids/" + filepath;
            downloadBtn.href = fullUrl;

            const fileExt = filepath.split('.').pop().toLowerCase();
            
            if (fileExt === 'pdf') {
                modalBody.innerHTML = `<iframe src="${fullUrl}" class="id-embed-frame"></iframe>`;
            } else {
                modalBody.innerHTML = `<img src="${fullUrl}" class="id-img-preview shadow-sm border" alt="ID Document for ${studentName}">`;
            }

            viewIdModal.show();
        });
    });
});

function openRejectModal(id, name) {
    document.getElementById('rejectStudentId').value = id;
    document.getElementById('rejectStudentName').innerText = name;
    rejectModal.show();
}

function checkOtherReason(select) {
    const customDiv = document.getElementById('customReasonDiv');
    const customText = document.getElementById('customReasonText');
    if (select.value === 'Other') {
        customDiv.classList.remove('d-none');
        customText.setAttribute('name', 'rejection_reason');
        select.removeAttribute('name');
        customText.required = true;
    } else {
        customDiv.classList.add('d-none');
        customText.removeAttribute('name');
        select.setAttribute('name', 'rejection_reason');
        customText.required = false;
    }
}
</script>

<?php include 'components/footer.php'; ?>