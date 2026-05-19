<?php
$current_page_file = basename($_SERVER['PHP_SELF']);

// Kunin ang count ng unread notifications para sa badge
$user_id = $_SESSION['user_id'];
$n_count_query = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
$unread_count = $n_count_query->fetch_assoc()['total'] ?? 0;

// Kunin ang count ng saved jobs para sa badge
$w_count_query = $conn->query("SELECT COUNT(*) AS total FROM wishlist WHERE user_id = '$user_id'");
$wish_count = $w_count_query->fetch_assoc()['total'] ?? 0;
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Updated Branding Logo - Consistent across all portals -->
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <span class="fw-bold">
            <i class="bi bi-intersect me-2 text-primary"></i>JobBoard<span class="text-primary">.</span>
        </span>
        <button class="btn d-lg-none p-0 border-0 shadow-none" id="closeSidebar">
            <i class="bi bi-x-lg text-muted"></i>
        </button>
    </div>
    
    <div class="mt-4">
        <div class="nav-label small text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Main Menu</div>
        
        <a href="dashboard.php" class="nav-link <?= $current_page_file == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        
        <a href="browse_jobs.php" class="nav-link <?= $current_page_file == 'browse_jobs.php' ? 'active' : '' ?>">
            <i class="bi bi-search"></i> Browse Jobs
        </a>

        <!-- NEW: Saved Jobs / Wishlist -->
        <a href="saved_jobs.php" class="nav-link <?= $current_page_file == 'saved_jobs.php' ? 'active' : '' ?>">
            <i class="bi bi-heart-fill text-danger"></i> Saved Jobs
            <?php if ($wish_count > 0): ?>
                <span class="badge bg-light text-primary rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $wish_count ?></span>
            <?php endif; ?>
        </a>

        <a href="my_applications.php" class="nav-link <?= $current_page_file == 'my_applications.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Applications
        </a>
        
        <a href="notifications.php" class="nav-link <?= $current_page_file == 'notifications.php' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> Notifications
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-label small text-uppercase text-muted fw-bold px-3 mt-4 mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">Account</div>
        
        <a href="profile.php" class="nav-link <?= $current_page_file == 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person"></i> My Profile
        </a>
        
        <hr class="mx-3 my-4 text-muted border-0 bg-secondary" style="height: 1px; opacity: 0.1;">
        
        <a href="#" class="nav-link text-danger logout-trigger" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</aside>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="logoutModalLabel">Sign Out?</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-door-open fs-2"></i>
                </div>
                <p class="text-muted small px-3 mb-0">Are you sure you want to log out of the student portal?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold small me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="../auth/logout.php" class="btn btn-danger px-4 rounded-pill fw-bold small shadow-sm">Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('closeSidebar');
        const logoutModalEl = document.getElementById('logoutModal');

        if(closeBtn && sidebar) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('show');
                if(overlay) overlay.classList.remove('show');
            });
        }

        if (logoutModalEl) {
            logoutModalEl.addEventListener('show.bs.modal', function () {
                if (window.innerWidth < 992 && sidebar) {
                    sidebar.classList.remove('show');
                    if(overlay) overlay.classList.remove('show');
                }
            });
        }
    });
</script>