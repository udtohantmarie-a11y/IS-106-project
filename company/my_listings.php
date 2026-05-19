<?php 
include 'components/header.php'; 
include 'components/navbar.php'; 

$company_id = $company['id'];

// Logic: Toggle status (Open/Closed)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $jid = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM job_listings WHERE id='$jid' AND company_id='$company_id'")->fetch_assoc();
    if ($cur) {
        $new_status = $cur['status'] === 'open' ? 'closed' : 'open';
        $conn->query("UPDATE job_listings SET status='$new_status' WHERE id='$jid'");
        echo "<script>window.location.href='my_listings.php';</script>";
    }
}

// Logic: Delete listing
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $jid = (int)$_GET['delete'];
    $conn->query("DELETE FROM job_listings WHERE id='$jid' AND company_id='$company_id'");
    echo "<script>window.location.href='my_listings.php';</script>";
}

$listings = $conn->query("
    SELECT j.*,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS total_applicants,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'pending') AS new_applicants
    FROM job_listings j
    WHERE j.company_id = '$company_id'
    ORDER BY j.created_at DESC
");
?>

<div class="container pb-5">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-1">My Job Listings</h2>
            <p class="text-muted small mb-0">Manage and monitor your active job openings.</p>
        </div>
        <a href="post_job.php" class="btn btn-success px-4 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Post New Job
        </a>
    </div>

    <!-- Listings Table -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase small fw-bold text-muted">Job Details</th>
                        <th class="text-uppercase small fw-bold text-muted">Type</th>
                        <th class="text-center text-uppercase small fw-bold text-muted">Slots</th>
                        <th class="text-uppercase small fw-bold text-muted">Deadline</th>
                        <th class="text-center text-uppercase small fw-bold text-muted">Applicants</th>
                        <th class="text-uppercase small fw-bold text-muted">Status</th>
                        <th class="text-center text-uppercase small fw-bold text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listings->num_rows > 0): ?>
                        <?php while ($job = $listings->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></div>
                                    <div class="text-muted" style="font-size: 11px;">Posted on: <?= date('M d, Y', strtotime($job['created_at'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border px-2 py-1 text-uppercase" style="font-size: 10px;">
                                        <?= $job['type'] ?>
                                    </span>
                                </td>
                                <td class="text-center fw-semibold text-secondary">
                                    <?= $job['slots'] ?>
                                </td>
                                <td class="small">
                                    <?= $job['deadline'] ? date('M d, Y', strtotime($job['deadline'])) : '<span class="text-muted">No deadline</span>' ?>
                                </td>
                                <td class="text-center">
                                    <a href="applicants.php?job_id=<?= $job['id'] ?>" class="text-decoration-none">
                                        <span class="h6 fw-bold text-primary mb-0"><?= $job['total_applicants'] ?></span>
                                        <?php if ($job['new_applicants'] > 0): ?>
                                            <span class="badge bg-danger ms-1" style="font-size: 10px;">+<?= $job['new_applicants'] ?> New</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($job['status'] === 'open'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3">Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm" role="group">
                                        <!-- View Applicants -->
                                        <a href="applicants.php?job_id=<?= $job['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Applicants">
                                            <i class="bi bi-people"></i>
                                        </a>
                                        
                                        <!-- Toggle Status Trigger -->
                                        <button type="button" class="btn btn-outline-<?= $job['status'] === 'open' ? 'warning' : 'info' ?> btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#toggleModal<?= $job['id'] ?>" 
                                                title="<?= $job['status'] === 'open' ? 'Close Listing' : 'Reopen Listing' ?>">
                                            <i class="bi bi-<?= $job['status'] === 'open' ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>

                                        <!-- Delete Trigger -->
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#deleteModal<?= $job['id'] ?>" 
                                                title="Delete Listing">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- TOGGLE MODAL -->
                                    <div class="modal fade" id="toggleModal<?= $job['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-body text-center p-4">
                                                    <i class="bi bi-exclamation-circle text-warning display-4"></i>
                                                    <h5 class="fw-bold mt-3"><?= $job['status'] === 'open' ? 'Close Listing?' : 'Reopen Listing?' ?></h5>
                                                    <p class="text-muted small">Are you sure you want to change the status of <b><?= htmlspecialchars($job['title']) ?></b>?</p>
                                                    <div class="d-grid gap-2 mt-4">
                                                        <a href="my_listings.php?toggle=<?= $job['id'] ?>" class="btn btn-<?= $job['status'] === 'open' ? 'warning' : 'info' ?>">Confirm</a>
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DELETE MODAL -->
                                    <div class="modal fade" id="deleteModal<?= $job['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-body text-center p-4">
                                                    <i class="bi bi-trash text-danger display-4"></i>
                                                    <h5 class="fw-bold mt-3">Remove Listing?</h5>
                                                    <p class="text-muted small">Warning: This will permanently delete <b><?= htmlspecialchars($job['title']) ?></b> and all associated applications.</p>
                                                    <div class="d-grid gap-2 mt-4">
                                                        <a href="my_listings.php?delete=<?= $job['id'] ?>" class="btn btn-danger">Delete Permanently</a>
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-folder2-open display-1 text-light"></i>
                                    <p class="text-muted mt-3">You haven't posted any job listings yet.</p>
                                    <a href="post_job.php" class="btn btn-success btn-sm mt-2">Post your first listing</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>