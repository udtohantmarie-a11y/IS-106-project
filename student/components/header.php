<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Student Dashboard' ?> - JobBoard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7fe; overflow-x: hidden; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: 260px; background: #fff; border-right: 1px solid #e9ecef; z-index: 1050; transition: all 0.3s ease; }
        .sidebar-brand { padding: 1.5rem; font-weight: 800; font-size: 1.25rem; color: #0d6efd; text-decoration: none; display: flex; align-items: center; justify-content: space-between; }
        .nav-link { padding: 0.8rem 1.5rem; color: #6c757d; font-weight: 500; display: flex; align-items: center; border-radius: 8px; margin: 0.2rem 1rem; transition: 0.2s; }
        .nav-link:hover { background: #f8f9fa; color: #0d6efd; }
        .nav-link.active { background: #e7f1ff; color: #0d6efd; }
        .nav-link i { font-size: 1.2rem; margin-right: 1rem; }
        .main-content { margin-left: 260px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .sidebar-overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.1); z-index: 1040; }
        @media (max-width: 991.98px) {
            .sidebar { margin-left: -260px; }
            .sidebar.show { margin-left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-overlay.show { display: block; }
            .mobile-toggle { display: block !important; }
        }
        .mobile-toggle { display: none; background: white; border: 1px solid #dee2e6; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
        .avatar-circle { width: 40px; height: 40px; background: #0d6efd; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>