<?php
require_once '../config/db.php';
requireLogin('company');

$user_id = $_SESSION['user_id'];

// 1. Fetch Company Details based on logged-in user
$company_query = $conn->query("SELECT * FROM companies WHERE user_id = '$user_id'");
$company = $company_query->fetch_assoc();
$company_id = $company['id'];

// 2. Mark feedback notifications as read
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND message LIKE '%feedback%'");

// 3. Fetch all reviews for this company (Kasama ang is_verified status at profile photo)
$feedbacks = $conn->query("
    SELECT r.*, s.full_name, s.course, s.is_verified, s.profile_photo 
    FROM company_reviews r 
    JOIN students s ON r.student_id = s.id 
    WHERE r.company_id = '$company_id' 
    ORDER BY r.created_at DESC
");

// 4. Calculate Average Rating Summary
$stats = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM company_reviews WHERE company_id = '$company_id'")->fetch_assoc();
$avg_rating = round($stats['avg_r'], 1);
$total_reviews = $stats['total'];

// 5. Page Variables
$page_title = "Company Feedbacks";
$current_page = "feedbacks.php";

include 'components/header.php'; 
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

    .table-responsive { -ms-overflow-style: none; scrollbar-width: none; }
    .table-responsive::-webkit-scrollbar { display: none; }

    .transition { transition: all 0.2s ease-in-out; }
    .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.02) !important; }
    .rounded-4 { border-radius: 1rem !important; }
    
    /* Profile Image Style */
    .student-avatar {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .avatar-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        background: #6c757d;
        text-transform: uppercase;
    }

    /* Verified Badge Style */
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

<!-- NAVBAR -->
<?php include 'components/navbar.php'; ?>

<div class="container pb-5 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Feedbacks & Ratings</h2>
        <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
            <i class="bi bi-star-fill text-warning me-1"></i> <?= $total_reviews ?> Total Reviews
        </span>
    </div>

    <!-- Summary Dashboard Section -->
    <div class="row mb-4 g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 small fw-bold text-uppercase mb-1">Average Rating</h6>
                        <h1 class="display-4 fw-bold mb-0"><?= $avg_rating ?: '0.0' ?></h1>
                        <div class="text-warning fs-5">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                echo ($i <= floor($avg_rating)) ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-patch-check-fill display-3 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 border-start border-primary border-4">
                <h5 class="fw-bold text-dark mb-2">Feedback Insights</h5>
                <p class="text-muted small mb-0">
                    Welcome, <strong><?= htmlspecialchars($company['company_name']) ?></strong>! 
                    These reviews are submitted by students who have participated in your programs. 
                    Use these insights to enhance your workplace reputation and attract the best talent.
                </p>
            </div>
        </div>
    </div>

    <!-- Feedbacks List Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4 py-3 border-0">STUDENT</th>
                        <th class="py-3 border-0">RATING</th>
                        <th class="py-3 border-0">COMMENT</th>
                        <th class="py-3 border-0 text-end pe-4">DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
                        <?php while ($row = $feedbacks->fetch_assoc()): ?>
                            <tr class="transition">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <!-- Profile Image Display -->
                                        <div class="me-3">
                                            <?php if (!empty($row['profile_photo'])): ?>
                                                <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" 
                                                     class="student-avatar" alt="Profile">
                                            <?php else: ?>
                                                <div class="avatar-placeholder bg-info bg-opacity-75">
                                                    <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']) ?></span>
                                                <!-- VERIFIED BADGE -->
                                                <?php if($row['is_verified'] == 1): ?>
                                                    <span class="verified-badge" title="Verified Account">
                                                        <i class="bi bi-patch-check-fill"></i> Verified
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-primary small fw-semibold" style="font-size: 0.75rem;"><?= htmlspecialchars($row['course']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-warning fw-bold">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="bi <?= $i <= $row['rating'] ? 'bi-star-fill' : 'bi-star text-muted opacity-25' ?> small"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1 text-dark small"><?= $row['rating'] ?>.0</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted small lh-base py-2" style="max-width: 450px; white-space: normal;">
                                        <i class="bi bi-quote text-primary opacity-50 fs-5"></i>
                                        <?= htmlspecialchars($row['comment']) ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4 text-muted small fw-medium">
                                    <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="py-5">
                                    <i class="bi bi-chat-left-dots display-1 text-light d-block mb-3"></i>
                                    <h5 class="text-muted fw-bold">No feedback found</h5>
                                    <p class="text-muted small mb-0">You haven't received any reviews from students yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>