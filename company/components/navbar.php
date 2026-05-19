<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$co_id = $company['id'];
$co_user_id = $_SESSION['user_id'];

/**
 * 1. BILANGIN ANG UNREAD ITEMS
 * Binibilang ang pending applications at unread notifications.
 */
$counts_query = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM applications a JOIN job_listings j ON a.job_id = j.id WHERE j.company_id = '$co_id' AND a.status = 'pending') as pending_apps,
        (SELECT COUNT(*) FROM notifications WHERE user_id = '$co_user_id' AND is_read = 0) as unread_notifs
");
$counts = $counts_query->fetch_assoc();
$total_unread = ($counts['pending_apps'] ?? 0) + ($counts['unread_notifs'] ?? 0);

/**
 * 2. KUNIN ANG RECENT UPDATES (FILTERED)
 * Ginamit ang 'n.id' at 'a.id' para maiwasan ang "ambiguous column" error.
 */
$recent_updates_query = $conn->query("
    SELECT n.id, n.message, n.created_at, 'system' as source 
    FROM notifications n
    WHERE n.user_id = '$co_user_id' 
    AND (n.message LIKE '%application%' OR n.message LIKE '%applied%' OR n.message LIKE '%rating%' OR n.message LIKE '%feedback%')
    
    UNION
    
    SELECT a.id, CONCAT(s.full_name, ' applied for ', j.title) as message, a.applied_at as created_at, 'app' as source 
    FROM applications a 
    JOIN students s ON a.student_id = s.id 
    JOIN job_listings j ON a.job_id = j.id 
    WHERE j.company_id = '$co_id' AND a.status = 'pending'
    
    ORDER BY created_at DESC LIMIT 5
");
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top mb-4 shadow-sm py-3">
    <div class="container">
        <!-- Unified Branding Logo -->
        <a class="navbar-brand fw-800 fs-4 d-flex align-items-center" href="dashboard.php" style="letter-spacing: -0.5px;">
            <i class="bi bi-intersect text-primary me-2"></i>
            <span>JobBoard<span class="text-primary">.</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 fw-600 <?= $current_page == 'dashboard.php' ? 'text-primary active' : 'text-muted' ?>" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 fw-600 <?= $current_page == 'my_listings.php' ? 'text-primary active' : 'text-muted' ?>" href="my_listings.php">My Listings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 fw-600 <?= $current_page == 'applicants.php' ? 'text-primary active' : 'text-muted' ?>" href="applicants.php">Applicants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 fw-600 <?= $current_page == 'feedbacks.php' ? 'text-primary active' : 'text-muted' ?>" href="feedbacks.php">Feedbacks</a>
                </li>
                <!-- NEW: ACTIVITY PAGE LINK -->
                <li class="nav-item">
                    <a class="nav-link px-3 fw-600 <?= $current_page == 'activity.php' ? 'text-primary active' : 'text-muted' ?>" href="activity.php">Activity</a>
                </li>

                <!-- NOTIFICATION BELL DROPDOWN -->
                <li class="nav-item dropdown mx-1">
                    <a class="nav-link position-relative d-flex align-items-center justify-content-center bg-light rounded-circle" 
                       href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" 
                       style="width: 40px; height: 40px;">
                        <i class="bi bi-bell fs-5 text-dark"></i>
                        <?php if ($total_unread > 0): ?>
                            <span id="unread-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" 
                                  style="margin-top: 5px; margin-left: -5px; font-size: 0.6rem;">
                                <?= $total_unread ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3 py-0 overflow-hidden" 
                        aria-labelledby="notifDropdown" style="width: 320px;">
                        <li class="px-3 py-3 fw-bold border-bottom bg-light small text-uppercase letter-spacing-1">Recent Activity</li>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if ($recent_updates_query->num_rows > 0): ?>
                                <?php while($row = $recent_updates_query->fetch_assoc()): ?>
                                    <li>
                                        <a class="dropdown-item py-3 border-bottom transition notif-link" 
                                           href="<?= $row['source'] == 'app' ? 'applicants.php' : 'feedbacks.php' ?>"
                                           data-id="<?= $row['id'] ?>" 
                                           data-source="<?= $row['source'] ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="me-2_notif mt-1">
                                                    <i class="bi <?= $row['source'] == 'app' ? 'bi-person-plus-fill text-primary' : 'bi-chat-left-text-fill text-warning' ?> me-2"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="small text-dark lh-sm fw-600" style="white-space: normal;">
                                                        <?= htmlspecialchars($row['message']) ?>
                                                    </div>
                                                    <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                                        <i class="bi bi-clock me-1"></i><?= date('M d, h:i A', strtotime($row['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    No updates yet
                                </li>
                            <?php endif; ?>
                        </div>
                        <!-- Updated View All Link -->
                        <li><a class="dropdown-item text-center py-2 small text-primary fw-bold" href="activity.php">View All Activity</a></li>
                    </ul>
                </li>

                <!-- PROFILE & LOGOUT -->
                <li class="nav-item ms-lg-3 d-flex align-items-center gap-2 border-start ps-lg-3 mt-3 mt-lg-0">
                    <a class="nav-link p-0" href="profile.php" title="Profile">
                        <?php if (!empty($company['profile_photo'])): ?>
                            <!-- DYNAMIC COMPANY LOGO IMAGE -->
                            <img src="../uploads/company_logos/<?= htmlspecialchars($company['profile_photo']) ?>" 
                                 class="rounded-circle object-fit-cover shadow-sm border" 
                                 style="width: 40px; height: 40px;" 
                                 alt="Logo">
                        <?php else: ?>
                            <!-- FALLBACK AVATAR LOGO -->
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border shadow-sm" style="width: 40px; height: 40px;">
                                <i class="bi bi-building fs-5 text-dark"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link p-0 text-danger ms-2" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-power fs-5"></i>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .fw-800 { font-weight: 800; }
    .fw-600 { font-weight: 600; }
    .transition { transition: all 0.2s ease-in-out; }
    .nav-link.active { color: #0d6efd !important; position: relative; }
    .object-fit-cover { object-fit: cover !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('unread-badge');
    const notifLinks = document.querySelectorAll('.notif-link');

    notifLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const notifId = this.getAttribute('data-id');
            const source = this.getAttribute('data-source');
            const targetUrl = this.getAttribute('href');

            if (source === 'system') {
                e.preventDefault(); 
                fetch('api_mark_read.php?id=' + notifId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (badge) {
                                let count = parseInt(badge.innerText);
                                if (count > 1) {
                                    badge.innerText = count - 1;
                                } else {
                                    badge.remove();
                                }
                            }
                            window.location.href = targetUrl;
                        }
                    })
                    .catch(err => {
                        console.error('Error marking notif:', err);
                        window.location.href = targetUrl; 
                    });
            }
        });
    });
});
</script>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0 px-4">
                <div class="mb-3 text-danger bg-danger bg-opacity-10 d-inline-flex p-3 rounded-circle">
                    <i class="bi bi-power fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Sign Out?</h5>
                <p class="text-muted small mb-0">End your session for <strong><?= htmlspecialchars($company['company_name'] ?? 'Company') ?></strong>?</p>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-center gap-2 pb-4 pt-4">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold text-secondary border" data-bs-dismiss="modal">Cancel</button>
                <a href="../auth/logout.php" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm">Logout</a>
            </div>
        </div>
    </div>
</div>