<?php
// Kunin ang user ID mula sa session
$current_user_id = $_SESSION['user_id'];

// Kunin ang fresh data ng student para sa photo at verification status
$student_query = $conn->query("SELECT profile_photo, is_verified, full_name, id FROM students WHERE user_id = '$current_user_id'");
$student_data = $student_query->fetch_assoc();

// Query para sa unread notifications count
$notif_count_res = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id = '$current_user_id' AND is_read = 0");
$unread_count = $notif_count_res->fetch_assoc()['total'] ?? 0;

// Query para sa huling 5 notifications
$notifications_res = $conn->query("SELECT * FROM notifications WHERE user_id = '$current_user_id' ORDER BY created_at DESC LIMIT 5");
?>

<header class="d-flex justify-content-between align-items-center mb-5">
    <!-- LEFT SIDE: Sidebar Toggle & Welcome Message -->
    <div class="d-flex align-items-center">
        <button class="mobile-toggle me-3 d-lg-none btn border-0" id="openSidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="d-none d-sm-block">
            <h4 class="fw-bold mb-0 text-dark"><?= $header_title ?? 'Dashboard' ?></h4>
            <p class="text-muted small mb-0 fst-italic">Logged in as <?= htmlspecialchars($student['username'] ?? 'Student') ?></p>
        </div>
    </div>
    
    <!-- RIGHT SIDE: Notifications & Profile -->
    <div class="d-flex align-items-center gap-3">
        
        <!-- NOTIFICATION DROPDOWN -->
        <div class="dropdown">
            <button class="btn bg-white rounded-circle shadow-sm position-relative p-0 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 42px; height: 42px; border: 1px solid #eee;">
                <i class="bi bi-bell fs-5 text-dark"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.6rem; padding: 0.35em 0.5em;">
                        <?= $unread_count > 99 ? '99+' : $unread_count ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-3 mt-2" style="width: 320px;">
                <li>
                    <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                        <h6 class="fw-bold mb-0">Notifications</h6>
                        <?php if ($unread_count > 0): ?>
                            <form action="notifications.php" method="POST" class="m-0">
                                <button type="submit" name="mark_all_read" class="btn btn-link p-0 text-primary text-decoration-none fw-bold" style="font-size: 0.75rem;">Mark all as read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
                <li><hr class="dropdown-divider opacity-50"></li>
                
                <div class="notification-list" style="max-height: 350px; overflow-y: auto;">
                    <?php if ($notifications_res && $notifications_res->num_rows > 0): ?>
                        <?php while($notif = $notifications_res->fetch_assoc()): 
                            $is_unread = $notif['is_read'] == 0;
                            $msg_lower = strtolower($notif['message']);
                            
                            // Assign icon based on keyword
                            $icon_class = "bi-bell-fill text-primary";
                            if(strpos($msg_lower, 'accepted') !== false) $icon_class = "bi-check-circle-fill text-success";
                            if(strpos($msg_lower, 'rejected') !== false || strpos($msg_lower, 'decided not') !== false) $icon_class = "bi-x-circle-fill text-danger";
                            if(strpos($msg_lower, 'hiring') !== false) $icon_class = "bi-megaphone-fill text-info";
                        ?>
                            <li>
                                <a class="dropdown-item p-3 rounded-3 mb-1 <?= $is_unread ? 'bg-light border-start border-primary border-3' : '' ?>" 
                                   href="<?= $notif['job_id'] ? 'job_details.php?id='.$notif['job_id'].'&notif_id='.$notif['id'] : 'notifications.php' ?>" 
                                   style="white-space: normal;">
                                    <div class="d-flex gap-2">
                                        <div class="mt-1">
                                            <i class="bi <?= $icon_class ?>" style="font-size: 0.9rem;"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 small text-dark <?= $is_unread ? 'fw-bold' : '' ?>" style="line-height: 1.4;">
                                                <?= htmlspecialchars($notif['message']) ?>
                                            </p>
                                            <span class="text-muted small" style="font-size: 0.65rem;">
                                                <?= date('M d, h:i A', strtotime($notif['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="text-center py-4">
                            <i class="bi bi-bell-slash text-muted fs-2 opacity-50"></i>
                            <p class="text-muted small mb-0">No notifications yet</p>
                        </li>
                    <?php endif; ?>
                </div>

                <li><hr class="dropdown-divider opacity-50"></li>
                <li>
                    <a class="dropdown-item text-center small text-primary fw-bold py-2 rounded-3" href="notifications.php">
                        See All Updates
                    </a>
                </li>
            </ul>
        </div>

        <!-- PROFILE SECTION -->
        <div class="d-flex align-items-center bg-white p-1 rounded-pill shadow-sm pe-3 border" style="cursor: pointer;" onclick="window.location.href='profile.php'">
            <div class="avatar-container position-relative me-2">
                <?php if (!empty($student_data['profile_photo'])): ?>
                    <img src="../uploads/profile_photos/<?= $student_data['profile_photo'] ?>" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: cover; border: 2px solid #fff;">
                <?php else: ?>
                    <div class="avatar-circle">
                        <?= strtoupper(substr($student_data['full_name'] ?? 'S', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($student_data['is_verified'] == 1): ?>
                    <span class="position-absolute bottom-0 end-0 translate-middle-y">
                        <i class="bi bi-patch-check-fill text-primary bg-white rounded-circle shadow-sm" style="font-size: 0.85rem; padding: 1px;"></i>
                    </span>
                <?php endif; ?>
            </div>
            <div class="text-start d-none d-md-block">
                <div class="fw-bold small lh-1 text-dark">
                    <?= htmlspecialchars($student_data['full_name'] ?? 'User Profile') ?>
                </div>
                <span class="text-muted" style="font-size: 0.65rem;">
                    #STU-<?= $student_data['id'] ?>
                    <?php if ($student_data['is_verified'] == 1): ?>
                        <span class="text-primary fw-bold ms-1">Verified</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</header>

<style>
    .avatar-circle {
        width: 38px;
        height: 38px;
        background: linear-gradient(45deg, #0d6efd, #0dcaf0);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid #fff;
        font-size: 0.9rem;
    }
    .avatar-container { width: 38px; height: 38px; }
    
    .notification-list::-webkit-scrollbar { width: 4px; }
    .notification-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    .dropdown-item:active { background-color: #f1f5f9; color: inherit; }
    .dropdown-item:hover { background-color: #f8fafc; }
    
    .bg-light.border-start {
        background-color: #f0f7ff !important;
        border-left-width: 4px !important;
    }
    
    .bi-patch-check-fill {
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-toggle {
        background: white;
        border: 1px solid #eee;
        padding: 5px 10px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
</style>