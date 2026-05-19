<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// 1. Get student info (Including is_verified status)
$student = $conn->query("
    SELECT s.*, u.username, u.email 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
")->fetch_assoc();

$student_id = $student['id'];

// 2. Data Logic for Stats
$total_applied  = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE student_id = '$student_id'")->fetch_assoc()['c'];
$total_pending  = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE student_id = '$student_id' AND status = 'pending'")->fetch_assoc()['c'];
$total_accepted = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE student_id = '$student_id' AND status = 'accepted'")->fetch_assoc()['c'];
$open_jobs      = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE status = 'open' AND slots > 0")->fetch_assoc()['c'];

// 3. Recent Activity Query (Included c.profile_photo)
$recent = $conn->query("
    SELECT a.*, j.title, c.company_name, c.profile_photo AS company_logo
    FROM applications a 
    JOIN job_listings j ON j.id = a.job_id 
    JOIN companies c ON c.id = j.company_id 
    WHERE a.student_id = '$student_id' 
    ORDER BY a.applied_at DESC 
    LIMIT 5
");

// 4. Component Variables
$page_title = "Dashboard";
$current_page = "dashboard";
$header_title = "Dashboard Overview";

// 5. Include Header and Sidebar
include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    /* HIDE SCROLLBAR GLOBALLY */
    body { 
        background-color: #f8f9fa; 
        font-family: 'Plus Jakarta Sans', sans-serif;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    body::-webkit-scrollbar { display: none; }

    .main-content { min-height: 100vh; padding-bottom: 3rem; }
    .stat-card { transition: transform 0.3s ease; border-radius: 1.2rem !important; }
    .stat-card:hover { transform: translateY(-5px); }
    
    .table-responsive { -ms-overflow-style: none; scrollbar-width: none; }
    .table-responsive::-webkit-scrollbar { display: none; }

    /* Company Logo Styling */
    .company-logo-sm {
        width: 35px;
        height: 35px;
        object-fit: cover;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 1px solid #eee;
    }
    .company-logo-placeholder {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #e9ecef;
        color: #adb5bd;
        font-size: 0.8rem;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Stats Grid -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-md-3">
                <div class="card stat-card p-3 h-100 border-0 shadow-sm">
                    <div class="stat-icon-box bg-primary bg-opacity-10 text-primary mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 45px; height: 45px;">
                        <i class="bi bi-send fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_applied ?></h3>
                    <p class="text-muted small mb-0">Applied Jobs</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card p-3 h-100 border-0 shadow-sm">
                    <div class="stat-icon-box bg-warning bg-opacity-10 text-warning mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 45px; height: 45px;">
                        <i class="bi bi-hourglass-split fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_pending ?></h3>
                    <p class="text-muted small mb-0">Pending Review</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card p-3 h-100 border-0 shadow-sm">
                    <div class="stat-icon-box bg-success bg-opacity-10 text-success mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 45px; height: 45px;">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $total_accepted ?></h3>
                    <p class="text-muted small mb-0">Accepted</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card p-3 h-100 border-0 shadow-sm">
                    <div class="stat-icon-box bg-info bg-opacity-10 text-info mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 45px; height: 45px;">
                        <i class="bi bi-briefcase fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?= $open_jobs ?></h3>
                    <p class="text-muted small mb-0">Live Openings</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Activity Table -->
            <div class="col-lg-8">
                <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Recent Activity</h5>
                        <a href="my_applications.php" class="btn btn-light btn-sm text-primary fw-bold px-3 rounded-pill">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr class="text-muted small">
                                    <th class="border-0">COMPANY</th>
                                    <th class="border-0">POSITION</th>
                                    <th class="border-0">STATUS</th>
                                    <th class="border-0 text-end pe-3">DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent->num_rows > 0): ?>
                                    <?php while ($row = $recent->fetch_assoc()): ?>
                                        <?php 
                                            $s = $row['status'];
                                            $status_class = match($s) {
                                                'accepted' => 'bg-success-subtle text-success border-success',
                                                'rejected' => 'bg-danger-subtle text-danger border-danger',
                                                'viewed'   => 'bg-info-subtle text-info border-info',
                                                'pending'  => 'bg-warning-subtle text-warning border-warning',
                                                default    => 'bg-secondary-subtle text-secondary'
                                            };
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <!-- Company Logo Logic -->
                                                    <?php if (!empty($row['company_logo'])): ?>
                                                        <img src="../uploads/company_logos/<?= htmlspecialchars($row['company_logo']) ?>" class="company-logo-sm" alt="Logo">
                                                    <?php else: ?>
                                                        <div class="company-logo-placeholder">
                                                            <i class="bi bi-building"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="fw-semibold text-dark small"><?= htmlspecialchars($row['company_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($row['title']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge <?= $status_class ?> border rounded-pill px-3 py-2 fw-bold" style="font-size: 0.65rem;">
                                                        <?= ucfirst($s) ?>
                                                    </span>
                                                    <?php if ($s == 'rejected' && !empty($row['rejection_note'])): ?>
                                                        <i class="bi bi-info-circle ms-2 text-danger" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Reason: <?= htmlspecialchars($row['rejection_note']) ?>" 
                                                           style="cursor: help;"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-muted small text-end pe-3"><?= date('M d, Y', strtotime($row['applied_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted small mb-2">You haven't applied to any jobs yet.</div>
                                            <a href="browse_jobs.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">Explore Opportunities</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Account Status & Quick Tips -->
            <div class="col-lg-4">
                <!-- Account Verification Card -->
                <div class="card p-4 mb-4 border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border-radius: 20px;">
                    <h6 class="fw-bold mb-3">Verification Status</h6>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <?php if ($student['is_verified'] == 1): ?>
                                <i class="bi bi-patch-check-fill display-5 shadow-sm"></i>
                            <?php else: ?>
                                <i class="bi bi-shield-lock display-5 opacity-50"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($student['is_verified'] == 1): ?>
                                <p class="small mb-1">Account Verified</p>
                                <span class="badge bg-white text-success rounded-pill fw-bold">Official Student</span>
                            <?php else: ?>
                                <p class="small mb-1">Verification in progress</p>
                                <a href="profile.php" class="text-white fw-bold small text-decoration-underline">Check ID Status &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Tip Card -->
                <div class="card p-4 border-0 shadow-sm bg-white rounded-4">
                    <div class="d-flex mb-3">
                        <i class="bi bi-lightbulb-fill text-warning fs-4 me-3"></i>
                        <h6 class="fw-bold mb-0 mt-1">Job Search Tip</h6>
                    </div>
                    <p class="text-muted small mb-0 lh-base">
                        Always tailor your cover letter for each specific job. Mentioning the company name and explaining how your skills match their needs increases your chances!
                    </p>
                    <hr class="my-3 opacity-10">
                    <div class="small fw-bold text-dark mb-1">Need Career Advice?</div>
                    <p class="text-muted small mb-0 lh-base">Visit our campus career center or check out online resources for BSIS students.</p>
                </div>
            </div>
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
</script>
</body>
</html>