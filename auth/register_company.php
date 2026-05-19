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
    $username       = clean($conn, $_POST['username']);
    $email          = clean($conn, $_POST['email']);
    $password       = $_POST['password'];
    $confirm        = $_POST['confirm_password'];
    $company_name   = clean($conn, $_POST['company_name']);
    $industry       = clean($conn, $_POST['industry']);
    $address        = clean($conn, $_POST['address']);
    $contact_person = clean($conn, $_POST['contact_person']);
    $phone          = clean($conn, $_POST['phone']);

    // Validation
    if ($password !== $confirm) {
        $_SESSION['reg_error'] = "Passwords do not match.";
        header("Location: ../index.php?view=register_company");
        exit();
    } elseif (strlen($password) < 6) {
        $_SESSION['reg_error'] = "Password must be at least 6 characters.";
        header("Location: ../index.php?view=register_company");
        exit();
    } else {
        // Check if username or email is already taken
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        
        if ($check->num_rows > 0) {
            $_SESSION['reg_error'] = "Username or email is already taken.";
            header("Location: ../index.php?view=register_company");
            exit();
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Transaction for database integrity
            $conn->begin_transaction();

            try {
                // 1. Insert into users table
                $conn->query("INSERT INTO users (username, email, password, role) 
                            VALUES ('$username', '$email', '$hashed', 'company')");
                $user_id = $conn->insert_id;

                // 2. Insert into companies table
                $conn->query("INSERT INTO companies (user_id, company_name, industry, address, contact_person, phone) 
                            VALUES ('$user_id', '$company_name', '$industry', '$address', '$contact_person', '$phone')");

                $conn->commit();

                // Set success state
                $_SESSION['reg_success'] = true;
                $_SESSION['success_type'] = 'company'; // Important: para malaman ng index.php kung anong message ang ipapakita

                header("Location: ../index.php?view=register_company");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['reg_error'] = "An error occurred during registration. Please try again.";
                header("Location: ../index.php?view=register_company");
                exit();
            }
        }
    }
} else {
    // If accessed directly without POST, redirect to index
    header("Location: ../index.php");
    exit();
}