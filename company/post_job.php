<?php 
include 'components/header.php'; 
include 'components/navbar.php'; 

$company_id = $company['id'];
$company_name = $company['company_name']; 
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $title        = mysqli_real_escape_string($conn, $_POST['title']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
    $type         = mysqli_real_escape_string($conn, $_POST['type']);
    $salary       = mysqli_real_escape_string($conn, $_POST['salary']); // NEW FIELD
    $slots        = (int)$_POST['slots'];
    $location     = mysqli_real_escape_string($conn, $_POST['location']);
    $deadline     = mysqli_real_escape_string($conn, $_POST['deadline']);

    if (empty($title) || empty($description) || empty($type)) {
        $error = "Job title, description, and type are required fields.";
    } elseif ($slots < 1) {
        $error = "The number of available slots must be at least 1.";
    } else {
        // 1. Save Job Listing (Including Salary)
        $sql = "INSERT INTO job_listings (company_id, title, description, requirements, type, salary, slots, location, deadline, status, created_at)
                VALUES ('$company_id', '$title', '$description', '$requirements', '$type', '$salary', '$slots', '$location', '$deadline', 'open', NOW())";
        
        if ($conn->query($sql)) {
            $new_job_id = $conn->insert_id;

            // --- START NOTIFICATION LOGIC ---
            // 3. Get all student users
            $student_query = "SELECT id FROM users WHERE role = 'student'";
            $all_students = $conn->query($student_query);
            
            if ($all_students && $all_students->num_rows > 0) {
                $notif_message = "$company_name is hiring: $title ($type). Allowance: $salary";
                
                while ($student_user = $all_students->fetch_assoc()) {
                    $student_user_id = $student_user['id'];
                    $notif_sql = "INSERT INTO notifications (user_id, job_id, message, is_read, created_at) 
                                 VALUES ('$student_user_id', '$new_job_id', '$notif_message', 0, NOW())";
                    $conn->query($notif_sql);
                }
            }
            // --- END NOTIFICATION LOGIC ---
            
            $success = true;
        } else {
            $error = "Database Error: Unable to post job. " . $conn->error;
        }
    }
}
?>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Breadcrumb Navigation -->
            <div class="d-flex align-items-center mb-4">
                <a href="my_listings.php" class="btn btn-outline-secondary btn-sm me-3" title="Back to Listings">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="h4 fw-bold mb-0">Post a New Opening</h2>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <?php if ($success): ?>
                        <!-- Success Message State -->
                        <div class="text-center py-4">
                            <div class="display-1 text-success mb-3">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h3 class="fw-bold text-success">Listing Published Successfully!</h3>
                            <p class="text-muted">Your job posting is now live and visible to students. A notification has been sent to all registered students.</p>
                            <div class="mt-4 gap-2 d-flex justify-content-center">
                                <a href="post_job.php" class="btn btn-success px-4 rounded-pill fw-bold">Post Another Job</a>
                                <a href="my_listings.php" class="btn btn-outline-primary px-4 rounded-pill fw-bold">Manage My Listings</a>
                            </div>
                        </div>
                    <?php else: ?>
                        
                        <!-- Error Notification -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm rounded-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?= $error ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Job Posting Form -->
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Job Title *</label>
                                <input type="text" name="title" class="form-control form-control-lg border-2 shadow-none rounded-3" placeholder="e.g. Software Engineer Intern" required>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Employment Type *</label>
                                    <select name="type" class="form-select border-2 shadow-none rounded-3" required>
                                        <option value="" selected disabled>-- Select Category --</option>
                                        <option value="internship">Internship / OJT</option>
                                        <option value="job">Full-time Job</option>
                                        <option value="part-time">Part-time Job</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Monthly Allowance / Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-2 text-success"><i class="bi bi-cash-stack"></i></span>
                                        <input type="text" name="salary" class="form-control border-2 shadow-none rounded-end-3" placeholder="e.g. PHP 5,000 or Unpaid">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Available Slots *</label>
                                    <input type="number" name="slots" class="form-control border-2 shadow-none rounded-3" min="1" value="1" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Work Location</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-2 text-muted"><i class="bi bi-geo-alt"></i></span>
                                        <input type="text" name="location" class="form-control border-2 shadow-none rounded-end-3" placeholder="e.g. Talibon, Bohol or Remote">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Application Deadline</label>
                                <input type="date" name="deadline" class="form-control border-2 shadow-none rounded-3" min="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Job Description *</label>
                                <textarea name="description" class="form-control border-2 shadow-none rounded-3" rows="6" placeholder="Provide a detailed overview of the role..." required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Qualifications & Requirements</label>
                                <textarea name="requirements" class="form-control border-2 shadow-none rounded-3" rows="4" placeholder="Specific skills, programming languages, etc."></textarea>
                            </div>

                            <hr class="my-4 text-muted opacity-25">

                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="my_listings.php" class="text-decoration-none text-secondary small fw-bold">Discard Changes</a>
                                <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm rounded-pill">
                                    <i class="bi bi-send-fill me-2"></i>Publish Listing
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
    .form-control:focus, .form-select:focus { border-color: #198754; }
    .rounded-4 { border-radius: 1rem !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>