<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Admin Dashboard' ?> - JobBoard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Plus Jakarta Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #0d6efd;
            --bg-body: #f8fafc;
            --sidebar-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --glass: rgba(255, 255, 255, 0.95);
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* --- SIDEBAR STYLES --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1060;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar .brand {
            padding: 0.5rem 1rem 2rem;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            letter-spacing: -1px;
        }

        .nav-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            padding: 1rem 1rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--text-muted) !important;
            font-weight: 500;
            border-radius: 12px;
            margin-bottom: 0.3rem;
            transition: all 0.2s;
            text-decoration: none !important;
        }

        .nav-link i {
            font-size: 1.25rem;
            margin-right: 0.8rem;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background: rgba(13, 110, 253, 0.05);
        }

        .nav-link.active {
            color: #fff !important;
            background: var(--primary-color);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        }

        /* --- MAIN CONTENT & NAV STYLES --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .top-nav {
            background: var(--glass);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1.5rem;
        }

        /* --- MOBILE HAMBURGER ICON --- */
        .mobile-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.8rem;
            color: var(--text-main);
        }

        /* --- OVERLAY --- */
        #sidebarOverlay {
            display: none;
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1050;
            top: 0;
            left: 0;
        }

        @media (max-width: 991.98px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .mobile-toggle { display: block; }
            #sidebarOverlay.active { display: block; }
        }

        .fw-800 { font-weight: 800; }
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Logic Script -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }
</script>