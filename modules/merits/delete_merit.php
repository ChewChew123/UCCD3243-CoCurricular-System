<?php
// File path: modules/merits/delete_merit.php
session_start();
require('../../database/db_connect.php');

// 1. 🌟 严格权限拦截：必须登录，且必须是 Admin 身份
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // 普通学生尝试访问直接踢走
    exit();
}

// 2. 获取 URL 中的 ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $merit_id = $_GET['id'];

    // 3. 🌟 修改：移除了 AND user_id = ?
    // 因为 Admin 需要根据 merit_id 删除任何学生的记录
    $sql = "DELETE FROM merits WHERE merit_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $merit_id);

    if ($stmt->execute()) {
        // 删除成功，带上状态码跳回列表
        header("Location: index.php?status=success&action=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    
    $stmt->close();
} else {
    // 如果没有提供 ID，直接退回
    header("Location: index.php");
    exit();
}

$conn->close();
?>