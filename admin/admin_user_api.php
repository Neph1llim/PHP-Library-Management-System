<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once '../config/database.php';
$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
try {
    if ($action === 'register') {
        $first = $_POST['first_name'];
        $last = $_POST['last_name'];
        $mid = $_POST['middle_name'] ?? '';
        $email = $_POST['email'];
        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $type = $_POST['user_type'] ?? 'User';
        $contact = $_POST['contact_number'] ?? '';
        $address = $_POST['address'] ?? '';
        $stmt = $db->prepare("CALL SP_RegisterUser(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first, $mid, $last, $email, $pass, $contact, $address, $type]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'profile') {
        $uid = (int)$_GET['user_id'];
        $stmt = $db->prepare("SELECT user_id, first_name, middle_name, last_name, email, contact_number, address, user_type FROM users WHERE user_id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) { echo json_encode(['success' => false, 'error' => 'Not found']); exit; }
        $full_name = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);
        $hist = $db->prepare("SELECT h.*, i.title FROM history h JOIN items i ON h.item_id = i.item_id WHERE h.user_id = ? ORDER BY h.borrowed_date DESC");
        $hist->execute([$uid]);
        $borrows = $hist->fetchAll(PDO::FETCH_ASSOC);
        $pen = $db->prepare("SELECT COALESCE(SUM(penalty_amount),0) FROM penalties p JOIN history h ON p.history_id = h.history_id WHERE h.user_id = ? AND p.payment_status = 'Unpaid'");
        $pen->execute([$uid]);
        $total_penalty = $pen->fetchColumn();
        $cnt = $db->prepare("SELECT COUNT(*) FROM history WHERE user_id = ? AND borrow_status = 'Borrowed'");
        $cnt->execute([$uid]);
        $borrowed_count = $cnt->fetchColumn();
        echo json_encode(['success' => true, 'full_name' => $full_name, 'email' => $user['email'], 'contact_number' => $user['contact_number'], 'address' => $user['address'], 'user_type' => $user['user_type'], 'total_penalty' => $total_penalty, 'borrowed_count' => $borrowed_count, 'borrows' => $borrows]);
    } elseif ($action === 'update_user') {
        $uid = (int)$_POST['user_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $contact = $_POST['contact_number'];
        $type = $_POST['user_type'];
        $address = $_POST['address'];
        $parts = explode(' ', $full_name, 3);
        $first = $parts[0];
        $last = $parts[count($parts)-1];
        $middle = count($parts) > 2 ? $parts[1] : '';
        $stmt = $db->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, address=?, user_type=? WHERE user_id=?");
        $stmt->execute([$first, $middle, $last, $email, $contact, $address, $type, $uid]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'deactivate') {
        $uid = (int)$_POST['user_id'];
        $stmt = $db->prepare("CALL SP_CancelAccount(?)");
        $stmt->execute([$uid]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}