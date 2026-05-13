<?php
// user/get_book.php - returns single book data as JSON
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$book_id = $_GET['id'] ?? 0;
if (!$book_id) {
    echo json_encode(['success' => false, 'error' => 'Missing book ID']);
    exit;
}

$db = getDB();
$sql = "SELECT i.item_id, i.title, i.author, i.isbn, i.publisher, i.publication_year AS year,
               i.quantity_available AS quantity, c.category_name AS category
        FROM items i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.item_id = :id";
$stmt = $db->prepare($sql);
$stmt->execute(['id' => $book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo json_encode(['success' => false, 'error' => 'Book not found']);
} else {
    echo json_encode(['success' => true] + $book);
}
$db = null;