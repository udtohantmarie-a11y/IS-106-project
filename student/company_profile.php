<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];
$company_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Fetch Company Details (Must be approved - Included profile_photo)
$company_query = $conn->query("SELECT * FROM companies WHERE id = '$company_id' AND status = 'approved'");

if ($company_query->num_rows == 0) {
    header("Location: browse_jobs.php");
    exit();
}

$company = $company_query->fetch_assoc();

// 2. Get Average Rating and Total Reviews
$rating_stats = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM company_reviews WHERE company_id = '$company_id'")->fetch_assoc();
$avg_rating = round($rating_stats['avg_rating'], 1);
$total_reviews = $rating_stats['total_reviews'];

// 3. Fetch all reviews/feedback (JOIN students to get verified status and profile photo)
$reviews_query = $conn->query("
    SELECT r.*, s.full_name, s.is_verified, s.profile_photo 
    FROM company_reviews r 
    JOIN students s ON r.student_id = s.id 
    WHERE r.company_id = '$company_id' 
    ORDER BY r.created_at DESC
");

// 4. Fetch Active Job Listings
$jobs_query = $conn->query("
    SELECT * FROM job_listings 
    WHERE company_id = '$company_id' AND status = 'open' AND slots > 0
    ORDER BY created_at DESC
");

// 5. Get Student Info for current session
$student = $conn->query("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
")->fetch_assoc();

$page_title = $company['company_name'] . " Profile";
$current_page = "browse";
$header_title = "Company Details";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
    .transition { transition: all 0.3s ease; }
    .hover-shadow:hover {
        border-color: #0d6efd !important;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.12);
        transform: translateY(-4px);
    }
    .main-content { min-height: 100vh; padding-bottom: 3rem; }
    .star-rating-display { color: #ffc107; }
    
    /* Company Logo Styles */
    .profile-logo-img {
        width: 85px;
        height: 85px;
        object-fit: cover;
        border-radius: 20px;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .profile-logo-placeholder {
        width: 85px;
        height: 85px;
        border-radius: 20px;
        background: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }

    /* Reviewer Avatar Styles */
    .reviewer-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .reviewer-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #6c757d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .verified-badge {
        font-size: 0.6rem;
        padding: 2px 8px;
        border-radius: 50px;
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        border: 1px solid #bae6fd;
        text-transform: uppercase;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-0">
        <!-- ALERT HANDLER -->
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><strong>Thank you!</strong> Your review has been posted successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif($_GET['status'] == 'duplicate'): ?>
                <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Already submitted!</strong> You've recently reviewed this company.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <a href="browse_jobs.php" class="btn btn-link text-decoration-none p-0 mb-4 text-muted small fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to listings
        </a>

        <div class="row g-4">
            <!-- LEFT COLUMN: Profile & Reviews -->
            <div class="col-lg-8">
                <!-- Company Header Card -->
                <div class="card border-0 shadow-sm p-4 mb-4 rounded-4 bg-white">
                    <div class="d-flex flex-column flex-md-row align-items-center mb-4 text-center text-md-start">
                        <div class="me-md-4 mb-3 mb-md-0">
                            <?php if (!empty($company['profile_photo'])): ?>
                                <img src="../uploads/company_logos/<?= htmlspecialchars($company['profile_photo']) ?>" class="profile-logo-img" alt="Company Logo">
                            <?php else: ?>
                                <div class="profile-logo-placeholder">
                                    <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="fw-800 text-dark mb-1"><?= htmlspecialchars($company['company_name']) ?></h2>
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                                <span class="star-rating-display fw-bold"><i class="bi bi-star-fill"></i> <?= $avg_rating ?: '0.0' ?></span>
                                <span class="text-muted small">(<?= $total_reviews ?> student reviews)</span>
                                <span class="mx-1 text-silver">|</span>
                                <span class="text-primary fw-bold small text-uppercase"><?= htmlspecialchars($company['industry']) ?></span>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">About the Company</h5>
                    <div class="text-muted lh-lg mb-4">
                        <?= !empty($company['about']) ? nl2br(htmlspecialchars($company['about'])) : "This company hasn't provided a detailed description yet." ?>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Office Location</h5>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-geo-alt-fill text-danger me-2"></i> <?= htmlspecialchars($company['address']) ?>
                    </p>
                </div>

                <!-- Feedbacks Card -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                        <h5 class="fw-bold mb-0">Student Feedbacks</h5>
                        <button class="btn btn-sm btn-primary rounded-pill fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                            Write Review
                        </button>
                    </div>

                    <div class="reviews-list">
                        <?php if ($reviews_query->num_rows > 0): ?>
                            <?php while ($rev = $reviews_query->fetch_assoc()): ?>
                                <div class="review-item mb-4 pb-3 border-bottom transition">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <!-- Reviewer Photo -->
                                            <div class="me-2">
                                                <?php if (!empty($rev['profile_photo'])): ?>
                                                    <img src="../uploads/profile_photos/<?= htmlspecialchars($rev['profile_photo']) ?>" class="reviewer-avatar" alt="Student">
                                                <?php else: ?>
                                                    <div class="reviewer-placeholder">
                                                        <?= strtoupper(substr($rev['full_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="fw-bold text-dark small"><?= htmlspecialchars($rev['full_name']) ?></span>
                                                    <?php if($rev['is_verified'] == 1): ?>
                                                        <span class="verified-badge shadow-sm" title="Verified Account">
                                                            <i class="bi bi-patch-check-fill"></i> VERIFIED
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></small>
                                    </div>
                                    <div class="star-rating-display mb-2" style="font-size: 0.8rem;">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star text-muted opacity-25' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-muted small mb-0 lh-base italic">
                                        "<?= nl2br(htmlspecialchars($rev['comment'])) ?>"
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-left-dots text-light display-1"></i>
                                <p class="text-muted small mt-2">No feedbacks yet. Be the first to share your experience!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Jobs & Contact -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 mb-4 rounded-4 bg-white">
                    <h5 class="fw-bold mb-4 small text-uppercase border-bottom pb-2">Active Openings</h5>
                    <?php if ($jobs_query->num_rows > 0): ?>
                        <?php while ($job = $jobs_query->fetch_assoc()): ?>
                            <div class="mb-3 p-3 border rounded-4 hover-shadow transition bg-light bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.6rem;">
                                        <?= strtoupper($job['type']) ?>
                                    </span>
                                    <span class="text-success small fw-bold" style="font-size: 0.7rem;">
                                        <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars($job['salary'] ?: 'Unpaid') ?>
                                    </span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark small"><?= htmlspecialchars($job['title']) ?></h6>
                                <p class="text-muted mb-3" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($job['location']) ?></p>
                                <a href="job_details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-bold" style="font-size: 0.75rem;">
                                    View Details
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 small">No active listings available.</p>
                    <?php endif; ?>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-4 text-uppercase small text-muted border-bottom pb-2">Inquiries</h6>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1">Contact Person</label>
                        <div class="fw-bold text-dark small"><i class="bi bi-person-badge me-2 text-primary"></i><?= htmlspecialchars($company['contact_person']) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1">Phone Number</label>
                        <div class="fw-bold text-dark small"><i class="bi bi-telephone me-2 text-primary"></i><?= htmlspecialchars($company['phone']) ?></div>
                    </div>
                    <div>
                        <label class="small text-muted d-block mb-1">Website</label>
                        <?php if(!empty($company['website'])): ?>
                            <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" class="fw-bold text-primary text-decoration-none small text-truncate d-block"><i class="bi bi-globe me-2"></i><?= htmlspecialchars($company['website']) ?></a>
                        <?php else: ?>
                            <span class="text-muted small italic">No website listed.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ADD REVIEW MODAL -->
<div class="modal fade" id="addReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="api_review.php" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Rate Your Experience</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4 text-dark">
                    <input type="hidden" name="company_id" value="<?= $company_id ?>">
                    
                    <div class="mb-4 text-center">
                        <label class="form-label d-block text-muted small fw-bold text-uppercase">Rating</label>
                        <select name="rating" class="form-select border-2 shadow-none rounded-pill text-center fw-bold" required>
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Great)</option>
                            <option value="3" selected>⭐⭐⭐ (Average)</option>
                            <option value="2">⭐⭐ (Poor)</option>
                            <option value="1">⭐ (Terrible)</option>
                        </select>
                    </div>

                    <div class="mb-0 text-start">
                        <label class="form-label text-muted small fw-bold text-uppercase">Your Feedback</label>
                        <textarea name="comment" class="form-control rounded-4 shadow-none border-2" rows="4" placeholder="How was the environment? The tasks?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>