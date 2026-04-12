<?php
// File path: modules/achievements/process_edit.php
session_start();
require('../../includes/db_connect.php');

// Security check: Ensure user is logged in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $achievement_id = $_POST['achievement_id'];
    $title = $_POST['achievement_title'];
    $category = $_POST['achievement_category'];
    $level = $_POST['level'];
    $issuer = $_POST['issuer'];
    $date = $_POST['date_received'];
    $description = $_POST['achievement_description']; // Fixed: Added description
    $event_id = !empty($_POST['event_id']) ? $_POST['event_id'] : null;

    // 1. Retrieve the existing image to handle replacement
    $sql_img = "SELECT certificate_image FROM achievements WHERE achievement_id = ? AND user_id = ?";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bind_param("ii", $achievement_id, $user_id);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();
    $old_data = $res_img->fetch_assoc();
    $old_image = $old_data['certificate_image'];

    $new_image_name = $old_image; // Keep current image by default

    // 2. Handle New Image Upload if provided
    if (isset($_FILES['certificate_image']) && $_FILES['certificate_image']['error'] == 0) {
        $target_dir = "../../uploads/certificates/";
        
        // Security check: Limit file size to 2MB
        if ($_FILES['certificate_image']['size'] > 2 * 1024 * 1024) {
            die("Error: New file exceeds 2MB limit.");
        }

        $file_ext = strtolower(pathinfo($_FILES["certificate_image"]["name"], PATHINFO_EXTENSION));
        $temp_name = "cert_" . time() . "_" . uniqid() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES["certificate_image"]["tmp_name"], $target_dir . $temp_name)) {
            $new_image_name = $temp_name;
            
            // Delete the old file from server to save storage
            if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                unlink($target_dir . $old_image);
            }
        }
    }

    // 3. Update Database Record
    $sql_update = "UPDATE achievements SET 
                    achievement_title = ?, 
                    achievement_category = ?, 
                    level = ?, 
                    issuer = ?, 
                    date_received = ?, 
                    certificate_image = ?, 
                    achievement_description = ?, 
                    event_id = ? 
                   WHERE achievement_id = ? AND user_id = ?";
    
    $stmt_up = $conn->prepare($sql_update);
    // Data types: string x7, int x1, int x2 (ID and UserID)
    $stmt_up->bind_param("sssssssiii", $title, $category, $level, $issuer, $date, $new_image_name, $description, $event_id, $achievement_id, $user_id);

    if ($stmt_up->execute()) {
        header("Location: index.php?msg=updated");
    } else {
        echo "Update Error: " . $conn->error;
    }

    $stmt_up->close();
    $conn->close();
} else {
    header("Location: index.php");
    exit();
}
?>