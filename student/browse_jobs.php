<?php
require_once '../config/db.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// 1. Get search and filter values
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';

// 2. Base Query - Inupdate para isama ang company profile photo
$query = "
    SELECT j.*, c.id AS company_id, c.company_name, c.industry, c.profile_photo AS company_logo,
    (SELECT COUNT(*) FROM wishlist w WHERE w.job_id = j.id AND w.user_id = '$user_id') as is_wishlisted
    FROM job_listings j
    JOIN companies c ON j.company_id = c.id
    WHERE j.status = 'open' AND j.slots > 0
";

if ($search) {
    $query .= " AND (j.title LIKE '%$search%' OR c.company_name LIKE '%$search%' OR j.location LIKE '%$search%')";
}

if ($type_filter) {
    $query .= " AND j.type = '$type_filter'";
}

$query .= " ORDER BY j.created_at DESC";
$jobs = $conn->query($query);

$student = $conn->query("SELECT s.*, u.username FROM students s JOIN users u ON u.id = s.user_id WHERE u.id = '$user_id'")->fetch_assoc();

$page_title = "Browse Jobs";
$current_page = "browse";
$header_title = "Explore Opportunities";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    /* HIDE SCROLLBAR */
    body { 
        scrollbar-width: none; 
        -ms-overflow-style: none; 
        background-color: #f8f9fa;
    }
    body::-webkit-scrollbar { display: none; }

    .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .card-hover:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(13, 110, 253, 0.1) !important; }
    
    .job-title-link { text-decoration: none; color: #212529; transition: color 0.2s; }
    .job-title-link:hover { color: #0d6efd; }
    
    .company-link { text-decoration: none; color: #0d6efd; transition: opacity 0.2s; }
    .company-link:hover { text-decoration: underline; opacity: 0.8; }
    
    /* Company Logo Styles */
    .company-logo-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #eee;
    }
    .company-logo-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background-color: #e9ecef;
        color: #adb5bd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: bold;
    }

    .wishlist-btn {
        border: none;
        background: none;
        padding: 0;
        cursor: pointer;
        transition: transform 0.2s ease;
        color: #ced4da;
    }
    .wishlist-btn:hover { transform: scale(1.2); }
    .wishlist-btn.active { color: #dc3545; }
    .wishlist-btn i { font-size: 1.25rem; }

    .search-input-group {
        background-color: white;
        height: 55px;
        border: 1px solid #dee2e6;
        transition: border-color 0.3s;
    }
    .search-input-group:focus-within {
        border-color: #0d6efd;
    }
</style>

<main class="main-content">
    <?php include 'components/navbar.php'; ?>

    <!-- Search Section -->
    <section class="mb-5 mt-2">
        <form action="" method="GET" id="searchForm" class="row g-3">
            <div class="col-md-7">
                <div class="search-input-group d-flex align-items-center px-4 rounded-pill shadow-sm">
                    <i class="bi bi-search text-muted me-3"></i>
                    <input type="search" name="search" id="searchInput" class="form-control border-0 shadow-none" placeholder="Search position, company, or city..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select search-input-group border-0 shadow-sm rounded-pill px-4" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="internship" <?= $type_filter == 'internship' ? 'selected' : '' ?>>Internship</option>
                    <option value="job" <?= $type_filter == 'job' ? 'selected' : '' ?>>Full-time Job</option>
                    <option value="part-time" <?= $type_filter == 'part-time' ? 'selected' : '' ?>>Part-time Job</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm fw-bold h-100">Search</button>
            </div>
        </form>
    </section>

    <!-- Listings Grid -->
    <div class="row g-4">
        <?php if ($jobs && $jobs->num_rows > 0): ?>
            <?php while ($row = $jobs->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm card-hover rounded-4 position-relative">
                        <div class="card-body p-4">
                            <!-- Top Row: Badge & Wishlist -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <?php 
                                    $type_label = ($row['type'] == 'internship') ? 'Internship' : (($row['type'] == 'job') ? 'Full-time' : 'Part-time');
                                    $type_class = ($row['type'] == 'internship') ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary'; 
                                ?>
                                <span class="badge <?= $type_class ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    <?= $type_label ?>
                                </span>
                                
                                <button type="button" 
                                        class="wishlist-btn <?= $row['is_wishlisted'] ? 'active' : '' ?>" 
                                        onclick="toggleWishlist(this, <?= $row['id'] ?>)"
                                        title="<?= $row['is_wishlisted'] ? 'Remove from saved' : 'Save for later' ?>">
                                    <i class="bi <?= $row['is_wishlisted'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                </button>
                            </div>
                            
                            <!-- Middle: Company Logo & Titles -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="me-3">
                                    <?php if (!empty($row['company_logo'])): ?>
                                        <img src="../uploads/company_logos/<?= htmlspecialchars($row['company_logo']) ?>" class="company-logo-img" alt="Logo">
                                    <?php else: ?>
                                        <div class="company-logo-placeholder">
                                            <?= strtoupper(substr($row['company_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="fw-bold mb-0 text-truncate">
                                        <a href="job_details.php?id=<?= $row['id'] ?>" class="job-title-link"><?= htmlspecialchars($row['title']) ?></a>
                                    </h5>
                                    <p class="small fw-bold mb-0">
                                        <a href="company_profile.php?id=<?= $row['company_id'] ?>" class="company-link"><?= htmlspecialchars($row['company_name']) ?></a>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Details Section -->
                            <div class="mb-4">
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-geo-alt-fill me-2 text-secondary"></i><?= htmlspecialchars($row['location'] ?: 'Not specified') ?>
                                </div>
                                <div class="small text-success fw-bold mb-2">
                                    <i class="bi bi-cash-stack me-2"></i><?= htmlspecialchars($row['salary'] ?: 'Unpaid / Not specified') ?>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-tags-fill me-2 text-secondary"></i><?= htmlspecialchars($row['industry']) ?>
                                </div>
                            </div>
                            
                            <hr class="opacity-10">
                            
                            <!-- Footer: Slots & CTA -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="small">
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                        <span class="fw-bold"><?= $row['slots'] ?></span> slots left
                                    </span>
                                </div>
                                <a href="job_details.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm px-4 rounded-pill fw-bold">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="py-5 bg-white rounded-4 shadow-sm border">
                    <i class="bi bi-clipboard-x display-1 text-light d-block mb-3"></i>
                    <h5 class="text-muted fw-bold">No available positions</h5>
                    <p class="text-muted small mb-0">Check back later or try adjusting your search filters.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleWishlist(btn, jobId) {
        const icon = btn.querySelector('i');
        fetch('api_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `job_id=${jobId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'added') {
                btn.classList.add('active');
                icon.classList.replace('bi-heart', 'bi-heart-fill');
            } else if(data.status === 'removed') {
                btn.classList.remove('active');
                icon.classList.replace('bi-heart-fill', 'bi-heart');
            }
        });
    }

    // Sidebar logic
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');

    function toggleSidebar() { 
        if(sidebar && overlay) {
            sidebar.classList.toggle('show'); overlay.classList.toggle('show'); 
        }
    }
    if(openBtn) openBtn.addEventListener('click', toggleSidebar);
    if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
</script>
</body>
</html>