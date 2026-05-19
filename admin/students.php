<?php
require_once '../config/db.php';
requireLogin('admin');

// Handle Delete Logic
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    // Kunin muna ang user_id bago i-delete ang student record para mabura rin sa users table
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $user_id = $user['user_id'];
        // Burahin sa users table (mabubura na rin sa students dahil sa ON DELETE CASCADE)
        $conn->query("DELETE FROM users WHERE id = $user_id");
        $msg = "Student account and associated data have been removed.";
    }
    header("Location: students.php?msg=" . urlencode($msg));
    exit();
}

// Fetch all students with their account details and ban status
$students = $conn->query("
    SELECT s.*, u.email, u.username, u.is_banned, u.created_at AS joined_date
    FROM students s
    JOIN users u ON u.id = s.user_id
    ORDER BY s.full_name ASC
");

$page_title = "Student Directory";
$header_title = "User Management";

include 'components/header.php';
include 'components/sidebar.php';
?>

<style>
    .student-avatar {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
</style>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-800 text-dark mb-0">Student Directory</h3>
                <p class="text-muted small mb-0">Monitor student activities, verification status, and account restrictions.</p>
            </div>
            <div class="bg-white border px-3 py-2 rounded-4 shadow-sm">
                <span class="text-muted small fw-bold">Total Students: </span>
                <span class="text-primary fw-800"><?= $students->num_rows ?></span>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?= htmlspecialchars($_GET['msg']) ?></div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Students Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3 border-0 fw-bold">STUDENT NAME</th>
                            <th class="py-3 border-0 fw-bold">COURSE & YEAR</th>
                            <th class="py-3 border-0 fw-bold">VERIFICATION</th>
                            <th class="py-3 border-0 fw-bold text-center">ACCOUNT STATUS</th>
                            <th class="py-3 border-0 text-center fw-bold">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <!-- Profile Image Logic -->
                                            <div class="me-3">
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="../uploads/profile_photos/<?= htmlspecialchars($row['profile_photo']) ?>" 
                                                         class="student-avatar" alt="Profile">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder bg-primary bg-opacity-75">
                                                        <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div>
                                                <div class="fw-bold text-dark lh-1 mb-1"><?= htmlspecialchars($row['full_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($row['course']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.7rem;"><?= $row['year_level'] ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['is_verified'] == 1): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-bold border border-success border-opacity-25" style="font-size: 0.65rem;">
                                                <i class="bi bi-patch-check-fill me-1"></i> VERIFIED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 fw-bold border border-warning border-opacity-25" style="font-size: 0.65rem;">
                                                <i class="bi bi-clock-history me-1"></i> PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['is_banned'] == 1): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 0.6rem;">
                                                <i class="bi bi-slash-circle me-1"></i> BANNED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-success rounded-pill px-3 py-1 fw-bold border border-success border-opacity-10" style="font-size: 0.6rem;">
                                                <i class="bi bi-check2-circle me-1"></i> ACTIVE
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- View Profile Action -->
                                            <a href="view_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary border rounded-pill px-3 fw-bold small shadow-sm transition">
                                                Profile
                                            </a>
                                            
                                            <!-- Delete Action -->
                                            <form method="POST" onsubmit="return confirm('CRITICAL: Delete this student? This will permanently remove their user account, applications, and reviews.');">
                                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-white text-danger border rounded-pill px-2 shadow-sm transition" title="Delete Student">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-people display-1 text-light"></i>
                                        <p class="text-muted mt-3 mb-0">No students registered in the system yet.</p>
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

<style>
    .transition { transition: all 0.2s ease-in-out; }
    .btn:hover { transform: translateY(-1px); }
</style>

<?php include 'components/footer.php'; ?>