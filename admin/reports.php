<?php
require_once '../config/db.php';
requireLogin('admin');

// 1. Summary numbers
$total_students     = ($res = $conn->query("SELECT COUNT(*) AS c FROM students")) ? $res->fetch_assoc()['c'] : 0;
$total_companies    = ($res = $conn->query("SELECT COUNT(*) AS c FROM companies WHERE status='approved'")) ? $res->fetch_assoc()['c'] : 0;
$total_listings     = ($res = $conn->query("SELECT COUNT(*) AS c FROM job_listings")) ? $res->fetch_assoc()['c'] : 0;
$open_listings      = ($res = $conn->query("SELECT COUNT(*) AS c FROM job_listings WHERE status='open'")) ? $res->fetch_assoc()['c'] : 0;
$total_applications = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications")) ? $res->fetch_assoc()['c'] : 0;
$total_accepted     = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status='accepted'")) ? $res->fetch_assoc()['c'] : 0;
$total_rejected     = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status='rejected'")) ? $res->fetch_assoc()['c'] : 0;
$total_pending      = ($res = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status='pending'")) ? $res->fetch_assoc()['c'] : 0;

// 2. Top companies by applicants
$top_companies = $conn->query("
    SELECT c.company_name, COUNT(a.id) AS total_apps,
           SUM(CASE WHEN a.status='accepted' THEN 1 ELSE 0 END) AS accepted
    FROM companies c
    JOIN job_listings j ON j.company_id = c.id
    JOIN applications a ON a.job_id = j.id
    GROUP BY c.id, c.company_name
    ORDER BY total_apps DESC
    LIMIT 10
");

// 3. Top courses applying
$top_courses = $conn->query("
    SELECT s.course, COUNT(a.id) AS total_apps
    FROM students s
    JOIN applications a ON a.student_id = s.id
    GROUP BY s.course
    ORDER BY total_apps DESC
    LIMIT 8
");

// 4. Monthly applications (last 6 months)
$monthly = $conn->query("
    SELECT DATE_FORMAT(applied_at, '%b %Y') AS month,
           COUNT(*) AS total
    FROM applications
    WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(applied_at, '%Y-%m')
    ORDER BY applied_at ASC
");

$monthly_labels = [];
$monthly_data   = [];
while ($row = $monthly->fetch_assoc()) {
    $monthly_labels[] = $row['month'];
    $monthly_data[]   = $row['total'];
}

$page_title = "System Reports";
$page_subtitle = "Analytical overview of the Job & Internship Board performance.";
$current_page = "reports";
$header_title = "Analytics & Reports";

include 'components/header.php';
include 'components/sidebar.php';
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid">
        <!-- Summary Statistics Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75 fw-bold text-uppercase">Students</div>
                            <h2 class="fw-800 mb-0 mt-1"><?= number_format($total_students) ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-2">
                            <i class="bi bi-mortarboard fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%); border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75 fw-bold text-uppercase">Companies</div>
                            <h2 class="fw-800 mb-0 mt-1"><?= number_format($total_companies) ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-2">
                            <i class="bi bi-building-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75 fw-bold text-uppercase">Open Jobs</div>
                            <h2 class="fw-800 mb-0 mt-1"><?= $open_listings ?> <span class="opacity-50 fs-6 fw-normal">/ <?= $total_listings ?></span></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-2">
                            <i class="bi bi-briefcase fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%); border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75 fw-bold text-uppercase">Total Apps</div>
                            <h2 class="fw-800 mb-0 mt-1"><?= number_format($total_applications) ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-2">
                            <i class="bi bi-file-earmark-bar-graph fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Status Breakdown -->
        <div class="row g-3 mb-5">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 rounded-4 bg-white">
                    <div class="text-muted small fw-bold mb-1">PENDING</div>
                    <div class="h3 fw-800 text-warning mb-0"><?= $total_pending ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 rounded-4 bg-white">
                    <div class="text-muted small fw-bold mb-1">ACCEPTED</div>
                    <div class="h3 fw-800 text-success mb-0"><?= $total_accepted ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 rounded-4 bg-white">
                    <div class="text-muted small fw-bold mb-1">REJECTED</div>
                    <div class="h3 fw-800 text-danger mb-0"><?= $total_rejected ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 rounded-4 bg-white">
                    <div class="text-muted small fw-bold mb-1">SUCCESS RATE</div>
                    <div class="h3 fw-800 text-primary mb-0">
                        <?= $total_applications > 0 ? round(($total_accepted / $total_applications) * 100, 1) : 0 ?>%
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Monthly Trends Chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 h-100 rounded-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-800 text-dark mb-0">Application Trends</h5>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small fw-bold">6 Months View</span>
                    </div>
                    <div style="height: 350px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Companies List -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm overflow-hidden h-100 rounded-4 bg-white">
                    <div class="p-4 border-bottom">
                        <h5 class="fw-800 text-dark mb-0">Top Performing Companies</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="bg-light">
                                <tr class="text-muted small">
                                    <th class="ps-4 border-0">COMPANY</th>
                                    <th class="text-center border-0">APPS</th>
                                    <th class="text-center border-0">HIRED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($top_companies->num_rows > 0):
                                    $rows = $top_companies->fetch_all(MYSQLI_ASSOC);
                                    $max = max(array_column($rows, 'total_apps')) ?: 1;
                                    foreach ($rows as $r): 
                                ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['company_name']) ?></div>
                                            <div class="progress mt-2" style="height: 4px; width: 100px;">
                                                <div class="progress-bar bg-primary" style="width: <?= round(($r['total_apps'] / $max) * 100) ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-800 text-dark"><?= $r['total_apps'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success px-3 rounded-pill fw-bold"><?= $r['accepted'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="3" class="text-center py-5 text-muted small">No company data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Courses Analysis -->
        <div class="card border-0 shadow-sm overflow-hidden mb-5 rounded-4 bg-white">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-800 text-dark mb-0">Participation by Degree / Course</h5>
                <i class="bi bi-graph-up text-primary fs-4"></i>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 border-0">COURSE</th>
                            <th class="border-0">APPLICATION VOLUME</th>
                            <th class="text-center border-0 pe-4">MARKET SHARE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($top_courses->num_rows > 0):
                            $course_rows = $top_courses->fetch_all(MYSQLI_ASSOC);
                            $cmax = max(array_column($course_rows, 'total_apps')) ?: 1;
                            foreach ($course_rows as $cr): 
                        ?>
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark"><?= htmlspecialchars($cr['course']) ?></td>
                                <td style="width: 45%;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress flex-grow-1 shadow-sm" style="height: 8px; border-radius: 10px;">
                                            <div class="progress-bar bg-info" style="width: <?= round(($cr['total_apps'] / $cmax) * 100) ?>%"></div>
                                        </div>
                                        <span class="small fw-800 text-dark"><?= $cr['total_apps'] ?></span>
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                                        <?= $total_applications > 0 ? round(($cr['total_apps'] / $total_applications) * 100, 1) : 0 ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted small">No course-specific data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    // Create Gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(13, 110, 253, 0.4)');
    gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [{
                label: 'Applications',
                data: <?= json_encode($monthly_data) ?>,
                borderColor: '#0d6efd',
                borderWidth: 4,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd',
                pointBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", weight: 'bold' },
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif" }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.05)', borderDash: [5, 5] },
                    ticks: { 
                        stepSize: 1,
                        font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' }
                    } 
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' } }
                }
            }
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>