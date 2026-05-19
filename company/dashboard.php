<?php 
include 'components/header.php'; 
include 'components/navbar.php'; 

$company_id = $company['id'];

// Stats Queries
$total_listings   = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE company_id='$company_id'")->fetch_assoc()['c'];
$open_listings    = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE company_id='$company_id' AND status='open'")->fetch_assoc()['c'];
$total_applicants = $conn->query("SELECT COUNT(*) AS c FROM applications a JOIN job_listings j ON j.id = a.job_id WHERE j.company_id = '$company_id'")->fetch_assoc()['c'];
$new_applicants   = $conn->query("SELECT COUNT(*) AS c FROM applications a JOIN job_listings j ON j.id = a.job_id WHERE j.company_id = '$company_id' AND a.status = 'pending'")->fetch_assoc()['c'];

// Recent applicants (Added s.is_verified and s.profile_photo in SELECT)
$recent = $conn->query("
    SELECT a.*, s.full_name, s.course, s.year_level, s.is_verified, s.profile_photo, j.title AS job_title
    FROM applications a
    JOIN students s ON s.id = a.student_id
    JOIN job_listings j ON j.id = a.job_id
    WHERE j.company_id = '$company_id'
    ORDER BY a.applied_at DESC
    LIMIT 6
");
?>

<style>
    .verified-badge {
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 50px;
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        border: 1px solid #bae6fd;
        text-transform: uppercase;
        vertical-align: middle;
    }
    /* Student Profile Image Styling */
    .applicant-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .applicant-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
    /* Company Logo Header Styling */
    .company-logo-header {
        width: 65px;
        height: 65px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .company-placeholder-header {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.5rem;
    }
</style>

<div class="container pb-5 mt-4">
    <!-- Header Profile Section -->
    <div class="card p-4 mb-4 border-0 shadow-sm rounded-4">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
            <!-- DISPLAY COMPANY LOGO -->
            <div class="flex-shrink-0">
                <?php if (!empty($company['profile_photo'])): ?>
                    <img src="../uploads/company_logos/<?= htmlspecialchars($company['profile_photo']) ?>" 
                         class="company-logo-header" alt="Company Logo">
                <?php else: ?>
                    <div class="company-placeholder-header">
                        <?= strtoupper(substr($company['company_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center text-md-start flex-grow-1">
                <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($company['company_name']) ?></h2>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i> <?= htmlspecialchars($company['industry']) ?> &bull; 
                    <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($company['address']) ?>
                </p>
            </div>
            <a href="post_job.php" class="btn btn-success px-4 rounded-pill shadow-sm fw-bold">+ Post a Job</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ['label' => 'Total Listings', 'value' => $total_listings, 'border' => 'primary'],
            ['label' => 'Open Listings', 'value' => $open_listings, 'border' => 'success'],
            ['label' => 'Total Applicants', 'value' => $total_applicants, 'border' => 'info'],
            ['label' => 'New / Pending', 'value' => $new_applicants, 'border' => 'warning']
        ];
        foreach ($stats as $s): ?>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100 border-0 shadow-sm border-start border-4 border-<?= $s['border'] ?> rounded-3">
                    <div class="text-muted small fw-bold text-uppercase"><?= $s['label'] ?></div>
                    <div class="h3 fw-bold mt-1 mb-0"><?= $s['value'] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Table Section -->
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h3 class="h5 fw-bold mb-0">Recent Applicants</h3>
        <a href="applicants.php" class="text-decoration-none small fw-bold">View All <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4 py-3 border-0">STUDENT</th>
                        <th class="py-3 border-0">COURSE & YEAR</th>
                        <th class="py-3 border-0">APPLIED FOR</th>
                        <th class="py-3 border-0">STATUS</th>
                        <th class="py-3 border-0">DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent->num_rows > 0): ?>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Student Profile Photo -->
                                        <?php if (!empty($row['profile_photo'])): ?>
                                            <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" 
                                                 class="applicant-img" alt="Profile">
                                        <?php else: ?>
                                            <div class="applicant-placeholder">
                                                <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-bold text-dark small"><?= htmlspecialchars($row['full_name']) ?></span>
                                                <!-- VERIFIED BADGE -->
                                                <?php if($row['is_verified'] == 1): ?>
                                                    <span class="verified-badge" title="Verified Account">
                                                        <i class="bi bi-patch-check-fill"></i> Verified
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($row['course']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.7rem;"><?= $row['year_level'] ?></div>
                                </td>
                                <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['job_title']) ?></td>
                                <td>
                                    <?php 
                                        $s = $row['status'];
                                        $badge_class = ($s == 'accepted') ? 'success' : (($s == 'rejected') ? 'danger' : (($s == 'viewed') ? 'info' : 'warning'));
                                    ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-<?= $badge_class ?> bg-opacity-10 text-<?= $badge_class ?> fw-bold border border-<?= $badge_class ?> border-opacity-25" style="font-size: 0.65rem;">
                                        <?= ucfirst($s) ?>
                                    </span>
                                </td>
                                <td class="text-muted small fw-medium"><?= date('M d, Y', strtotime($row['applied_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">No applicants yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>