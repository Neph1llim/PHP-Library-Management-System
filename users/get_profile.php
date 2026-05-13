<?php
// users/get_profile.php
// Returns the logged-in user's profile info, total unpaid penalty, and active borrow count.
session_start();
header('Content-Type: application/json');
 
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
 
require_once '../config/database.php';
 
// Only allow fetching own profile
$user_id = (int)$_SESSION['user_id'];
 
$db = getDB();
try {
    // 1. Basic user info
    $stmt = $db->prepare(
        "SELECT TRIM(CONCAT(first_name, ' ',
                            COALESCE(middle_name, ''), ' ',
                            last_name)) AS full_name,
                email, contact_number, address
         FROM users
         WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
 
    // 2. Total unpaid penalty amount
    // Fine rule: ₱300 per day late (calculated by stored procedure on return).
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(p.penalty_amount), 0) AS total_penalty
         FROM penalties p
         JOIN history h ON h.history_id = p.history_id
         WHERE h.user_id = ? AND p.payment_status = 'Unpaid'"
    );
    $stmt->execute([$user_id]);
    $penRow = $stmt->fetch(PDO::FETCH_ASSOC);
 
    // 3. Count of currently borrowed (not yet returned) books
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS borrowed_count
         FROM history
         WHERE user_id = ? AND borrow_status = 'Borrowed'"
    );
    $stmt->execute([$user_id]);
    $borrowRow = $stmt->fetch(PDO::FETCH_ASSOC);
 
    echo json_encode([
        'success'        => true,
        'full_name'      => trim($user['full_name']),
        'email'          => $user['email'],
        'contact_number' => $user['contact_number'],
        'address'        => $user['address'],
        'total_penalty'  => (float)$penRow['total_penalty'],
        'borrowed_count' => (int)$borrowRow['borrowed_count'],
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$db = null;