<!-- Navbar CSS Fix -->
<style>
    .top-nav {
        background: #ffffff;
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky; /* O 'relative' depende sa layout mo */
        top: 0;
        z-index: 1050; /* Mas mataas kaysa sa main content (karaniwang 1000) */
        height: 70px;
    }

    /* Siguraduhin na ang dropdown menu ay hindi mapuputol */
    .dropdown-menu {
        z-index: 1060 !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .mobile-toggle {
        font-size: 1.5rem;
        cursor: pointer;
        display: none; /* Hidden by default */
    }

    @media (max-width: 991.98px) {
        .mobile-toggle {
            display: block;
        }
    }
    
    .fw-800 {
        font-weight: 800;
    }
</style>

<!-- Top Nav -->
<header class="top-nav shadow-sm">
    <div class="d-flex align-items-center">
        <!-- Modern Hamburger Menu for Mobile -->
        <div class="mobile-toggle me-3" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </div>
        
        <!-- Page Titles -->
        <div>
            <h5 class="fw-800 mb-0 text-dark">
                <?= isset($page_title) ? $page_title : 'Admin Dashboard' ?>
            </h5>
            <p class="text-muted small mb-0 d-none d-md-block">
                <?= isset($page_subtitle) ? $page_subtitle : 'System overview and management' ?>
            </p>
        </div>
    </div>

    <!-- Right Side: Admin Profile -->
    <div class="d-flex align-items-center">
        <div class="dropdown">
            <div class="d-flex align-items-center cursor-pointer bg-light p-1 pe-3 rounded-pill border shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    A
                </div>
                <div class="text-start d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.75rem; line-height: 1;">Administrator</div>
                </div>
            </div>
            
            <!-- Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 rounded-4 p-2">
                <li class="px-3 py-2 d-md-none">
                    <div class="fw-bold small">Administrator</div>
                    <div class="text-muted" style="font-size: 0.65rem;">admin@jobboard.com</div>
                </li>
                <li class="d-md-none"><hr class="dropdown-divider opacity-10"></li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 small" href="settings.php">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider opacity-10"></li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 small text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-left me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>