<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// 1. Get student info for components and identification
$student = $conn->query("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
")->fetch_assoc();

$student_id = $student['id'];

// 2. Filter by status
$filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$where = "a.student_id = '$student_id'";
if ($filter) $where .= " AND a.status = '$filter'";

// 3. Fetch applications with related job and company info (Included c.profile_photo)
$applications = $conn->query("
    SELECT a.*, j.title, j.type, j.location, j.deadline, c.company_name, c.profile_photo AS company_logo
    FROM applications a
    JOIN job_listings j ON j.id = a.job_id
    JOIN companies c ON c.id = j.company_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

// 4. Calculate counts for filter pills
$counts = [];
$status_options = ['pending', 'viewed', 'accepted', 'rejected'];
foreach ($status_options as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE student_id='$student_id' AND status='$s'")->fetch_assoc()['c'];
}
$total_count = array_sum($counts);

// 5. Component Variables
$page_title = "My Applications";
$current_page = "applications";
$header_title = "Application Tracking";

// 6. Include separated components
include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .company-logo-tracking {
        width: 35px;
        height: 35px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #eee;
    }
    .logo-placeholder-tracking {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        background-color: #e9ecef;
        color: #adb5bd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    .deadline-warning { color: #dc3545; font-weight: 700; }
    .nav-pills .nav-link.active { background-color: #0d6efd !important; }
    .nav-link { color: #6c757d; font-weight: 600; border-radius: 50px !important; }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Filter Pills Section -->
    <div class="nav nav-pills mb-4 overflow-auto flex-nowrap pb-2">
        <a href="my_applications.php" class="nav-link <?= !$filter ? 'active text-white' : 'bg-white border' ?> me-2 shadow-sm">
            All <span class="badge <?= !$filter ? 'bg-white text-primary' : 'bg-light text-dark' ?> ms-1"><?= $total_count ?></span>
        </a>
        <?php foreach ($status_options as $s): ?>
            <a href="?status=<?= $s ?>" class="nav-link <?= $filter === $s ? 'active text-white' : 'bg-white border' ?> me-2 shadow-sm text-capitalize">
                <?= $s ?> <span class="badge <?= $filter === $s ? 'bg-white text-primary' : 'bg-light text-dark' ?> ms-1"><?= $counts[$s] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Applications Table Card -->
    <div class="card overflow-hidden border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small">
                        <th class="ps-4 py-3 border-0">JOB POSITION</th>
                        <th class="py-3 border-0">COMPANY</th>
                        <th class="py-3 border-0">TYPE</th>
                        <th class="py-3 border-0">DEADLINE</th>
                        <th class="py-3 border-0">STATUS</th>
                        <th class="py-3 border-0 text-end pe-4">APPLIED ON</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($applications->num_rows > 0): ?>
                        <?php while ($app = $applications->fetch_assoc()): 
                            $days_left = $app['deadline'] ? (strtotime($app['deadline']) - time()) / 86400 : null;
                            
                            $status_class = match($app['status']) {
                                'accepted' => 'bg-success-subtle text-success border border-success border-opacity-25',
                                'rejected' => 'bg-danger-subtle text-danger border border-danger border-opacity-25',
                                'viewed'   => 'bg-info-subtle text-info border border-info border-opacity-25',
                                'pending'  => 'bg-warning-subtle text-warning border border-warning border-opacity-25',
                                default    => 'bg-secondary-subtle text-secondary'
                            };
                        ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($app['title']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($app['location']) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Company Logo Logic -->
                                        <?php if (!empty($app['company_logo'])): ?>
                                            <img src="../uploads/company_logos/<?= htmlspecialchars($app['company_logo']) ?>" class="company-logo-tracking" alt="Logo">
                                        <?php else: ?>
                                            <div class="logo-placeholder-tracking">
                                                <i class="bi bi-building"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="fw-semibold text-muted small">
                                            <?= htmlspecialchars($app['company_name']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= ($app['type'] == 'internship') ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.65rem;">
                                        <?= strtoupper($app['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($app['deadline']): ?>
                                        <span class="<?= ($days_left !== null && $days_left <= 7 && $days_left >= 0) ? 'deadline-warning' : 'text-muted' ?> small fw-medium">
                                            <?= date('M d, Y', strtotime($app['deadline'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50 small italic">No deadline</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge <?= $status_class ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                        <?php if ($app['status'] == 'rejected' && !empty($app['rejection_note'])): ?>
                                            <i class="bi bi-info-circle ms-2 text-danger" 
                                               data-bs-toggle="tooltip" 
                                               title="Note: <?= htmlspecialchars($app['rejection_note']) ?>" 
                                               style="cursor: help;"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-muted small text-end pe-4">
                                    <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-folder-x display-1 text-light"></i>
                                    <p class="text-muted mt-3 mb-0">No records found for the selected status.</p>
                                    <a href="browse_jobs.php" class="btn btn-primary btn-sm rounded-pill px-4 mt-3 shadow-sm fw-bold">Explore More Jobs</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Sidebar Logic
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');

    function toggleSidebar() { 
        sidebar.classList.toggle('show'); 
        overlay.classList.toggle('show'); 
    }

    if(openBtn) openBtn.addEventListener('click', toggleSidebar);
    if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
</script>
</body>
</html>