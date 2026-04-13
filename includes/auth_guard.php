<?php
// Function to check if the current user is an admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to block non-admins from sensitive actions (Add/Edit/Delete)
function restrictToAdmin() {
    if (!isAdmin()) {
        // Redirect back to dashboard with a warning message
        header("Location: /UCCD3243-CoCurricular-System/index.php?error=unauthorized");
        exit();
    }
}
?>