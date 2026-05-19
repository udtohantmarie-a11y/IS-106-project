<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// 1. Get student info
$student_query = $conn->query("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
");
$student = $student_query->fetch_assoc();
$student_id = $student['id'];

// 2. Handle Mark All as Read (PHP POST)
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id'");
    header("Location: notifications.php");
    exit();
}

// 3. Filter by Read/Unread Status
$filter = isset($_GET['view']) ? $conn->real_escape_string($_GET['view']) : '';
$where = "n.user_id = '$user_id'";
if ($filter === 'unread') $where .= " AND n.is_read = 0";
if ($filter === 'read') $where .= " AND n.is_read = 1";

// 4. Fetch Notifications (JOINed companies to get Profile Photo)
$notif_query = $conn->query("
    SELECT n.*, j.title AS job_title, a.rejection_note, c.company_name, c.profile_photo AS company_logo
    FROM notifications n 
    LEFT JOIN job_listings j ON n.job_id = j.id 
    LEFT JOIN companies c ON j.company_id = c.id
    LEFT JOIN applications a ON a.job_id = j.id AND a.student_id = '$student_id'
    WHERE $where 
    ORDER BY n.created_at DESC
");

// 5. Calculate Counts
$count_unread = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id='$user_id' AND is_read=0")->fetch_assoc()['c'];
$count_total = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id='$user_id'")->fetch_assoc()['c'];

// 6. Component Variables
$page_title = "Notifications";
$current_page = "notifications";
$header_title = "Updates & Alerts";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
    .unread-row { background-color: rgba(13, 110, 253, 0.03) !important; border-left: 4px solid #0d6efd !important; }
    .transition { transition: all 0.2s ease; }
    .nav-pills .nav-link { color: #6c757d; font-weight: 600; font-size: 0.9rem; border-radius: 50px; padding: 8px 20px; }
    .nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
    .rejection-box { background-color: #fff5f5; border: 1px dashed #feb2b2; color: #c53030; font-size: 0.8rem; border-radius: 8px; }
    
    /* Company Logo Styles */
    .notif-logo {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #eee;
    }
    .notif-icon-circle {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <!-- Filter Pills -->
            <div class="nav nav-pills overflow-auto flex-nowrap pb-1">
                <a href="notifications.php" class="nav-link <?= !$filter ? 'active' : '' ?> shadow-sm me-2">
                    All <span class="badge bg-light text-dark ms-1 fw-bold"><?= $count_total ?></span>
                </a>
                <a href="?view=unread" class="nav-link <?= $filter === 'unread' ? 'active' : '' ?> shadow-sm me-2">
                    Unread <span class="badge bg-light text-dark ms-1 fw-bold"><?= $count_unread ?></span>
                </a>
                <a href="?view=read" class="nav-link <?= $filter === 'read' ? 'active' : '' ?> shadow-sm">
                    Read
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-3">
                <?php if ($count_unread > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_all_read" class="btn btn-link text-primary fw-bold text-decoration-none small p-0">
                            <i class="bi bi-check2-all me-1"></i> Mark all as read
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($count_total > 0): ?>
                    <button id="clearNotifs" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold shadow-sm transition">
                        <i class="bi bi-trash3 me-1"></i> Clear All
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3 border-0" style="width: 80px;"></th>
                            <th class="py-3 border-0">NOTIFICATION DETAILS</th>
                            <th class="py-3 border-0 text-end pe-4">RECEIVED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notif_query->num_rows > 0): ?>
                            <?php while ($notif = $notif_query->fetch_assoc()): 
                                $is_read = $notif['is_read'];
                                $msg_lower = strtolower($notif['message']);
                                
                                // Category labels based on message content
                                if (strpos($msg_lower, 'accepted') !== false) {
                                    $type_label = "Application Accepted";
                                    $theme = "success";
                                } elseif (strpos($msg_lower, 'viewed') !== false) {
                                    $type_label = "Resume Viewed";
                                    $theme = "info";
                                } elseif (strpos($msg_lower, 'decided not') !== false || strpos($msg_lower, 'rejected') !== false) {
                                    $type_label = "Status Update";
                                    $theme = "danger";
                                } else {
                                    $type_label = "System Alert";
                                    $theme = "primary";
                                }
                            ?>
                                <tr class="<?= !$is_read ? 'unread-row' : '' ?> transition">
                                    <td class="ps-4">
                                        <?php if (!empty($notif['company_logo'])): ?>
                                            <!-- Show Company Logo if available -->
                                            <img src="../uploads/company_logos/<?= htmlspecialchars($notif['company_logo']) ?>" class="notif-logo shadow-sm" alt="Logo">
                                        <?php else: ?>
                                            <!-- Fallback to Themed Icon -->
                                            <div class="notif-icon-circle bg-<?= $theme ?> bg-opacity-10 text-<?= $theme ?> shadow-sm">
                                                <i class="bi <?= ($theme == 'success') ? 'bi-check-circle' : (($theme == 'danger') ? 'bi-x-circle' : 'bi-bell') ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex flex-column">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="fw-bold text-<?= $theme ?> me-2 small text-uppercase letter-spacing-1" style="font-size: 0.7rem;"><?= $type_label ?></span>
                                                <?php if (!$is_read): ?>
                                                    <span class="badge bg-primary p-1 rounded-circle" style="width: 6px; height: 6px;"> </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="<?= !$is_read ? 'fw-600 text-dark' : 'text-muted' ?> small mb-2 lh-base" style="max-width: 650px;">
                                                <?= htmlspecialchars($notif['message']) ?>
                                            </p>

                                            <?php if (!empty($notif['rejection_note']) && (strpos($msg_lower, 'decided not') !== false)): ?>
                                                <div class="rejection-box p-2 mb-2">
                                                    <i class="bi bi-info-circle-fill me-1"></i> 
                                                    <strong>Note from <?= htmlspecialchars($notif['company_name'] ?? 'Company') ?>:</strong> <?= htmlspecialchars($notif['rejection_note']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($notif['job_id']): ?>
                                                <div>
                                                    <a href="job_details.php?id=<?= $notif['job_id'] ?>&notif_id=<?= $notif['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                                        View Job Details <i class="bi bi-arrow-right ms-1"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 text-muted small fw-medium">
                                        <span class="text-dark"><?= date('M d, Y', strtotime($notif['created_at'])) ?></span><br>
                                        <span class="opacity-50"><?= date('h:i A', strtotime($notif['created_at'])) ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-bell-slash display-1 text-light opacity-50"></i>
                                        <p class="text-muted mt-3 mb-0">Your notification inbox is empty.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

    // Clear All Notifications Logic
    document.getElementById('clearNotifs')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to permanently clear all your notifications?')) {
            fetch('api_clear_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error clearing notifications: ' + data.message);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
    });
</script>
</body>
</html>