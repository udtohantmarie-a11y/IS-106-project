<?php
require_once '../config/db.php';
requireLogin('company');

$user_id = $_SESSION['user_id'];

$company = $conn->query("
    SELECT c.*, u.email, u.username
    FROM companies c
    JOIN users u ON u.id = c.user_id
    WHERE u.id = '$user_id'
")->fetch_assoc();

// Block if not yet approved
if ($company['status'] !== 'approved') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pending Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#f8f9fa; min-height:100vh; display:flex; align-items:center; justify-content:center;}</style></head><body>
    <div class="card shadow-sm border-0 text-center p-5" style="max-width: 450px; border-radius: 15px;">
        <div class="display-1 text-warning mb-3">⌛</div>
        <h2 class="fw-bold">Account Pending</h2>
        <p class="text-muted">Your company registration is currently being reviewed by the admin.</p>
        <a href="../auth/logout.php" class="btn btn-outline-danger mt-3 px-4">Logout</a>
    </div></body></html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - JobBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7fe; color: #334155; }
        .navbar { background-color: #ffffff !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .nav-link { font-weight: 500; color: #64748b !important; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: #10b981 !important; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .avatar-circle {
            width: 60px; height: 60px; background: #10b981; 
            color: white; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; font-weight: 700; font-size: 24px;
        }
        .table thead th { background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; color: #64748b; }
        .badge { padding: 0.5em 0.8em; border-radius: 6px; font-weight: 600; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-viewed { background-color: #e0e7ff; color: #3730a3; }
        .status-accepted { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>