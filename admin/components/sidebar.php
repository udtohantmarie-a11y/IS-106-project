<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <i class="bi bi-intersect me-2"></i> JobBoard<span class="text-primary">.</span>
    </div>

    <div class="nav-label">Main Menu</div>
    <div class="nav flex-column mt-2">
        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="approve_companies.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'approve_companies.php' ? 'active' : '' ?>">
            <i class="bi bi-building-check"></i> Companies
        </a>

        <!-- Student List Section -->
        <a href="students.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Students
        </a>

        <a href="manage_listings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_listings.php' ? 'active' : '' ?>">
            <i class="bi bi-list-task"></i> Job Listings
        </a>

        <!-- ID Validation Section -->
        <a href="verify_users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'verify_users.php' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> ID Validation
            <?php
            // Optional: Count pending verifications for the badge
            $pending_count_query = $conn->query("SELECT (SELECT COUNT(*) FROM students WHERE resume_path IS NOT NULL AND is_verified = 0) AS total");
            $p_count = $pending_count_query->fetch_assoc()['total'] ?? 0;
            if ($p_count > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $p_count ?></span>
            <?php endif; ?>
        </a>

        <a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
        
        <div class="nav-label mt-4">System</div>
        <!-- Logout Trigger -->
        <a href="#" class="nav-link text-danger logout-trigger" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</aside>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-800" id="logoutModalLabel">Sign Out?</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center text-dark">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-door-open fs-2"></i>
                </div>
                <p class="text-muted small px-3 mb-0">Are you sure you want to log out of the admin portal?</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold small me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="../auth/logout.php" class="btn btn-danger px-4 rounded-pill fw-bold small shadow-sm">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- CSS for Hidden Scrollable Sidebar -->
<style>
    .sidebar {
        height: 100vh;
        overflow-y: auto; /* Enable vertical scroll */
        display: flex;
        flex-direction: column;
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        -webkit-overflow-scrolling: touch;
    }

    .sidebar::-webkit-scrollbar {
        display: none;
    }

    /* Hide scrollbar for IE, Edge and Firefox */
    .sidebar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const logoutModalEl = document.getElementById('logoutModal');
        
        if (logoutModalEl) {
            /**
             * Accessibility Fix:
             * Removes focus from the button inside the modal before it hides
             * to prevent the "Blocked aria-hidden" console warning.
             */
            logoutModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && logoutModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            /**
             * Sidebar Behavior:
             * Closes the sidebar on mobile when the modal opens.
             */
            logoutModalEl.addEventListener('show.bs.modal', function () {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                if (window.innerWidth < 992 && sidebar) {
                    sidebar.classList.remove('active');
                    if(overlay) overlay.classList.remove('active');
                    document.body.style.overflow = 'auto'; // Re-enable scroll if sidebar was locking it
                }
            });

            /**
             * Return Focus:
             * Returns focus to the trigger button after closing the modal.
             */
            logoutModalEl.addEventListener('hidden.bs.modal', function () {
                const trigger = document.querySelector('.logout-trigger');
                if(trigger) trigger.focus();
            });
        }
    });
</script>