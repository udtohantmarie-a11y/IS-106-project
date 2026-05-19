<?php
require_once '../config/db.php';
requireLogin('company');

$user_id = $_SESSION['user_id'];

// Kunin ang company data
$company_query = $conn->query("SELECT * FROM companies WHERE user_id = '$user_id'");
$company = $company_query->fetch_assoc();

if (!$company) {
    die("Company profile not found.");
}

$co_id = $company['id'];

/**
 * Unified Activity Query
 * Pinagsama ang Notifications at Applications na may kasamang is_verified status at profile_photo
 */
$activities = $conn->query("
    SELECT n.id, n.message, n.created_at, n.is_read, 'system' as source, n.type, 0 as is_verified, NULL as profile_photo, NULL as full_name
    FROM notifications n
    WHERE n.user_id = '$user_id' 
    AND (n.message LIKE '%application%' OR n.message LIKE '%applied%' OR n.message LIKE '%rating%' OR n.message LIKE '%feedback%')
    
    UNION
    
    SELECT a.id, CONCAT(s.full_name, ' applied for ', j.title) as message, a.applied_at as created_at, 
           (CASE WHEN a.status = 'pending' THEN 0 ELSE 1 END) as is_read, 'app' as source, 'application' as type, s.is_verified, s.profile_photo, s.full_name
    FROM applications a 
    JOIN students s ON a.student_id = s.id 
    JOIN job_listings j ON a.job_id = j.id 
    WHERE j.company_id = '$co_id'
    
    ORDER BY created_at DESC LIMIT 20
");

include 'components/header.php';
include 'components/navbar.php';
?>

<style>
    .transition { transition: all 0.2s ease; }
    .hover-primary:hover { background-color: #0d6efd !important; color: white !important; }
    .fw-600 { font-weight: 600; }
    
    /* Profile Image Styling */
    .activity-avatar {
        width: 42px;
        height: 42px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    .avatar-placeholder {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        background: #0d6efd;
        font-size: 0.9rem;
    }
    .system-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
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

    /* Modal Stacking Layers Fix */
    .modal { z-index: 2000 !important; }
    .modal-backdrop { z-index: 1990 !important; }
</style>

<div class="container pb-5 mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1 text-dark">Recent Activity</h2>
            <p class="text-muted small mb-0">Track your notifications and application updates.</p>
        </div>
        <div class="d-flex gap-2">
            <button id="markAllRead" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold shadow-sm transition">
                Mark all read
            </button>
            <!-- Trigger Bootstrap Modal instead of native confirm drop handler -->
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold shadow-sm transition" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                <i class="bi bi-trash3 me-1"></i> Clear History
            </button>
        </div>
    </div>

    <!-- Activity Table Card -->
    <div class="card shadow-sm overflow-hidden border-0 rounded-4 bg-white">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Activity Details</th>
                        <th class="py-3 text-muted small fw-bold text-uppercase text-center">Category</th>
                        <th class="py-3 text-muted small fw-bold text-uppercase text-center">Time</th>
                        <th class="py-3 text-muted small fw-bold text-uppercase text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activities && $activities->num_rows > 0): ?>
                        <?php while ($row = $activities->fetch_assoc()): ?>
                            <?php $is_unread = ($row['is_read'] == 0); ?>
                            <tr class="<?= $is_unread ? 'border-start border-4 border-primary bg-primary bg-opacity-10' : '' ?> transition">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <!-- Profile Photo or System Icon -->
                                        <div class="me-3">
                                            <?php if ($row['source'] === 'app'): ?>
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" class="activity-avatar" alt="Profile">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="system-icon-box">
                                                    <i class="bi bi-bell-fill"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="<?= $is_unread ? 'fw-bold text-dark' : 'text-muted' ?> small mb-0">
                                                    <?= htmlspecialchars($row['message']) ?>
                                                </div>
                                                <!-- VERIFIED BADGE -->
                                                <?php if($row['source'] == 'app' && $row['is_verified'] == 1): ?>
                                                    <span class="verified-badge" title="Verified Applicant">
                                                        <i class="bi bi-patch-check-fill"></i> Verified
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.65rem;">
                                                <span class="badge bg-light text-dark border-0 fw-normal text-uppercase p-0">Source: <?= $row['source'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $badge_class = 'bg-info text-info';
                                        if($row['type'] == 'rating') $badge_class = 'bg-warning text-warning';
                                        if($row['type'] == 'application') $badge_class = 'bg-primary text-primary';
                                    ?>
                                    <span class="badge rounded-pill <?= $badge_class ?> bg-opacity-10 px-3 border border-<?= explode(' ', $badge_class)[0] ?> border-opacity-25" style="font-size: 0.7rem;">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted small">
                                    <div class="fw-bold" style="font-size: 0.75rem;"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                    <div style="font-size: 0.65rem;"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="../company/<?= $row['source'] == 'app' ? 'applicants.php' : 'feedbacks.php' ?>" 
                                       class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-primary fw-bold"
                                       style="font-size: 0.7rem;"
                                       onclick="markSingleRead(<?= $row['id'] ?>, '<?= $row['source'] ?>')">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-mailbox2 text-muted opacity-25" style="font-size: 4rem;"></i>
                                    <h6 class="text-muted mt-3">Your activity log is clean!</h6>
                                    <p class="text-muted small">No recent updates to show right now.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACTIVITY LOG CLEAR HISTORY MODAL -->
<div class="modal fade" id="clearHistoryModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-end">
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0 text-center text-dark">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-trash-fill fs-2"></i>
                </div>
                <h5 class="fw-800 text-dark mb-2">Clear History?</h5>
                <p class="text-muted small mb-0">
                    Are you sure you want to flush your notification records? This operation is permanent and cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-0 d-grid gap-2 pb-4 px-4 pt-0">
                <button type="button" id="confirmClearBtn" class="btn btn-danger py-2 rounded-pill fw-bold shadow-sm">Clear Log Archive</button>
                <button type="button" class="btn btn-light py-2 rounded-pill fw-bold text-secondary border" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- FIRST LOAD BOOTSTRAP BUNDLE FRAMEWORK SCRIPT ASSET -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let singleClearHistoryModalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        singleClearHistoryModalInstance = new bootstrap.Modal(document.getElementById('clearHistoryModal'));
    }

    // Connect confirm button to operational transaction pipeline
    document.getElementById('confirmClearBtn').addEventListener('click', function() {
        if (singleClearHistoryModalInstance) {
            singleClearHistoryModalInstance->hide();
        }
        executeHistoryClearanceRoutine();
    });
});

// Mark a single system notification as read when clicking "View"
function markSingleRead(id, source) {
    if(source === 'system') {
        fetch('api_mark_read.php?id=' + id);
    }
}

// Mark All as Read logic
document.getElementById('markAllRead').addEventListener('click', function() {
    fetch('api_mark_read.php')
        .then(response => response.json())
        .then(data => { 
            if(data.status === 'success') location.reload(); 
        });
});

// Segment isolation handler for processing AJAX transaction
function executeHistoryClearanceRoutine() {
    fetch('api_clear_activity.php')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => console.error('Error handling log clearance transmission:', err));
}
</script>
</body>
</html>