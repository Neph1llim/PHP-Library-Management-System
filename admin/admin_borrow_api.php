<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once '../config/database.php';
$db = getDB();
$action = $_POST['action'] ?? '';
try {
    if ($action === 'return_book') {
        $history_id = (int)$_POST['history_id'];
        $stmt = $db->prepare("CALL SP_ReturnBook(?, 0)");
        $stmt->execute([$history_id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'cancel_borrow') {
        $history_id = (int)$_POST['history_id'];
        $db->prepare("DELETE FROM history WHERE history_id = ? AND borrow_status != 'Returned'")->execute([$history_id]);
        $db->prepare("UPDATE items i JOIN history h ON i.item_id = h.item_id SET i.quantity_available = i.quantity_available + 1, i.item_status = 'Available' WHERE h.history_id = ?")->execute([$history_id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'add_fine') {
        $history_id = (int)$_POST['history_id'];
        $amount = (float)$_POST['amount'];
        $stmt = $db->prepare("INSERT INTO penalties (history_id, penalty_amount, payment_status) VALUES (?, ?, 'Unpaid')");
        $stmt->execute([$history_id, $amount]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'mark_paid') {
        $penalty_id = (int)$_POST['penalty_id'];
        $stmt = $db->prepare("UPDATE penalties SET payment_status = 'Paid', payment_date = CURDATE() WHERE penalty_id = ?");
        $stmt->execute([$penalty_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}