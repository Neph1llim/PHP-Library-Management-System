<?php
// users/dashboard.php - Library catalog homepage
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
 
require_once '../config/database.php';
 
$full_name    = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$user_id      = (int)$_SESSION['user_id'];
$full_name_js = json_encode($full_name);
 
$search = trim($_GET['search'] ?? '');
$db = getDB();
 
$sql = "SELECT i.item_id, i.title, i.author, i.quantity_available,
               i.item_status, c.category_name
        FROM items i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.title LIKE :search OR i.author LIKE :search
        ORDER BY i.title";
$stmt = $db->prepare($sql);
$stmt->execute(['search' => "%$search%"]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
$db = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog — LIBRAR-E</title>
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
        Welcome,
        <!-- Clickable name triggers profile popup -->
        <button class="user-name-btn" id="openProfileBtn"><?= $full_name ?></button>
        <a href="../auth/logout.php" class="logout">Logout</a>
    </div>
</header>
 
<main>
    <div class="search-section">
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" placeholder="Search by title or author..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>
 
    <div class="books-grid" id="booksGrid">
        <?php if (empty($books)): ?>
            <p style="text-align:center; grid-column:1/-1;">No books found.</p>
        <?php else: ?>
            <?php foreach ($books as $book):
                $available   = $book['quantity_available'];
                $statusClass = $available > 0 ? 'available' : 'unavailable';
                $statusText  = $available > 0 ? "Available – $available" : "Unavailable";
            ?>
                <div class="book-card" data-id="<?= $book['item_id'] ?>">
                    <div class="book-cover">📖</div>
                    <div class="book-info">
                        <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                        <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
                        <div class="book-category"><?= htmlspecialchars($book['category_name']) ?></div>
                        <div class="availability <?= $statusClass ?>"><?= $statusText ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
 
<!-- ── Book Detail Modal ───────────────────────────── -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="modalTitle">Book Details</h3>
        <div id="modalBody"></div>
        <button id="borrowFromDetailBtn" class="borrow-btn">Borrow</button>
        <div id="modalError" class="error-msg" style="display:none;"></div>
    </div>
</div>
 
<!-- ── Confirm Borrow Modal ────────────────────────── -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Borrow</h3>
        <div id="confirmBody"></div>
        <div class="confirm-buttons">
            <button id="confirmBorrowBtn" class="btn-confirm">Confirm Borrow</button>
            <button id="cancelBorrowBtn"  class="btn-cancel">Cancel</button>
        </div>
        <div id="confirmError" class="error-msg" style="display:none;"></div>
    </div>
</div>
 
<!-- ── User Profile Modal (NEW) ───────────────────── -->
<div id="profileModal" class="modal profile-modal">
    <div class="modal-content">
        <span class="close-modal" id="closeProfileBtn">&times;</span>
        <div id="profileContent">
            <div class="profile-loading">Loading profile…</div>
        </div>
    </div>
</div>
 
<script>
/* ── Helpers ─────────────────────────────────────────── */
function showModal(id) { document.getElementById(id).classList.add('active'); }
function hideModal(id) { document.getElementById(id).classList.remove('active'); }
function escapeHtml(str) {
    if (!str && str !== 0) return '—';
    return String(str).replace(/[&<>]/g, m => m==='&'?'&amp;':m==='<'?'&lt;':'&gt;');
}
 
/* ── Close modals on backdrop click or × ─────────────── */
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => { if (e.target === modal) hideModal(modal.id); });
    modal.querySelector('.close-modal')?.addEventListener('click', () => hideModal(modal.id));
});
 
/* ── Book cards  ───────────────────────── */
let currentBookId = null;
 
document.querySelectorAll('.book-card').forEach(card => {
    card.addEventListener('click', () => { if (card.dataset.id) showBookDetails(card.dataset.id); });
});
 
async function showBookDetails(bookId) {
    try {
        const response = await fetch(`get_book.php?id=${bookId}`);
        const book     = await response.json();
        if (!book.success) throw new Error(book.error);
        currentBookId = book.item_id;
 
        const availHtml = book.quantity > 0
            ? `<span style="color:var(--ok)">Available – ${book.quantity}</span>`
            : `<span style="color:var(--err)">Unavailable</span>`;
 
        document.getElementById('modalBody').innerHTML = `
            <div class="detail-row"><span class="detail-label">Title:</span>     ${escapeHtml(book.title)}</div>
            <div class="detail-row"><span class="detail-label">Author:</span>    ${escapeHtml(book.author)}</div>
            <div class="detail-row"><span class="detail-label">Category:</span>  ${escapeHtml(book.category)}</div>
            <div class="detail-row"><span class="detail-label">Publisher:</span> ${escapeHtml(book.publisher)}</div>
            <div class="detail-row"><span class="detail-label">Year:</span>      ${escapeHtml(book.year)}</div>
            <div class="detail-row"><span class="detail-label">Availability:</span> ${availHtml}</div>
        `;
 
        const borrowBtn = document.getElementById('borrowFromDetailBtn');
        if (book.quantity <= 0) {
            borrowBtn.classList.add('disabled');
            borrowBtn.disabled   = true;
            borrowBtn.textContent = 'Unavailable';
        } else {
            borrowBtn.classList.remove('disabled');
            borrowBtn.disabled    = false;
            borrowBtn.textContent = 'Borrow';
        }
        document.getElementById('modalError').style.display = 'none';
        showModal('detailModal');
    } catch (err) { alert('Error loading book details: ' + err.message); }
}
 
document.getElementById('borrowFromDetailBtn').addEventListener('click', async () => {
    if (!currentBookId) return;
    const res  = await fetch(`get_book.php?id=${currentBookId}`);
    const book = await res.json();
    if (!book.success || book.quantity <= 0) {
        document.getElementById('modalError').innerText     = 'This book is no longer available.';
        document.getElementById('modalError').style.display = 'block';
        return;
    }
    const today   = new Date();
    const dueDate = new Date();
    dueDate.setDate(today.getDate() + 14);
    const fmt = d => d.toISOString().slice(0, 10);
 
    document.getElementById('confirmBody').innerHTML = `
        <div class="detail-row"><span class="detail-label">Book:</span>        ${escapeHtml(book.title)}</div>
        <div class="detail-row"><span class="detail-label">Borrower:</span>    ${<?= $full_name_js ?>}</div>
        <div class="detail-row"><span class="detail-label">Borrow Date:</span> ${fmt(today)}</div>
        <div class="detail-row"><span class="detail-label">Due Date:</span>    ${fmt(dueDate)}</div>
        <p style="margin-top:12px;font-size:.85rem;color:#c0392b;">⚠️ Please return the book on or before the due date.</p>
    `;
    document.getElementById('confirmError').style.display = 'none';
    hideModal('detailModal');
    showModal('confirmModal');
});
 
document.getElementById('confirmBorrowBtn').addEventListener('click', async () => {
    if (!currentBookId) return;
    try {
        const formData = new FormData();
        formData.append('book_id',   currentBookId);
        formData.append('user_id',   <?= $user_id ?>);
        formData.append('loan_days', 14);
        const res    = await fetch('borrow.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) { alert('Book borrowed successfully!'); location.reload(); }
        else {
            document.getElementById('confirmError').innerText     = result.error || 'Borrow failed.';
            document.getElementById('confirmError').style.display = 'block';
        }
    } catch (err) {
        document.getElementById('confirmError').innerText     = 'Network error.';
        document.getElementById('confirmError').style.display = 'block';
    }
});
 
document.getElementById('cancelBorrowBtn').addEventListener('click', () => hideModal('confirmModal'));
 
/* ── User Profile Modal ─────────────────── */
document.getElementById('openProfileBtn').addEventListener('click', async () => {
    showModal('profileModal');
    const content = document.getElementById('profileContent');
    content.innerHTML = '<div class="profile-loading">Loading profile…</div>';
 
    try {
        const res  = await fetch(`get_profile.php?user_id=<?= $user_id ?>`);
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
 
        const penaltyHtml = data.total_penalty > 0
            ? `<span class="profile-row-value penalty">₱${parseFloat(data.total_penalty).toFixed(2)}</span>`
            : `<span class="profile-row-value ok">N/A</span>`;
 
        const initial = (data.full_name || 'U')[0].toUpperCase();
 
        content.innerHTML = `
            <div class="profile-header">
                <div class="profile-avatar">${initial}</div>
                <div>
                    <div class="profile-name">${escapeHtml(data.full_name)}</div>
                    <div class="profile-email">${escapeHtml(data.email)}</div>
                </div>
            </div>
            <div class="profile-row">
                <span class="profile-row-label">Contact</span>
                <span class="profile-row-value">${escapeHtml(data.contact_number) || 'N/A'}</span>
            </div>
            <div class="profile-row">
                <span class="profile-row-label">Address</span>
                <span class="profile-row-value">${escapeHtml(data.address) || 'N/A'}</span>
            </div>
            <div class="profile-row">
                <span class="profile-row-label">Penalties</span>
                ${penaltyHtml}
            </div>
            <div class="profile-row">
                <span class="profile-row-label">Borrowed Books</span>
                <span class="profile-row-value">${data.borrowed_count}</span>
                <a href="borrowed_books.php" class="view-btn">View</a>
            </div>
        `;
    } catch (err) {
        content.innerHTML = `<p style="color:var(--err);text-align:center;">${err.message}</p>`;
    }
});
</script>
</body>
</html>