<?php
require_once '../config/db.php';
requireLogin('admin');

// Handle approve / reject logic
if (isset($_GET['action'], $_GET['id']) && is_numeric($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'approve') {
        $conn->query("UPDATE companies SET status='approved' WHERE id='$id'");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE companies SET status='rejected' WHERE id='$id'");
    }
    
    $current_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    header("Location: approve_companies.php?status=" . $current_status . "&msg=success");
    exit();
}

$filter = isset($_GET['status']) ? clean($conn, $_GET['status']) : 'pending';
$where  = $filter ? "WHERE c.status = '$filter'" : "";

$companies = $conn->query("
    SELECT c.*, u.email, u.username, u.created_at
    FROM companies c
    JOIN users u ON u.id = c.user_id
    $where
    ORDER BY u.created_at DESC
");

$page_title = "Company Management";
$page_subtitle = "Review and manage partner company registrations.";
$current_page = "companies";
$header_title = "Verify Partners";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .company-logo-admin {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #eee;
    }
    .company-logo-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background-color: #f8f9fa;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 1px solid #eee;
    }
    .modal-logo-preview {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 15px;
        margin-bottom: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
</style>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Status Filter Tabs -->
        <div class="d-flex mb-4">
            <div class="btn-group p-1 bg-white shadow-sm rounded-4" role="group">
                <a href="?status=pending"  class="btn rounded-3 px-4 fw-bold <?= $filter === 'pending' ? 'btn-primary' : 'btn-white border-0 text-muted' ?>">Pending</a>
                <a href="?status=approved" class="btn rounded-3 px-4 fw-bold <?= $filter === 'approved' ? 'btn-primary' : 'btn-white border-0 text-muted' ?>">Approved</a>
                <a href="?status=rejected" class="btn rounded-3 px-4 fw-bold <?= $filter === 'rejected' ? 'btn-primary' : 'btn-white border-0 text-muted' ?>">Rejected</a>
                <a href="?status="         class="btn rounded-3 px-4 fw-bold <?= $filter === '' ? 'btn-primary' : 'btn-white border-0 text-muted' ?>">All</a>
            </div>
        </div>

        <!-- Companies Table Card -->
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="px-4 py-3 border-0">COMPANY DETAILS</th>
                            <th class="py-3 border-0">CONTACT PERSON</th>
                            <th class="py-3 border-0">EMAIL & ADDRESS</th>
                            <th class="py-3 border-0">STATUS</th>
                            <th class="text-end px-4 py-3 border-0">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($companies->num_rows > 0): ?>
                            <?php while ($c = $companies->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <!-- COMPANY LOGO LOGIC -->
                                            <div class="me-3">
                                                <?php if (!empty($c['profile_photo'])): ?>
                                                    <img src="../uploads/company_logos/<?= htmlspecialchars($c['profile_photo']) ?>" class="company-logo-admin" alt="Logo">
                                                <?php else: ?>
                                                    <div class="company-logo-placeholder">
                                                        <?= strtoupper(substr($c['company_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($c['company_name']) ?></div>
                                                <div class="text-muted small"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($c['industry']) ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Applied: <?= date('M d, Y', strtotime($c['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($c['contact_person']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-telephone-fill me-1" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($c['phone']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small mb-1 text-primary fw-medium"><i class="bi bi-envelope-at-fill me-1"></i> <?= htmlspecialchars($c['email']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 180px;"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($c['address']) ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge_class = 'bg-warning-subtle text-warning border-warning';
                                            if($c['status'] == 'approved') $badge_class = 'bg-success-subtle text-success border-success';
                                            if($c['status'] == 'rejected') $badge_class = 'bg-danger-subtle text-danger border-danger';
                                        ?>
                                        <span class="badge <?= $badge_class ?> px-3 py-2 rounded-pill fw-bold border border-opacity-25">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="btn-group gap-2">
                                            <?php if ($c['status'] !== 'approved'): ?>
                                                <button type="button" 
                                                   class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold action-confirm shadow-sm"
                                                   data-url="?action=approve&id=<?= $c['id'] ?>&status=<?= $filter ?>"
                                                   data-title="Approve Company"
                                                   data-logo="<?= !empty($c['profile_photo']) ? '../uploads/company_logos/'.$c['profile_photo'] : '' ?>"
                                                   data-initial="<?= strtoupper(substr($c['company_name'], 0, 1)) ?>"
                                                   data-body="Are you sure you want to verify '<?= htmlspecialchars($c['company_name']) ?>'? This will grant them access to post vacancies."
                                                   data-btn="btn-success">
                                                   <i class="bi bi-check2"></i> Approve
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($c['status'] !== 'rejected'): ?>
                                                <button type="button" 
                                                   class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold action-confirm shadow-sm"
                                                   data-url="?action=reject&id=<?= $c['id'] ?>&status=<?= $filter ?>"
                                                   data-title="Reject Registration"
                                                   data-logo="<?= !empty($c['profile_photo']) ? '../uploads/company_logos/'.$c['profile_photo'] : '' ?>"
                                                   data-initial="<?= strtoupper(substr($c['company_name'], 0, 1)) ?>"
                                                   data-body="Do you want to decline the registration for '<?= htmlspecialchars($c['company_name']) ?>'?"
                                                   data-btn="btn-danger">
                                                   <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-inbox text-light display-1"></i>
                                        <p class="text-muted mt-3">No <?= $filter ?> company applications found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Premium Confirmation Modal -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-800 text-dark w-100 text-center" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4 text-center text-dark">
                <!-- MODAL LOGO PREVIEW -->
                <div id="modalLogoContainer" class="mx-auto"></div>
                
                <div class="px-2" id="modalBody" style="font-size: 0.95rem;">Are you sure you want to proceed?</div>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-5 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="modalConfirmBtn" class="btn px-4 rounded-pill shadow-sm fw-bold">Proceed</a>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const companyModalEl = document.getElementById('companyModal');
    const companyModal = new bootstrap.Modal(companyModalEl);
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const modalLogoContainer = document.getElementById('modalLogoContainer');

    document.querySelectorAll('.action-confirm').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const title = this.getAttribute('data-title');
            const body = this.getAttribute('data-body');
            const btnClass = this.getAttribute('data-btn');
            const logoPath = this.getAttribute('data-logo');
            const initial = this.getAttribute('data-initial');

            modalTitle.innerText = title;
            modalBody.innerText = body;
            modalConfirmBtn.href = url;
            modalConfirmBtn.className = 'btn px-4 rounded-pill shadow-sm fw-bold ' + btnClass;

            // Handle Modal Logo Display
            if (logoPath) {
                modalLogoContainer.innerHTML = `<img src="${logoPath}" class="modal-logo-preview">`;
            } else {
                modalLogoContainer.innerHTML = `<div class="company-logo-placeholder mx-auto mb-3" style="width:70px; height:70px; font-size:1.5rem;">${initial}</div>`;
            }

            companyModal.show();
        });
    });

    companyModalEl.addEventListener('hide.bs.modal', function () {
        if (document.activeElement && companyModalEl.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    });
});
</script>