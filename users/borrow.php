<?php
// user/borrow.php - processes borrow via stored procedure
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$user_id = $_POST['user_id'] ?? 0;
$book_id = $_POST['book_id'] ?? 0;
$loan_days = (int)($_POST['loan_days'] ?? 14);

// Security: ensure the user_id matches session
if ($user_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

if (!$book_id) {
    echo json_encode(['success' => false, 'error' => 'Missing book ID']);
    exit;
}

$db = getDB();
try {
    // Call the existing stored procedure SP_CheckOutBook
    $stmt = $db->prepare("CALL SP_CheckOutBook(:user_id, :item_id, :loan_days)");
    $stmt->execute([
        'user_id' => $user_id,
        'item_id' => $book_id,
        'loan_days' => $loan_days
    ]);
    // Fetch result (new_history_id)
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($result && isset($result['new_history_id'])) {
        echo json_encode(['success' => true, 'history_id' => $result['new_history_id']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Borrow failed – no history ID returned']);
    }
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    // Provide user-friendly messages for known constraints
    if (strpos($errorMsg, 'Unpaid penalties') !== false) {
        $errorMsg = 'You have unpaid penalties. Cannot borrow.';
    } elseif (strpos($errorMsg, 'No copies available') !== false) {
        $errorMsg = 'This book is currently unavailable.';
    } elseif (strpos($errorMsg, 'Item not found') !== false) {
        $errorMsg = 'Book not found.';
    }
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
$db = null;