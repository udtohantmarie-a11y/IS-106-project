<?php 
include 'components/header.php'; 
include 'components/navbar.php'; 

$company_id = $company['id'];
$company_name = $company['company_name'];

// Logic: Update application status with optional rejection note + STUDENT NOTIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['new_status'])) {
    $app_id     = (int)$_POST['app_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $notes      = isset($_POST['rejection_note']) ? mysqli_real_escape_string($conn, $_POST['rejection_note']) : '';
    $allowed    = ['pending', 'viewed', 'accepted', 'rejected'];
    
    if (in_array($new_status, $allowed)) {
        // 1. Update the application status and notes
        $update = $conn->query("
            UPDATE applications a
            JOIN job_listings j ON j.id = a.job_id
            SET a.status = '$new_status', a.rejection_note = '$notes'
            WHERE a.id = '$app_id' AND j.company_id = '$company_id'
        ");

        if ($update) {
            // 2. Fetch student user_id and job_id for the notification
            $info_query = $conn->query("
                SELECT s.user_id, j.title, j.id AS job_id 
                FROM applications a 
                JOIN students s ON a.student_id = s.id 
                JOIN job_listings j ON a.job_id = j.id 
                WHERE a.id = '$app_id'
            ");
            
            if ($info = $info_query->fetch_assoc()) {
                $student_user_id = $info['user_id'];
                $job_title = $info['title'];
                $job_id = $info['job_id'];

                // 3. Construct Notification Message
                $msg = "";
                if ($new_status == 'viewed') {
                    $msg = "$company_name has viewed your application for $job_title.";
                } elseif ($new_status == 'accepted') {
                    $msg = "Congratulations! Your application for $job_title at $company_name has been ACCEPTED.";
                } elseif ($new_status == 'rejected') {
                    $msg = "Update on your application for $job_title: $company_name decided not to move forward.";
                }

                // 4. Insert notification
                if (!empty($msg)) {
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, job_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                    $stmt->bind_param("iis", $student_user_id, $job_id, $msg);
                    $stmt->execute();
                }

                echo "<script>alert('Status updated and student notified!'); window.location.href='applicants.php';</script>";
            }
        }
    }
}

// Filters Logic
$job_filter = isset($_GET['job_id']) && is_numeric($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = "j.company_id = '$company_id'";
if ($job_filter)    $where .= " AND j.id = '$job_filter'";
if ($status_filter) $where .= " AND a.status = '$status_filter'";

// Fetch Applicants with Profile Photo
$applicants = $conn->query("
    SELECT a.*, s.full_name, s.course, s.year_level, s.phone, s.is_verified, s.profile_photo,
           a.resume_path AS application_resume, 
           j.title AS job_title, j.type AS job_type, u.email
    FROM applications a
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    JOIN job_listings j ON j.id = a.job_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

$all_listings = $conn->query("SELECT id, title FROM job_listings WHERE company_id='$company_id' ORDER BY created_at DESC");
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
    .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.02) !important; }
    .transition { transition: all 0.3s ease; }
    
    /* Avatar Styling */
    .applicant-avatar {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .avatar-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: white;
        background: #0d6efd;
        font-size: 1.1rem;
    }

    .verified-badge {
        font-size: 0.55rem;
        padding: 2px 6px;
        border-radius: 50px;
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 2px;
        border: 1px solid #bae6fd;
        text-transform: uppercase;
        vertical-align: middle;
    }
</style>

<div class="container pb-5 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Manage Applicants</h2>
        <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
            <i class="bi bi-people-fill text-success me-1"></i> <?= $applicants->num_rows ?> Total Applicants
        </span>
    </div>

    <!-- Filter Card -->
    <div class="card p-3 mb-4 shadow-sm border-0 rounded-4 bg-white">
        <form class="row g-3 align-items-center" method="GET">
            <div class="col-md-4">
                <label class="small fw-bold text-muted text-uppercase">Job Position</label>
                <select name="job_id" class="form-select rounded-3 shadow-none border-2" onchange="this.form.submit()">
                    <option value="">All Listings</option>
                    <?php while ($lst = $all_listings->fetch_assoc()): ?>
                        <option value="<?= $lst['id'] ?>" <?= $job_filter == $lst['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lst['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted text-uppercase">Status</label>
                <select name="status" class="form-select rounded-3 shadow-none border-2" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending"  <?= $status_filter === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="viewed"   <?= $status_filter === 'viewed'   ? 'selected' : '' ?>>Viewed</option>
                    <option value="accepted" <?= $status_filter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 mt-md-4 pt-2">
                <?php if ($job_filter || $status_filter): ?>
                    <a href="applicants.php" class="text-danger small fw-bold text-decoration-none">
                        <i class="bi bi-x-circle me-1"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Applicants Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4 py-3">STUDENT INFO</th>
                        <th>POSITION</th>
                        <th>DOCUMENT</th>
                        <th>STATUS</th>
                        <th class="text-center pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($applicants->num_rows > 0): ?>
                        <?php while ($app = $applicants->fetch_assoc()): ?>
                            <tr class="transition">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <!-- Profile Photo -->
                                        <div class="me-3">
                                            <?php if (!empty($app['profile_photo'])): ?>
                                                <img src="../uploads/profile_photos/<?= htmlspecialchars($app['profile_photo']) ?>" 
                                                     class="applicant-avatar" alt="Profile">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?= strtoupper(substr($app['full_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($app['full_name']) ?></span>
                                                <?php if($app['is_verified'] == 1): ?>
                                                    <span class="verified-badge" title="Verified Account">
                                                        <i class="bi bi-patch-check-fill"></i> Verified
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($app['email']) ?></div>
                                            <div class="text-primary small fw-semibold" style="font-size: 0.7rem;"><?= htmlspecialchars($app['course']) ?> &bull; <?= $app['year_level'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($app['job_title']) ?></div>
                                    <span class="badge bg-light text-primary border" style="font-size: 9px;"><?= strtoupper($app['job_type']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($app['application_resume'])): ?>
                                        <a href="../uploads/resumes/<?= htmlspecialchars($app['application_resume']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-light border px-3 rounded-pill fw-bold text-primary shadow-sm" style="font-size: 0.7rem;">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Resume
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No File</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $s = $app['status'];
                                        $badge_class = ($s == 'accepted') ? 'success' : (($s == 'rejected') ? 'danger' : (($s == 'viewed') ? 'info' : 'warning'));
                                    ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-<?= $badge_class ?> bg-opacity-10 text-<?= $badge_class ?> fw-bold border border-<?= $badge_class ?> border-opacity-25" style="font-size: 0.7rem;">
                                        <?= ucfirst($s) ?>
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-dark px-3 rounded-pill fw-bold shadow-sm" 
                                            onclick="openStatusModal(<?= $app['id'] ?>, '<?= $app['status'] ?>', '<?= htmlspecialchars($app['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($app['rejection_note'] ?? '', ENT_QUOTES) ?>')">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">No applicants found matching the filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- STATUS UPDATE MODAL -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form id="statusForm" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Update Status</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="small text-muted mb-4 fst-italic">Applicant: <strong class="text-dark" id="studentName"></strong></p>
                    <input type="hidden" name="app_id" id="modalAppId">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">New Status</label>
                        <select name="new_status" id="statusSelect" class="form-select rounded-3 shadow-none border-2" required onchange="toggleRejectionNote()">
                            <option value="pending">Pending</option>
                            <option value="viewed">Viewed</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div id="rejectionField" class="mb-0 d-none">
                        <label class="form-label small fw-bold text-danger text-uppercase">Feedback / Rejection Reason</label>
                        <textarea name="rejection_note" id="rejectionNote" class="form-control rounded-3 shadow-none border-2" rows="3" placeholder="Explain the reason for this decision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">Confirm & Notify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- INCLUSION OF BOOTSTRAP BUNDLE JS TO PREVENT TYPE ERRORS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let myModal = null;

    // Secure operational attachment via DOM ready logic execution pipeline
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap !== 'undefined') {
            myModal = new bootstrap.Modal(document.getElementById('statusModal'));
        } else {
            console.error("Bootstrap JS framework asset load failure exception.");
        }
    });

    function openStatusModal(id, currentStatus, name, note) {
        document.getElementById('modalAppId').value = id;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('studentName').innerText = name;
        document.getElementById('rejectionNote').value = note;
        toggleRejectionNote();
        
        if (myModal) {
            myModal.show();
        } else if (typeof bootstrap !== 'undefined') {
            // Backup runtime instance initialization target path fallback logic
            myModal = new bootstrap.Modal(document.getElementById('statusModal'));
            myModal.show();
        } else {
            alert("Unable to open window container action: Bootstrap library asset missing.");
        }
    }

    function toggleRejectionNote() {
        const status = document.getElementById('statusSelect').value;
        const field = document.getElementById('rejectionField');
        if (status === 'rejected') {
            field.classList.remove('d-none');
        } else {
            field.classList.add('d-none');
        }
    }
</script>
</body>
</html>