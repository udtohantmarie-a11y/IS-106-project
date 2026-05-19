<?php
require_once '../config/db.php';
requireLogin('admin');

// Data fetching logic
$total_students     = ($res = $conn->query("SELECT COUNT(*) AS c FROM students")) ? $res->fetch_assoc()['c'] : 0;
$total_companies    = ($res = $conn->query("SELECT COUNT(*) AS c FROM companies WHERE status='approved'")) ? $res->fetch_assoc()['c'] : 0;
$pending_companies  = ($res = $conn->query("SELECT COUNT(*) AS c FROM companies WHERE status='pending'")) ? $res->fetch_assoc()['c'] : 0;
$total_listings     = ($res = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE status='open'")) ? $res->fetch_assoc()['c'] : 0;
$total_applications = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications")) ? $res->fetch_assoc()['c'] : 0;
$total_accepted     = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status='accepted'")) ? $res->fetch_assoc()['c'] : 0;

// Fetch Recent Students with Profile Photos
$recent_students = $conn->query("
    SELECT s.full_name, s.course, s.year_level, s.profile_photo, u.created_at 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    ORDER BY u.created_at DESC LIMIT 5
");

// Fetch Pending Companies
$pending_list = $conn->query("
    SELECT c.*, u.email, u.created_at 
    FROM companies c 
    JOIN users u ON u.id = c.user_id 
    WHERE c.status = 'pending' 
    ORDER BY u.created_at DESC LIMIT 5
");

$page_title = "Admin Dashboard";
$page_subtitle = "Welcome back! Here's what's happening today.";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .avatar-sm {
        width: 35px;
        height: 35px;
        object-fit: cover;
        border-radius: 50%;
    }
    .avatar-placeholder-sm {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 0.75rem;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php include 'components/navbar.php'; ?>

        <!-- Alert Banner para sa Pending Companies -->
        <?php if ($pending_companies > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 rounded-4">
            <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
            <div>
                <strong><?= $pending_companies ?> company registration(s)</strong> are waiting for your approval.
                <a href="approve_companies.php" class="alert-link ms-2">Review now <i class="fas fa-arrow-right small"></i></a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mb-2">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $total_students ?></h4>
                    <span class="text-muted small">Students</span>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-success bg-opacity-10 text-success mb-2">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $total_companies ?></h4>
                    <span class="text-muted small">Active Partners</span>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $pending_companies ?></h4>
                    <span class="text-muted small">Pending</span>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-info bg-opacity-10 text-info mb-2">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $total_listings ?></h4>
                    <span class="text-muted small">Open Jobs</span>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary mb-2">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $total_applications ?></h4>
                    <span class="text-muted small">Applications</span>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card stat-card shadow-sm p-3 border-0 rounded-4 h-100">
                    <div class="icon-box bg-success bg-opacity-25 text-success mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?= $total_accepted ?></h4>
                    <span class="text-muted small">Hired</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Table: Pending Approvals -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Pending Approvals</h5>
                    <a href="approve_companies.php" class="btn btn-sm btn-outline-primary border-0 fw-bold">View All</a>
                </div>
                <div class="card table-card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th>Company</th>
                                    <th>Industry</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_list->num_rows > 0): ?>
                                    <?php while ($row = $pending_list->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-placeholder-sm bg-dark me-2">
                                                    <i class="fas fa-building small"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small"><?= htmlspecialchars($row['company_name']) ?></div>
                                                    <div class="text-muted small" style="font-size: 0.65rem;"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark fw-normal border" style="font-size: 0.65rem;"><?= htmlspecialchars($row['industry']) ?></span></td>
                                        <td class="text-center">
                                            <a href="approve_companies.php" class="btn btn-sm btn-primary px-3 rounded-pill fw-bold" style="font-size: 0.7rem;">Review</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">No pending approvals <i class="fas fa-check-circle text-success ms-1"></i></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Table: Recent Students -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">New Students</h5>
                    <a href="students.php" class="btn btn-sm btn-outline-primary border-0 fw-bold">Directory</a>
                </div>
                <div class="card table-card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th class="text-end pe-4">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_students->num_rows > 0): ?>
                                    <?php while ($row = $recent_students->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <?php if (!empty($row['profile_photo'])): ?>
                                                        <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" class="avatar-sm" alt="Profile">
                                                    <?php else: ?>
                                                        <div class="avatar-placeholder-sm bg-info bg-opacity-75">
                                                            <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="fw-bold small"><?= htmlspecialchars($row['full_name']) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['course']) ?></div>
                                            <div class="text-muted" style="font-size: 0.65rem;"><?= $row['year_level'] ?></div>
                                        </td>
                                        <td class="text-end pe-4 text-muted small"><?= date('M d', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>