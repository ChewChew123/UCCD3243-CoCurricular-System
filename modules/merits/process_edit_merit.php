<?php
// File path: modules/merits/process_edit_merit.php
session_start();
require('../../database/db_connect.php');

// 1. 🌟 权限与登录安全检查
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Administrative privileges required.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $merit_id = $_POST['merit_id'];
    $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : NULL;
    $organizer = trim($_POST['organizer']);
    $hours = $_POST['hours'];
    $date = $_POST['date_completed'];
    $desc = trim($_POST['merit_description']);

    // 2. 基础验证
    if (empty($organizer) || empty($hours) || empty($date)) {
        header("Location: edit_merit.php?id=$merit_id&error=empty");
        exit();
    }

    // 3. 🌟 先获取这条记录到底属于哪个学生 (用于查重逻辑)
    $find_student_sql = "SELECT user_id FROM merits WHERE merit_id = ?";
    $fs_stmt = $conn->prepare($find_student_sql);
    $fs_stmt->bind_param("i", $merit_id);
    $fs_stmt->execute();
    $student_data = $fs_stmt->get_result()->fetch_assoc();
    $target_student_id = $student_data['user_id'];

    // 4. 🌟 重复数据检查 (排除掉当前正在编辑的这一条，且只检查该学生的记录)
    $check_sql = "SELECT * FROM merits WHERE user_id = ? AND organizer = ? AND date_completed = ? AND hours = ? AND merit_id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("isdsi", $target_student_id, $organizer, $date, $hours, $merit_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: edit_merit.php?id=$merit_id&error=duplicate");
        exit();
    }

    // 5. 🌟 更新数据库记录 (移除了 WHERE 里的 AND user_id = ?)
    // 因为 Admin 有权修改任何 ID 对应的记录
    $update_sql = "UPDATE merits SET 
                    event_id = ?, 
                    organizer = ?, 
                    hours = ?, 
                    date_completed = ?, 
                    merit_description = ? 
                   WHERE merit_id = ?";
    
    $u_stmt = $conn->prepare($update_sql);
    
    // 🌟 参数对应：i(event_id), s(organizer), d(hours), s(date), s(desc), i(merit_id)
    $u_stmt->bind_param("isdssi", $event_id, $organizer, $hours, $date, $desc, $merit_id);

    if ($u_stmt->execute()) {
        header("Location: index.php?status=success&action=edited");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    
    $u_stmt->close();
} else {
    header("Location: index.php");
    exit();
}

$conn->close();
?>