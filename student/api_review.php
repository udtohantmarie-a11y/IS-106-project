<?php
/**
 * api_review.php
 * Handles student feedback/ratings for companies
 */

require_once '../config/db.php';

// Siguraduhin na ang session ay nagsimula na para sa user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Validation: Siguraduhing kumpleto ang required data
    if (!isset($_POST['company_id'], $_POST['rating']) || empty($_POST['rating'])) {
        header("Location: browse_jobs.php");
        exit();
    }

    $company_id = (int)$_POST['company_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']); // Linisin ang white spaces

    // 1. Kunin ang student profile information
    $stmt_student = $conn->prepare("SELECT id, full_name FROM students WHERE user_id = ?");
    $stmt_student->bind_param("i", $user_id);
    $stmt_student->execute();
    $student = $stmt_student->get_result()->fetch_assoc();
    
    if (!$student) {
        die("Error: Student profile not found.");
    }

    $student_id = $student['id'];
    $student_name = $student['full_name'];

    /**
     * 2. ANTI-SPAM / DUPLICATE CHECK
     * Tinitingnan natin kung ang student na ito ay nagbigay na ng review 
     * sa kumpanyang ito sa loob ng huling 30 seconds (iwas double-click).
     */
    $check_stmt = $conn->prepare("
        SELECT id FROM company_reviews 
        WHERE student_id = ? AND company_id = ? 
        AND created_at > NOW() - INTERVAL 30 SECOND
    ");
    $check_stmt->bind_param("ii", $student_id, $company_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        // Redirect agad kung duplicate para hindi mag-insert ulit
        header("Location: company_profile.php?id=$company_id&status=duplicate");
        exit();
    }

    // 3. I-save ang Review sa company_reviews table
    $stmt_review = $conn->prepare("
        INSERT INTO company_reviews (company_id, student_id, rating, comment, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt_review->bind_param("iiis", $company_id, $student_id, $rating, $comment);

    if ($stmt_review->execute()) {
        
        /**
         * 4. NOTIFICATION LOGIC FOR COMPANY
         * Hanapin ang owner ng kumpanya para mapadalhan ng notif.
         */
        $stmt_co = $conn->prepare("SELECT user_id FROM companies WHERE id = ?");
        $stmt_co->bind_param("i", $company_id);
        $stmt_co->execute();
        $company_user = $stmt_co->get_result()->fetch_assoc();
        
        if ($company_user) {
            $target_user_id = $company_user['user_id'];
            $notif_message = "$student_name left a $rating-star rating and feedback on your profile.";

            /**
             * 5. I-insert sa notifications table.
             * Note: NULL ang job_id dahil feedback ito sa profile, hindi sa job post.
             */
            $stmt_notif = $conn->prepare("
                INSERT INTO notifications (user_id, job_id, message, type, is_read, created_at) 
                VALUES (?, NULL, ?, 'rating', 0, NOW())
            ");
            $stmt_notif->bind_param("is", $target_user_id, $notif_message);
            $stmt_notif->execute();
        }

        // Success redirect
        header("Location: company_profile.php?id=$company_id&status=success");
        exit();
    } else {
        // Error redirect
        header("Location: company_profile.php?id=$company_id&status=error");
        exit();
    }
} else {
    // Kung hindi POST request, ibalik sa browse
    header("Location: browse_jobs.php");
    exit();
}