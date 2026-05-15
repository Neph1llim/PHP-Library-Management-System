<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';
$db = getDB();
$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $isbn = $_POST['isbn'] ?? '';
        $qty = (int)$_POST['qty'];
        $cat_id = (int)$_POST['cat_id'];
        $shelf = $_POST['shelf'] ?? '';
        $publisher = $_POST['publisher'] ?? '';
        $year = $_POST['year'] ?? null;
        $cover_path = null;

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/covers/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $filename);
            $cover_path = 'uploads/covers/' . $filename;
        }

        $stmt = $db->prepare("CALL SP_AddBook(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author, $isbn, $publisher, $year, $cat_id, $qty, $shelf]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($result && isset($result['new_item_id']) && $cover_path) {
            $upd = $db->prepare("UPDATE items SET cover_image = ? WHERE item_id = ?");
            $upd->execute([$cover_path, $result['new_item_id']]);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'edit') {
        $id = (int)$_POST['item_id'];
        $title = $_POST['title'];
        $author = $_POST['author'];
        $isbn = $_POST['isbn'] ?? '';
        $qty = (int)$_POST['qty'];
        $cat_id = (int)$_POST['cat_id'];
        $shelf = $_POST['shelf'] ?? '';
        $publisher = $_POST['publisher'] ?? '';
        $year = $_POST['year'] ?? null;
        $status = $_POST['status'];

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/covers/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $filename);
            $cover_path = 'uploads/covers/' . $filename;
            $upd = $db->prepare("UPDATE items SET cover_image = ? WHERE item_id = ?");
            $upd->execute([$cover_path, $id]);
        }

        $stmt = $db->prepare("CALL SP_EditBook(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $title, $author, $isbn, $publisher, $year, $cat_id, $qty, $shelf, $status]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['item_id'];
        $stmt = $db->prepare("CALL SP_RemoveBook(?)");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}