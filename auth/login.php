<?php
require_once '../config/db.php';

// Kung naka-login na, i-redirect sa tamang dashboard base sa role (unless banned)
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $check = $conn->query("SELECT is_banned, role FROM users WHERE id = '$user_id'")->fetch_assoc();
    
    if ($check['is_banned'] == 1 && $check['role'] !== 'admin') {
        redirect('banned.php');
    }

    $role = $_SESSION['role'];
    if ($role === 'admin')   redirect('../admin/dashboard.php');
    if ($role === 'company') redirect('../company/dashboard.php');
    if ($role === 'student') redirect('../student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            // 1. I-set ang initial session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // 2. CHECK KUNG BANNED ANG USER (Maliban sa Admin)
            if ($user['is_banned'] == 1 && $user['role'] !== 'admin') {
                // I-redirect sa banned page imbes na sa dashboard
                redirect('banned.php');
            }

            // 3. Role-based Redirection
            if ($user['role'] === 'admin')   redirect('../admin/dashboard.php');
            if ($user['role'] === 'company') redirect('../company/dashboard.php');
            if ($user['role'] === 'student') redirect('../student/dashboard.php');

        } else {
            $_SESSION['login_error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['login_error'] = "Username not found.";
    }
    
    // Pag may error, ibalik sa index.php (kung saan nandoon ang login modal/form mo)
    header("Location: ../index.php");
    exit();
}

// Safety fallback: Redirect sa index kung walang POST request pero in-access ang file
header("Location: ../index.php");
exit();