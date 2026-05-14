<?php
// users/borrowed_books.php
// Shows all books borrowed by the logged-in user (all statuses).
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
 
require_once '../config/database.php';
 
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$user_id   = (int)$_SESSION['user_id'];
 
$db = getDB();
 
// Fetch every borrow record for this user, newest first.
// Also compute whether the record is overdue (still Borrowed but past due date).
$stmt = $db->prepare(
    "SELECT h.history_id,
            i.item_id,
            i.title,
            i.author,
            i.isbn,
            h.borrowed_date,
            h.due_date,
            h.return_date,
            h.borrow_status,
            CASE
                WHEN h.borrow_status = 'Borrowed' AND CURRENT_DATE > h.due_date
                THEN 1 ELSE 0
            END AS is_overdue
     FROM history h
     JOIN items i ON i.item_id = h.item_id
     WHERE h.user_id = ?
     ORDER BY h.borrowed_date DESC, h.history_id DESC"
);
$stmt->execute([$user_id]);
$borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Books — LIBRAR-E</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
 
<header>
    <div class="logo">
        <svg width="44" height="44" viewBox="0 0 52 52" fill="none">
            <path d="M8 38V14a2 2 0 012-2h12c3 0 4 2 4 2s1-2 4-2h12a2 2 0 012 2v24" stroke="#1a2744" stroke-width="2.2"/>
            <line x1="26" y1="14" x2="26" y2="38" stroke="#1a2744" stroke-width="2.2"/>
            <path d="M8 38h16s1 2 2 2 2-2 2-2h16" stroke="#1a2744" stroke-width="2.2"/>
        </svg>
        <div class="brand">
            <h1>LIBRAR-E</h1>
            <p>Batangas State University</p>
        </div>
    </div>
    <div class="user-info">
        Welcome, <strong><?= $full_name ?></strong>
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</header>
 
<main>
    <a href="dashboard.php" class="back-link">&#8592; Back to Catalog</a>
 
    <div class="page-title">My Borrowed Books</div>
    <div class="page-subtitle">
        Showing all borrow records for your account.
    </div>
 
    <?php if (empty($borrows)): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <p>You haven't borrowed any books yet.</p>
        </div>
    <?php else: ?>
        <div class="borrowed-list">
            <?php foreach ($borrows as $b):
                // Determine badge
                if ($b['borrow_status'] === 'Returned') {
                    $badgeClass = 'status-returned';
                    $badgeText  = 'Returned';
                } elseif ($b['borrow_status'] === 'Overdue' || $b['is_overdue']) {
                    $badgeClass = 'status-overdue';
                    $badgeText  = 'Overdue';
                } else {
                    $badgeClass = 'status-borrowed';
                    $badgeText  = 'Borrowed';
                }
                $dueCls = ($b['is_overdue'] && $b['borrow_status'] !== 'Returned') ? 'overdue' : '';
            ?>
                <div class="borrowed-card">
                    <!-- Book "cover" — uses emoji placeholder; swap img tag if you have covers -->
                    <div class="borrowed-cover">📖</div>
 
                    <div class="borrowed-info">
                        <div class="borrowed-title"><?= htmlspecialchars($b['title']) ?></div>
                        <div class="borrowed-author"><?= htmlspecialchars($b['author']) ?></div>
 
                        <div class="borrowed-meta">
                            <div class="meta-item">
                                <span class="meta-label">Borrowed</span>
                                <span class="meta-value"><?= htmlspecialchars($b['borrowed_date']) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Return by</span>
                                <span class="meta-value <?= $dueCls ?>"><?= htmlspecialchars($b['due_date']) ?></span>
                            </div>
                            <?php if ($b['return_date']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Returned on</span>
                                <span class="meta-value"><?= htmlspecialchars($b['return_date']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <span class="meta-label">Status</span>
                                <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
 
</body>
</html>