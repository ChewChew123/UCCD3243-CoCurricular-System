<?php
session_start();
require_once 'includes/db_connect.php';
// 引入刚刚下载的 FPDF 库
require_once 'includes/fpdf/fpdf.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ==========================================
// 1. 数据搜集阶段 (整合四大模块)
// ==========================================

// 抓取用户信息
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 抓取总 Merit Hours
$merit_sql = "SELECT SUM(hours) as total FROM merits WHERE user_id = ?";
$stmt = $conn->prepare($merit_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_merit = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 抓取活跃社团
$club_sql = "SELECT c.club_name, cm.member_role FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.user_id = ? AND cm.member_status = 'Active'";
$stmt = $conn->prepare($club_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$clubs = $stmt->get_result();

// 抓取荣誉与成就
$achiev_sql = "SELECT achievement_title, achievement_category, date_received FROM achievements WHERE user_id = ? ORDER BY date_received DESC LIMIT 5";
$stmt = $conn->prepare($achiev_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$achievements = $stmt->get_result();


// ==========================================
// 2. PDF 生成与排版阶段
// ==========================================

// 创建 A4 尺寸的 PDF 对象
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// --- 页眉 (Header) ---
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(0, 63, 135); // 主题蓝色 (#003f87)
$pdf->Cell(0, 10, 'OFFICIAL CO-CURRICULAR TRANSCRIPT', 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'The Academic Curator System - Universiti Tunku Abdul Rahman', 0, 1, 'C');
$pdf->Ln(10); // 换行

// --- 个人信息区域 ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(240, 240, 240); // 浅灰背景
$pdf->Cell(0, 8, ' STUDENT PROFILE', 0, 1, 'L', true);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 8, 'Full Name:', 0, 0);
$pdf->Cell(60, 8, $user['full_name'], 0, 0);
$pdf->Cell(40, 8, 'Student ID:', 0, 0);
$pdf->Cell(0, 8, str_pad($user['username'], 7, '0', STR_PAD_LEFT), 0, 1);

$pdf->Cell(40, 8, 'Programme:', 0, 0);
$pdf->Cell(60, 8, $user['programme'], 0, 0);
$pdf->Cell(40, 8, 'Academic Year:', 0, 0);
$pdf->Cell(0, 8, $user['academic_year'] ?? 'N/A', 0, 1);
$pdf->Ln(5);

// --- 核心亮点：Merit Hours ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, ' ENGAGEMENT METRICS', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 10, 'Total Accumulated Merit Hours:', 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(4, 120, 87); // 绿色
$pdf->Cell(0, 10, number_format($total_merit, 2) . ' Hours', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

// --- 社团参与 ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, ' ACTIVE CLUB MEMBERSHIPS', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 11);
if ($clubs->num_rows > 0) {
    while ($club = $clubs->fetch_assoc()) {
        $pdf->Cell(10, 8, '-', 0, 0, 'C');
        $pdf->Cell(100, 8, $club['club_name'], 0, 0);
        $pdf->Cell(0, 8, '[' . $club['member_role'] . ']', 0, 1);
    }
} else {
    $pdf->Cell(0, 8, 'No active club memberships recorded.', 0, 1);
}
$pdf->Ln(5);

// --- 荣誉与成就 ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, ' NOTABLE ACHIEVEMENTS', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 11);
if ($achievements->num_rows > 0) {
    while ($achv = $achievements->fetch_assoc()) {
        $pdf->Cell(10, 8, '*', 0, 0, 'C');
        $pdf->Cell(120, 8, $achv['achievement_title'] . ' (' . $achv['achievement_category'] . ')', 0, 0);
        $pdf->Cell(0, 8, $achv['date_received'], 0, 1);
    }
} else {
    $pdf->Cell(0, 8, 'No achievements recorded yet.', 0, 1);
}
$pdf->Ln(15);

// --- 底部声明 (提升真实感) ---
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'This is a computer-generated document from The Academic Curator system.', 0, 1, 'C');
$pdf->Cell(0, 5, 'Generated on: ' . date('d F Y, H:i A'), 0, 1, 'C');

// ==========================================
// 3. 输出 PDF (直接触发下载)
// ==========================================
// 'D' 代表 Download，'I' 代表在浏览器中预览
$pdf->Output('D', 'Co_Curricular_Transcript_' . $user['username'] . '.pdf');
?>