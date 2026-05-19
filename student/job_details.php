<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- START NOTIFICATION MARK AS READ LOGIC ---
if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = '$notif_id' AND user_id = '$user_id'");
}
// --- END NOTIFICATION LOGIC ---

// 1. Get Job & Company Details (Included salary and profile_photo in SELECT)
$job_query = $conn->query("
    SELECT j.*, c.id AS company_id, c.company_name, c.industry, c.address, c.website, c.profile_photo AS company_logo
    FROM job_listings j
    JOIN companies c ON j.company_id = c.id
    WHERE j.id = '$job_id'
");

if ($job_query->num_rows == 0) {
    header("Location: browse_jobs.php");
    exit();
}

$job = $job_query->fetch_assoc();

// 2. Get student info
$student = $conn->query("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
")->fetch_assoc();

$student_id = $student['id'];

// 3. Check if the student has already applied
$check_app = $conn->query("SELECT id FROM applications WHERE student_id = '$student_id' AND job_id = '$job_id'");
$has_applied = ($check_app->num_rows > 0);

// 4. Component Variables
$page_title = $job['title'] . " Details";
$current_page = "browse"; 
$header_title = "Opportunity Details";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .company-link { transition: all 0.2s ease; }
    .company-link:hover { color: #0d6efd !important; text-decoration: underline !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
    .salary-tag { background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); }
    
    /* Company Logo Styles */
    .detail-logo-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 16px;
        border: 1px solid #eee;
        background-color: #fff;
    }
    .detail-logo-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-0">
        <a href="browse_jobs.php" class="btn btn-link text-decoration-none p-0 mb-4 text-muted small fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to listings
        </a>

        <!-- Job Header Section -->
        <div class="card border-0 shadow-sm p-4 mb-4" style="border-left: 5px solid #0d6efd !important; border-radius: 20px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <!-- Company Logo -->
                        <div class="me-3">
                            <?php if (!empty($job['company_logo'])): ?>
                                <img src="../uploads/company_logos/<?= htmlspecialchars($job['company_logo']) ?>" class="detail-logo-img shadow-sm" alt="Logo">
                            <?php else: ?>
                                <div class="detail-logo-placeholder shadow-sm">
                                    <?= strtoupper(substr($job['company_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="d-flex flex-wrap gap-2 mb-1">
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    <?= strtoupper($job['type']) ?>
                                </span>
                                <span class="badge salary-tag px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    <i class="bi bi-cash-stack me-1"></i> <?= htmlspecialchars($job['salary'] ?: 'Unpaid') ?>
                                </span>
                            </div>
                            <h1 class="fw-800 text-dark mb-0 h3"><?= htmlspecialchars($job['title']) ?></h1>
                        </div>
                    </div>
                    
                    <p class="text-muted mb-0 ms-0 ms-md-5 ps-0 ps-md-4">
                        <i class="bi bi-building me-1"></i> 
                        <a href="company_profile.php?id=<?= $job['company_id'] ?>" class="text-decoration-none text-muted company-link fw-bold">
                            <?= htmlspecialchars($job['company_name']) ?>
                        </a>
                        <span class="mx-2 text-silver">|</span> 
                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <?php if ($has_applied): ?>
                        <button class="btn btn-success btn-lg px-5 rounded-pill disabled w-100 shadow-sm fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i> Applied
                        </button>
                    <?php elseif ($job['slots'] <= 0 || $job['status'] == 'closed'): ?>
                        <button class="btn btn-secondary btn-lg px-5 rounded-pill disabled w-100 shadow-sm fw-bold">
                            No Slots Available
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill w-100 shadow-sm fw-800" data-bs-toggle="modal" data-bs-target="#applyModal">
                            Apply for this Role
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card p-4 mb-4 border-0 shadow-sm rounded-4">
                    <h5 class="fw-800 text-dark mb-4 border-bottom pb-2">Description</h5>
                    <div class="text-muted lh-lg">
                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                    </div>
                    
                    <h5 class="fw-800 text-dark mt-5 mb-4 border-bottom pb-2">Requirements</h5>
                    <div class="text-muted lh-lg">
                        <?= nl2br(htmlspecialchars($job['requirements'] ?: 'No specific requirements listed.')) ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <div class="card p-4 mb-4 border-0 bg-white shadow-sm rounded-4">
                    <h6 class="fw-bold mb-4 text-uppercase small text-muted letter-spacing-1">Information Overview</h6>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3 text-success border border-success border-opacity-10">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Compensation</div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($job['salary'] ?: 'Unpaid / Not Specified') ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                            <i class="bi bi-calendar-event fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Application Deadline</div>
                            <div class="fw-bold text-dark"><?= $job['deadline'] ? date('F d, Y', strtotime($job['deadline'])) : 'No Deadline' ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-warning bg-opacity-10 p-2 rounded-3 me-3 text-warning">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Available Slots</div>
                            <div class="fw-bold text-dark"><?= $job['slots'] ?> positions remaining</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="bg-info bg-opacity-10 p-2 rounded-3 me-3 text-info">
                            <i class="bi bi-tags fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Industry</div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($job['industry']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 bg-light border-0 shadow-sm rounded-4">
                    <h6 class="fw-bold mb-2 small text-dark"><i class="bi bi-info-circle me-2"></i>Submission Notice</h6>
                    <p class="text-muted small mb-0">
                        Make sure your resume is up-to-date before applying. You can also upload a specific resume for this role in the application window.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Apply Modal -->
<div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-800" id="applyModalLabel">Submit Application</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_application.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body py-4 text-dark">
                    <input type="hidden" name="job_id" value="<?= $job_id ?>">
                    
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 d-inline-flex p-3 rounded-circle mb-3">
                            <i class="bi bi-file-earmark-arrow-up fs-2 text-primary"></i>
                        </div>
                        <p class="text-muted small mt-1 px-3">Position: <strong class="text-dark"><?= htmlspecialchars($job['title']) ?></strong></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">COVER LETTER (OPTIONAL)</label>
                        <textarea name="cover_letter" class="form-control rounded-3 shadow-none border-2" rows="4" placeholder="Tell the employer why you are a good fit..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Resume / CV (PDF ONLY)</label>
                        <input type="file" name="resume" class="form-control rounded-3 shadow-none border-2" accept=".pdf">
                        <div class="form-text small mt-2">
                            <?php if ($student['resume_path']): ?>
                                <i class="bi bi-info-circle me-1 text-primary"></i> You already have a resume on file. Upload a new one only if you want to update it for this job.
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle text-danger me-1"></i> No resume found. <strong>Please upload one to continue.</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-light px-4 rounded-pill fw-bold text-secondary border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-800 shadow-sm">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle logic
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
</script>
</body>
</html>