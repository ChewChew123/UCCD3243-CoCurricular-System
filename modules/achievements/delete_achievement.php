<?php
// 文件路径: modules/achievements/delete_achievement.php
session_start();
require('../../includes/db_connect.php'); 

// 1. 安全检查：没登录的踢出去
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 检查网址里有没有传 ID 过来 (比如 delete_achievement.php?id=3)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $achievement_id = $_GET['id'];

    // 🔥 核心安全逻辑：必须加上 user_id = ?，绝不允许别人通过改 URL 来删你的奖项！
    $delete_sql = "DELETE FROM achievements WHERE achievement_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    
    // 绑定参数: 两个都是整数 (i, i)
    $stmt->bind_param("ii", $achievement_id, $user_id);

    if ($stmt->execute()) {
        // 删除成功，带着 msg=deleted 跳回主页
        header("Location: index.php?msg=deleted");
        exit();
    } else {
        // 如果出错（比如数据库问题），可以加个错误提示跳回去
        echo "Error deleting record: " . $conn->error;
    }

} else {
    // 如果有人直接在浏览器输入 delete_achievement.php (没带 ID)，直接踢回主页
    header("Location: index.php");
    exit();
}
?>