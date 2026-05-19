<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$login_error = $_SESSION['login_error'] ?? '';
$reg_error   = $_SESSION['reg_error'] ?? '';
$reg_success = $_SESSION['reg_success'] ?? '';
$success_type = $_SESSION['success_type'] ?? ''; 

// Clean sessions after retrieval
unset($_SESSION['login_error'], $_SESSION['reg_error'], $_SESSION['reg_success'], $_SESSION['success_type']);

$view = $_GET['view'] ?? '';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   redirect('admin/dashboard.php');
    if ($role === 'company') redirect('company/dashboard.php');
    if ($role === 'student') redirect('student/dashboard.php');
}

// Counts
$total_jobs      = ($res = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE status='open'")) ? $res->fetch_assoc()['c'] : 0;
$total_companies = ($res = $conn->query("SELECT COUNT(*) AS c FROM companies WHERE status='approved'")) ? $res->fetch_assoc()['c'] : 0;
$total_students  = ($res = $conn->query("SELECT COUNT(*) AS c FROM students")) ? $res->fetch_assoc()['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobBoard - Connecting Talent with Opportunity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0d6efd;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --glass: rgba(255, 255, 255, 0.9);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--light-bg);
            color: var(--dark);
        }

        .navbar {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            color: var(--dark) !important;
        }

        .hero-section {
            padding: 140px 0 100px;
            background: radial-gradient(circle at top right, rgba(13, 110, 253, 0.05), transparent),
                        radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.05), transparent);
        }
        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -2px;
            margin-bottom: 1.5rem;
        }
        .text-gradient {
            background: linear-gradient(45deg, #0d6efd, #00d2ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        }
        .btn-outline {
            border: 2px solid #e2e8f0;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            color: var(--dark);
            transition: all 0.3s;
        }
        .btn-outline:hover {
            background: #fff;
            border-color: var(--primary);
            color: var(--primary);
        }

        .stats-container {
            background: #fff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.03);
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }
        .stat-val { font-size: 2.5rem; font-weight: 800; color: var(--dark); }
        .stat-lab { color: #64748b; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; }

        .feature-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }
        .icon-box {
            width: 60px;
            height: 60px;
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 25px;
        }

        .modal-content { border-radius: 24px; border: none; padding: 10px; }
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .form-control:focus, .form-select:focus { background: #fff; box-shadow: none; border-color: var(--primary); }

        .resume-note {
            background: #eef6ff;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-intersect text-primary me-2"></i>JobBoard
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link fw-600 text-dark px-3" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Sign In</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-primary shadow-sm" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold mb-3">🚀 Launching Careers</span>
                <h1 class="hero-title text-dark">Find the perfect <span class="text-gradient">Internship</span> for your future.</h1>
                <p class="lead text-muted mb-5 pe-lg-5">The smartest way for college students to connect with world-class companies and kickstart their professional journey.</p>
                <div class="d-flex flex-wrap gap-3">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">I'm a Student</button>
                    <button class="btn btn-outline btn-lg" data-bs-toggle="modal" data-bs-target="#companyRegisterModal">I'm a Company</button>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <img src="https://img.freepik.com/free-vector/job-hunt-concept-illustration_114360-812.jpg" class="img-fluid rounded-4" alt="Job Search">
            </div>
        </div>
    </div>
</header>

<!-- Stats -->
<div class="container">
    <div class="stats-container">
        <div class="row text-center gy-4">
            <div class="col-md-4">
                <div class="stat-val"><?= number_format($total_jobs) ?>+</div>
                <div class="stat-lab">Live Openings</div>
            </div>
            <div class="col-md-4 border-start border-end border-light">
                <div class="stat-val"><?= number_format($total_companies) ?>+</div>
                <div class="stat-lab">Partner Companies</div>
            </div>
            <div class="col-md-4">
                <div class="stat-val"><?= number_format($total_students) ?>+</div>
                <div class="stat-lab">Active Students</div>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<section class="py-5 mt-5">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-800 h1">Everything you need</h2>
            <p class="text-muted">A platform built for the modern recruitment era.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="icon-box"><i class="bi bi-lightning-charge"></i></div>
                    <h4 class="fw-bold">Fast Applications</h4>
                    <p class="text-muted">Apply to multiple internships with just one click using your stored digital resume.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="icon-box" style="background: rgba(52, 168, 83, 0.1); color: #34a853;"><i class="bi bi-shield-check"></i></div>
                    <h4 class="fw-bold">Verified Partners</h4>
                    <p class="text-muted">Every company is manually verified by the school administrator for student safety.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="icon-box" style="background: rgba(249, 171, 0, 0.1); color: #f9ab00;"><i class="bi bi-graph-up-arrow"></i></div>
                    <h4 class="fw-bold">Status Tracking</h4>
                    <p class="text-muted">Know exactly when your application is viewed, accepted, or moved forward in real-time.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-5 border-top bg-white">
    <div class="container text-center">
        <div class="mb-4">
            <a class="navbar-brand" href="#"><i class="bi bi-intersect text-primary me-2"></i>JobBoard</a>
        </div>
        <p class="text-muted small mb-0">&copy; <?= date('Y') ?> JobBoard Academic Portal. All rights reserved.</p>
    </div>
</footer>

<!-- MODALS (LOGIN) -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-body p-5">
                <div class="text-center mb-4">
                    <div class="icon-box mx-auto mb-3"><i class="bi bi-person-lock"></i></div>
                    <h3 class="fw-800">Welcome Back</h3>
                    <p class="text-muted small">Please enter your credentials to continue</p>
                </div>
                <?php if ($login_error): ?>
                    <div class="alert alert-danger border-0 small mb-4 py-2 text-center"> <?= htmlspecialchars($login_error) ?> </div>
                <?php endif; ?>
                <form action="auth/login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">USERNAME</label>
                        <input type="text" name="username" class="form-control shadow-none" placeholder="your_username" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">PASSWORD</label>
                        <input type="password" name="password" class="form-control shadow-none" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 shadow-sm">Sign In to Dashboard</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODALS (STUDENT REGISTER) -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-5">
                <h3 class="fw-800 mb-1">Student Registration</h3>
                <p class="text-muted small mb-4">Join our talent community today.</p>

                <?php if ($reg_success && $success_type === 'student'): ?>
                    <div class="alert alert-success border-0 small py-3 mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Successfully registered!</strong> You can log in now.
                    </div>
                <?php endif; ?>
                
                <?php if ($reg_error && $view !== 'register_company'): ?>
                    <div class="alert alert-danger border-0 small py-2"><?= htmlspecialchars($reg_error) ?></div>
                <?php endif; ?>

                <form action="auth/register_student.php" method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-muted">FULL NAME</label>
                        <input type="text" name="full_name" class="form-control shadow-none" placeholder="e.g., Juan Dela Cruz" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">USERNAME</label>
                        <input type="text" name="username" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">PHONE</label>
                        <input type="text" name="phone" class="form-control shadow-none" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-muted">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold small text-muted">COURSE</label>
                        <input type="text" name="course" class="form-control shadow-none" placeholder="e.g., BS Information Systems" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold small text-muted">YEAR LEVEL</label>
                        <select name="year_level" class="form-select shadow-none" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <!-- RESUME CLARIFICATION SECTION -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">INITIAL RESUME (Optional)</label>
                        <input type="file" name="resume" class="form-control shadow-none" accept=".pdf,.doc,.docx">
                        <div class="resume-note mt-2">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            <strong>Note:</strong> Companies will see this resume when you apply. You can always update or replace this in your <strong>Profile Settings</strong> after you log in.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">PASSWORD</label>
                        <input type="password" name="password" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" class="form-control shadow-none" required>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">Create Student Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODALS (COMPANY REGISTER) -->
<div class="modal fade" id="companyRegisterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-body p-5 text-dark">
                <h3 class="fw-800 mb-1">Company Partner Program</h3>
                <p class="text-muted small mb-4">Recruit the best students for your internship programs.</p>

                <?php if ($reg_success && $success_type === 'company'): ?>
                    <div class="alert alert-info border-0 small py-3 mb-4">
                        <i class="bi bi-clock-history me-2"></i>
                        <strong>Request Submitted!</strong> Your account is waiting for admin approval.
                    </div>
                <?php endif; ?>

                <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 mb-4">
                    <small class="text-warning-emphasis fw-bold"><i class="bi bi-info-circle me-1"></i> ADMIN REVIEW:</small>
                    <p class="small text-muted mb-0">Your account will require manual approval by the school administrator before you can post job listings.</p>
                </div>

                <form action="auth/register_company.php" method="POST" class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">OFFICIAL COMPANY NAME</label>
                        <input type="text" name="company_name" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">INDUSTRY</label>
                        <input type="text" name="industry" class="form-control shadow-none" placeholder="e.g. IT, Banking, Manufacturing" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CONTACT PERSON</label>
                        <input type="text" name="contact_person" class="form-control shadow-none" placeholder="HR Manager Name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">HEADQUARTERS ADDRESS</label>
                        <textarea name="address" class="form-control shadow-none" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CORPORATE USERNAME</label>
                        <input type="text" name="username" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">BUSINESS PHONE</label>
                        <input type="text" name="phone" class="form-control shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">BUSINESS EMAIL</label>
                        <input type="email" name="email" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">SET PASSWORD</label>
                        <input type="password" name="password" class="form-control shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" class="form-control shadow-none" required>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-pill shadow-sm">Submit Partner Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        var regModal = new bootstrap.Modal(document.getElementById('registerModal'));
        var compModal = new bootstrap.Modal(document.getElementById('companyRegisterModal'));

        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');

        // Show login modal if there's an error
        <?php if ($login_error): ?> 
            loginModal.show(); 
        <?php endif; ?>

        // Show modals for errors or success
        <?php if ($reg_error || $reg_success): ?>
            <?php if ($success_type === 'company' || $view === 'register_company'): ?>
                compModal.show();
            <?php else: ?>
                regModal.show();
            <?php endif; ?>
        <?php endif; ?>

        // Default direct view trigger
        if (view === 'register_company' && !'<?php echo $reg_success; ?>') {
            compModal.show();
        }
    });
</script>
</body>
</html>