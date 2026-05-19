<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username   = clean($conn, $_POST['username']);
    $email      = clean($conn, $_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];
    $full_name  = clean($conn, $_POST['full_name']);
    $course     = clean($conn, $_POST['course']);
    $year_level = clean($conn, $_POST['year_level']);
    $phone      = clean($conn, $_POST['phone']);

    // Validation
    if ($password !== $confirm) {
        $_SESSION['reg_error'] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $_SESSION['reg_error'] = "Password must be at least 6 characters.";
    } else {
        // Check if username or email exists
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        
        if ($check->num_rows > 0) {
            $_SESSION['reg_error'] = "Username or email is already taken.";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Start Transaction to ensure both inserts work or none at all
            $conn->begin_transaction();

            try {
                // Insert into users table
                $conn->query("INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed', 'student')");
                $user_id = $conn->insert_id;

                // Insert into students table
                $conn->query("INSERT INTO students (user_id, full_name, course, year_level, phone) VALUES ('$user_id', '$full_name', '$course', '$year_level', '$phone')");

                // Commit transaction
                $conn->commit();

                // Set success sessions for index.php
                $_SESSION['reg_success'] = true;
                $_SESSION['success_type'] = 'student'; // Eto ang mag-uutos sa JS na buksan ang Student Modal

            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $_SESSION['reg_error'] = "Registration failed. Please try again.";
            }
        }
    }
    
    // Redirect back to home
    header("Location: ../index.php");
    exit();
}