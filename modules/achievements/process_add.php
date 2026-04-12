<?php
// File path: modules/achievements/process_add.php
session_start();
require('../../includes/db_connect.php');

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['achievement_title'];
    $category = $_POST['achievement_category'];
    $level = $_POST['level'];
    $issuer = $_POST['issuer'];
    $date = $_POST['date_received'];
    $description = $_POST['achievement_description']; // Fixed: Added description
    
    // Check for linked event, store NULL if empty
    $event_id = !empty($_POST['event_id']) ? $_POST['event_id'] : null;

    // --- Core: Handle Image Upload ---
    $image_name = null; 
    if (isset($_FILES['certificate_image']) && $_FILES['certificate_image']['error'] == 0) {
        $target_dir = "../../uploads/certificates/";
        
        // Auto-create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Security check: Limit file size to 2MB
        if ($_FILES['certificate_image']['size'] > 2 * 1024 * 1024) {
            die("Error: File size exceeds 2MB limit.");
        }

        // Generate a unique filename to prevent overwriting
        $file_ext = strtolower(pathinfo($_FILES["certificate_image"]["name"], PATHINFO_EXTENSION));
        $image_name = "cert_" . time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $image_name;

        // Verify if the file is an actual image
        $check = getimagesize($_FILES["certificate_image"]["tmp_name"]);
        if($check !== false) {
            move_uploaded_file($_FILES["certificate_image"]["tmp_name"], $target_file);
        } else {
            $image_name = null; 
        }
    }

    // --- Core: Save to Database ---
    $sql = "INSERT INTO achievements (user_id, achievement_title, achievement_category, level, issuer, date_received, event_id, certificate_image, achievement_description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    // Data types: int, string, string, string, string, string, int, string, string
    $stmt->bind_param("isssssiss", $user_id, $title, $category, $level, $issuer, $date, $event_id, $image_name, $description);

    if ($stmt->execute()) {
        // Redirect back to index with success message
        header("Location: index.php?msg=added");
    } else {
        echo "Database Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>