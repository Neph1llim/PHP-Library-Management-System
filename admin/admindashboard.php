<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com' || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
$db = getDB();

$totalBooks = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE email != 'admin@admin.com'")->fetchColumn();
$activeBorrows = $db->query("SELECT COUNT(*) FROM history WHERE borrow_status = 'Borrowed'")->fetchColumn();
$overdueBorrows = $db->query("SELECT COUNT(*) FROM history WHERE due_date < CURDATE() AND borrow_status = 'Borrowed'")->fetchColumn();
$totalPenaltiesUnpaid = $db->query("SELECT COALESCE(SUM(penalty_amount),0) FROM penalties WHERE payment_status = 'Unpaid'")->fetchColumn();

$db = null;
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - LIBRAR-E</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>
    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">
                <h2>Dashboard</h2>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></p>
            </div>
        </div>
        <div class="content">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-number"><?= $totalBooks ?></div><div class="stat-label">Total Books</div></div>
                <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-number"><?= $totalUsers ?></div><div class="stat-label">Registered Users</div></div>
                <div class="stat-card"><div class="stat-icon">📖</div><div class="stat-number"><?= $activeBorrows ?></div><div class="stat-label">Active Borrows</div></div>
                <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-number"><?= $overdueBorrows ?></div><div class="stat-label">Overdue Books</div></div>
                <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-number">₱<?= number_format($totalPenaltiesUnpaid, 2) ?></div><div class="stat-label">Unpaid Penalties</div></div>
            </div>
            <div class="action-buttons">
                <a href="manage_books.php" class="action-btn">📘 Manage Books</a>
                <a href="manage_users.php" class="action-btn">👤 Manage Users</a>
                <a href="manage_borrows.php" class="action-btn">🔄 Manage Borrows</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>