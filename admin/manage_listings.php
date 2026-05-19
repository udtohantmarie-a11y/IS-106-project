<?php
require_once '../config/db.php';
requireLogin('admin');

// 1. Delete listing logic
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM job_listings WHERE id='$id'");
    header("Location: manage_listings.php?msg=deleted");
    exit();
}

// 2. Toggle status logic
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM job_listings WHERE id='$id'")->fetch_assoc();
    if ($cur) {
        $new = $cur['status'] === 'open' ? 'closed' : 'open';
        $conn->query("UPDATE job_listings SET status='$new' WHERE id='$id'");
    }
    header("Location: manage_listings.php?msg=toggled");
    exit();
}

// 3. Filtering & Search Logic
$search = isset($_GET['search']) ? clean($conn, $_GET['search']) : '';
$type   = isset($_GET['type'])   ? clean($conn, $_GET['type'])   : '';
$status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';

$where = "1=1";
if ($search) $where .= " AND (j.title LIKE '%$search%' OR c.company_name LIKE '%$search%')";
if ($type)   $where .= " AND j.type = '$type'";
if ($status) $where .= " AND j.status = '$status'";

$listings = $conn->query("
    SELECT j.*, c.company_name,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS total_apps
    FROM job_listings j
    JOIN companies c ON c.id = j.company_id
    WHERE $where
    ORDER BY j.created_at DESC
");

$page_title = "Manage Job Listings";
$page_subtitle = "Monitor and control job and internship vacancies.";
$current_page = "jobs";
$header_title = "Job Management";

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-body p-4">
                <form class="row g-3" method="GET">
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted mb-1">SEARCH</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-light border-start-0 ps-0 shadow-none" placeholder="Search title or company..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">TYPE</label>
                        <select name="type" class="form-select bg-light border-0 shadow-none">
                            <option value="">All Types</option>
                            <option value="job" <?= $type === 'job' ? 'selected' : '' ?>>Job</option>
                            <option value="internship" <?= $type === 'internship' ? 'selected' : '' ?>>Internship</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">STATUS</label>
                        <select name="status" class="form-select bg-light border-0 shadow-none">
                            <option value="">All Status</option>
                            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">Apply Filter</button>
                        <?php if ($search || $type || $status): ?>
                            <a href="manage_listings.php" class="btn btn-light border fw-bold rounded-pill">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <h6 class="text-muted fw-normal mb-0 small">Displaying <strong><?= $listings->num_rows ?></strong> active listings</h6>
        </div>

        <!-- Listings Table -->
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3 border-0">JOB TITLE & COMPANY</th>
                            <th class="py-3 border-0">TYPE</th>
                            <th class="py-3 border-0">SLOTS</th>
                            <th class="py-3 border-0">APPS</th>
                            <th class="py-3 border-0">DEADLINE</th>
                            <th class="py-3 border-0">STATUS</th>
                            <th class="py-3 border-0 text-end pe-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($listings->num_rows > 0): ?>
                            <?php while ($job = $listings->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($job['company_name']) ?></div>
                                    </td>
                                    <td>
                                        <?php $typeColor = $job['type'] === 'job' ? 'primary' : 'info'; ?>
                                        <span class="badge bg-<?= $typeColor ?>-subtle text-<?= $typeColor ?> rounded-pill px-3 py-2 fw-bold">
                                            <?= ucfirst($job['type']) ?>
                                        </span>
                                    </td>
                                    <td><span class="fw-bold text-dark"><?= $job['slots'] ?></span></td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border px-3 fw-bold">
                                            <?= $job['total_apps'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-medium <?= (strtotime($job['deadline']) < time() && $job['deadline']) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= $job['deadline'] ? date('M d, Y', strtotime($job['deadline'])) : '—' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($job['status'] === 'open'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-bold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Open
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill fw-bold">
                                                <i class="bi bi-x-circle-fill me-1"></i> Closed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border-0 rounded-circle shadow-none" type="button" data-bs-toggle="dropdown" style="width: 32px; height: 32px;">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
                                                <li>
                                                    <a class="dropdown-item rounded-3 py-2 action-confirm" 
                                                       href="#" 
                                                       data-url="?toggle=<?= $job['id'] ?>"
                                                       data-title="<?= $job['status'] === 'open' ? 'Close Listing' : 'Reopen Listing' ?>"
                                                       data-body="Confirm toggling the visibility for '<?= htmlspecialchars($job['title']) ?>'."
                                                       data-btn="btn-warning">
                                                        <i class="bi <?= $job['status'] === 'open' ? 'bi-lock text-warning' : 'bi-unlock text-success' ?> me-2"></i>
                                                        <?= $job['status'] === 'open' ? 'Close Listing' : 'Reopen Listing' ?>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider opacity-10"></li>
                                                <li>
                                                    <a class="dropdown-item rounded-3 py-2 text-danger action-confirm" 
                                                       href="#" 
                                                       data-url="?delete=<?= $job['id'] ?>"
                                                       data-title="Delete Listing"
                                                       data-body="WARNING: This will permanently delete '<?= htmlspecialchars($job['title']) ?>' and all related student applications. This action is irreversible."
                                                       data-btn="btn-danger">
                                                        <i class="bi bi-trash3 me-2"></i> Delete Permanently
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-clipboard-x display-1 text-light"></i>
                                        <p class="text-muted mt-3">No job listings found matching your search.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- container-fluid end -->
</div> <!-- main-content end -->

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center text-dark">
                <div id="modalIconContainer" class="mb-3">
                    <i class="bi bi-question-circle text-primary display-4"></i>
                </div>
                <div id="modalBody" class="px-3">Are you sure you want to proceed?</div>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="modalConfirmBtn" class="btn px-4 rounded-pill shadow-sm fw-bold">Proceed</a>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl);
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

    // Handle button clicks
    document.querySelectorAll('.action-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('data-url');
            const title = this.getAttribute('data-title');
            const body = this.getAttribute('data-body');
            const btnClass = this.getAttribute('data-btn');

            modalTitle.innerText = title;
            modalBody.innerText = body;
            modalConfirmBtn.href = url;
            
            // Clean classes and apply new
            modalConfirmBtn.className = 'btn px-4 shadow-sm fw-bold rounded-pill ' + btnClass;

            confirmModal.show();
        });
    });

    /**
     * Accessibility Fix:
     * Prevents the "Blocked aria-hidden" console warning by blurring the 
     * active button inside the modal before it starts to hide.
     */
    confirmModalEl.addEventListener('hide.bs.modal', function () {
        if (document.activeElement && confirmModalEl.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    });
});
</script>