<?php
// File path: modules/achievements/delete_achievement.php
session_start();
require('../../includes/db_connect.php'); 

// 1. 🌟 严格权限拦截：必须登录，且必须是 Admin 身份才能执行删除操作
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $achievement_id = $_GET['id'];

    // 2. 🌟 进阶优化：在删除数据库记录前，先把服务器里的证书图片文件删掉
    $img_sql = "SELECT certificate_image FROM achievements WHERE achievement_id = ?";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $achievement_id);
    $img_stmt->execute();
    $img_res = $img_stmt->get_result();
    
    if ($row = $img_res->fetch_assoc()) {
        if (!empty($row['certificate_image'])) {
            $file_path = "../../uploads/certificates/" . $row['certificate_image'];
            // 检查文件是否存在，存在就删除 (unlink)
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    $img_stmt->close();

    // 3. 🌟 修改：移除了 AND user_id = ?，因为 Admin 可以删除任何人的记录
    $delete_sql = "DELETE FROM achievements WHERE achievement_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $achievement_id);

    if ($stmt->execute()) {
        header("Location: index.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}

$conn->close();
?>