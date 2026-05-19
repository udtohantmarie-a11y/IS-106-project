<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// 1. Get student info for components
$student_query = $conn->query("
    SELECT s.*, u.username 
    FROM students s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.id = '$user_id'
");
$student = $student_query->fetch_assoc();

// 2. Fetch Saved Jobs from Wishlist table (Included c.profile_photo)
$saved_jobs = $conn->query("
    SELECT w.id AS wishlist_id, j.*, c.company_name, c.industry, c.profile_photo AS company_logo
    FROM wishlist w
    JOIN job_listings j ON w.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE w.user_id = '$user_id'
    ORDER BY j.created_at DESC
");

// 3. Component Variables
$page_title = "Saved Jobs";
$current_page = "saved_jobs"; // Para mag-active ang menu sa sidebar
$header_title = "My Collection";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .transition { transition: all 0.2s ease; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.01) !important; }
    .main-content { background-color: #f8f9fa; min-height: 100vh; }
    
    /* Company Logo Styles */
    .company-logo-wishlist {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #eee;
    }
    .logo-placeholder-wishlist {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background-color: #e9ecef;
        color: #adb5bd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">Saved for Later</h3>
                <p class="text-muted small mb-0">Review the opportunities you've marked with a heart.</p>
            </div>
            <a href="browse_jobs.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-search me-1"></i> Browse More
            </a>
        </div>

        <!-- Saved Jobs Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3 border-0" style="width: 50px;"></th>
                            <th class="py-3 border-0">JOB POSITION</th>
                            <th class="py-3 border-0">COMPANY</th>
                            <th class="py-3 border-0 text-center">TYPE</th>
                            <th class="py-3 border-0 text-end pe-4">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($saved_jobs && $saved_jobs->num_rows > 0): ?>
                            <?php while ($row = $saved_jobs->fetch_assoc()): ?>
                                <tr id="row-<?= $row['id'] ?>" class="transition">
                                    <td class="ps-4">
                                        <div class="text-danger">
                                            <i class="bi bi-heart-fill fs-5"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['location']) ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- Company Logo Logic -->
                                            <?php if (!empty($row['company_logo'])): ?>
                                                <img src="../uploads/company_logos/<?= htmlspecialchars($row['company_logo']) ?>" class="company-logo-wishlist" alt="Logo">
                                            <?php else: ?>
                                                <div class="logo-placeholder-wishlist">
                                                    <i class="bi bi-building"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="fw-semibold text-muted small">
                                                <?= htmlspecialchars($row['company_name']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= ($row['type'] == 'internship') ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.65rem;">
                                            <?= strtoupper($row['type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="job_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold small text-primary shadow-sm">
                                                Details
                                            </a>
                                            <!-- Remove from wishlist button -->
                                            <button class="btn btn-sm btn-white text-danger border rounded-pill px-3 fw-bold small shadow-sm" onclick="removeFromWishlist(<?= $row['id'] ?>)">
                                                Remove
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Empty State -->
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-heart display-1 text-light d-block mb-3"></i>
                                        <h5 class="text-muted fw-bold">Your wishlist is empty</h5>
                                        <p class="text-muted small mb-4">Start exploring and save jobs that catch your interest!</p>
                                        <a href="browse_jobs.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Browse Now</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // AJAX Function para mag-remove sa wishlist nang hindi nire-refresh ang page
    function removeFromWishlist(jobId) {
        if(confirm('Remove this job from your saved list?')) {
            fetch('api_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `job_id=${jobId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'removed') {
                    window.location.reload();
                }
            });
        }
    }

    // Sidebar Logic
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