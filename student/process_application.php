<?php
require_once '../config/db.php';
requireLogin('student');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $job_id = (int)$_POST['job_id'];
    $cover_letter = $conn->real_escape_string($_POST['cover_letter']);

    // 1. Kunin ang student data (ID at Default Resume)
    $student_data = $conn->query("SELECT id, full_name, resume_path FROM students WHERE user_id = '$user_id'")->fetch_assoc();
    $student_id = $student_data['id'];
    $student_name = $student_data['full_name'];
    $default_resume = $student_data['resume_path'];

    // 2. Double check kung nakapag-apply na
    $check = $conn->query("SELECT id FROM applications WHERE student_id = '$student_id' AND job_id = '$job_id'");
    
    if ($check->num_rows == 0) {
        
        $final_resume_path = $default_resume; // Default value

        // 3. FILE UPLOAD LOGIC
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['resume']['tmp_name'];
            $file_name = $_FILES['resume']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Limit sa PDF lang para safe
            if ($file_ext === 'pdf') {
                // Gumawa ng unique filename (e.g., RESUME_1715500000_5.pdf)
                $new_filename = "RESUME_" . time() . "_" . $student_id . "." . $file_ext;
                $upload_dir = "../uploads/resumes/";

                // Siguraduhin na exist ang folder, kung hindi, gagawin ito
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $final_resume_path = $new_filename;
                }
            }
        }

        // 4. I-save ang application (isinama ang resume_path)
        $sql = "INSERT INTO applications (student_id, job_id, cover_letter, resume_path, status, applied_at) 
                VALUES ('$student_id', '$job_id', '$cover_letter', '$final_resume_path', 'pending', NOW())";
        
        if ($conn->query($sql)) {
            
            // --- START COMPANY NOTIFICATION LOGIC ---
            $job_info = $conn->query("
                SELECT j.title, c.user_id as company_user_id 
                FROM job_listings j 
                JOIN companies c ON j.company_id = c.id 
                WHERE j.id = '$job_id'
            ")->fetch_assoc();

            if ($job_info) {
                $company_user_id = $job_info['company_user_id'];
                $job_title = $job_info['title'];
                $notif_msg = "$student_name submitted a new application for: $job_title";
                
                $conn->query("
                    INSERT INTO notifications (user_id, job_id, message, is_read, created_at) 
                    VALUES ('$company_user_id', '$job_id', '$notif_msg', 0, NOW())
                ");
            }
            // --- END COMPANY NOTIFICATION LOGIC ---

            header("Location: my_applications.php?success=1");
        } else {
            header("Location: job_details.php?id=$job_id&error=failed");
        }
    } else {
        header("Location: job_details.php?id=$job_id&error=already_applied");
    }
} else {
    header("Location: browse_jobs.php");
}
exit();