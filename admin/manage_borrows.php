<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com' || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
$db = getDB();
$borrows = $db->query(
    "SELECT h.history_id, h.user_id, h.item_id,
            CONCAT(u.first_name,' ',COALESCE(u.middle_name,''),' ',u.last_name) AS full_name,
            u.email, i.title AS book_title, i.author,
            h.borrowed_date, h.due_date, h.return_date, h.borrow_status,
            COALESCE(p.penalty_amount,0) AS penalty_amount,
            p.penalty_id, p.payment_status
     FROM history h
     JOIN users u ON u.user_id = h.user_id
     JOIN items i ON i.item_id = h.item_id
     LEFT JOIN penalties p ON p.history_id = h.history_id
     ORDER BY h.borrowed_date DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$db = null;
$current_page = 'borrows';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Borrows - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>
    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">
                <h2>Manage Borrows</h2>
                <p>Track and manage borrowed books</p>
            </div>
        </div>
        <div class="content">
            <div class="toolbar">
                <input class="search-bar" id="borrowSearch" placeholder="Search by name or email…" oninput="filterBorrows()">
                <select class="filter-select" id="borrowFilter" onchange="filterBorrows()">
                    <option value="">All Status</option>
                    <option>Borrowed</option>
                    <option>Returned</option>
                    <option>Overdue</option>
                </select>
            </div>
            <div class="table-wrap">
                <table id="borrowsTable">
                    <thead>
                        <tr><th>User</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($borrows as $b): $statusClass = $b['borrow_status']==='Returned'?'badge-returned':($b['borrow_status']==='Overdue'?'badge-overdue':'badge-borrowed'); ?>
                        <tr data-status="<?= $b['borrow_status'] ?>" data-name="<?= strtolower($b['full_name']) ?>" data-email="<?= strtolower($b['email']) ?>">
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['book_title']) ?></td>
                            <td><?= $b['borrowed_date'] ?></td>
                            <td><?= $b['due_date'] ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $b['borrow_status'] ?></span></td>
                            <td>
                                <?php if($b['borrow_status'] !== 'Returned'): ?>
                                <button class="dots-btn" onclick="openBorrowActions(<?= $b['history_id'] ?>, '<?= addslashes($b['full_name']) ?>', '<?= addslashes($b['book_title']) ?>', '<?= $b['borrow_status'] ?>', <?= $b['penalty_amount'] ?>, <?= $b['penalty_id'] ?: 0 ?>)">Actions</button>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="borrowActionModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('borrowActionModal')">&times;</button>
<div id="borrowActionContent"></div>
<div class="confirm-buttons">
<button class="btn-green" onclick="doMarkReturned()">Mark Returned</button>
<button class="btn-red" onclick="openAddFine()">Add Fine</button>
<button class="btn-cancel" onclick="closeModal('borrowActionModal')">Close</button>
</div>
</div>
</div>

<div id="addFineModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('addFineModal')">&times;</button>
<h3>Add Fine</h3>
<div class="field"><label>Amount (₱)</label><input type="number" id="fineAmount" step="0.01"></div>
<button class="btn-red" onclick="submitAddFine()">Add Fine</button>
</div>
</div>

<div id="paidModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('paidModal')">&times;</button>
<h3>Mark as Paid</h3>
<div class="field"><label>Paid Amount (₱)</label><input type="number" id="paidAmount" step="0.01"></div>
<button class="btn-green" onclick="submitPaid()">Confirm Payment</button>
</div>
</div>

<script>
let activeBorrow = { historyId: null, penaltyId: null, penaltyAmount: 0 };
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); }));
function filterBorrows() { let q = document.getElementById('borrowSearch').value.toLowerCase(); let st = document.getElementById('borrowFilter').value; document.querySelectorAll('#borrowsTable tbody tr').forEach(r => { let nameOk = r.dataset.name?.includes(q) || r.dataset.email?.includes(q) || r.innerText.toLowerCase().includes(q); let stOk = !st || r.dataset.status === st; r.style.display = (nameOk && stOk) ? '' : 'none'; }); }
function openBorrowActions(histId, userName, bookTitle, status, penaltyAmt, penaltyId) { activeBorrow = { historyId: histId, penaltyId: penaltyId, penaltyAmount: penaltyAmt, userName, bookTitle, status }; document.getElementById('borrowActionContent').innerHTML = `<strong>${bookTitle}</strong><br>User: ${userName}<br>Status: ${status}<br>Penalty: ₱${parseFloat(penaltyAmt).toFixed(2)}`; openModal('borrowActionModal'); }
async function doMarkReturned() { let fd = new FormData(); fd.append('action','return_book'); fd.append('history_id', activeBorrow.historyId); fd.append('penalty_per_day', 0); let res = await fetch('admin_borrow_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('Marked as returned'); location.reload(); } else alert(data.error); }
function openAddFine() { closeModal('borrowActionModal'); openModal('addFineModal'); }
async function submitAddFine() { let amount = document.getElementById('fineAmount').value; if(!amount || amount<=0) { alert('Enter valid amount'); return; } let fd = new FormData(); fd.append('action','add_fine'); fd.append('history_id', activeBorrow.historyId); fd.append('amount', amount); let res = await fetch('admin_borrow_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('Fine added'); closeModal('addFineModal'); location.reload(); } else alert(data.error); }
function openPaidModal() { closeModal('borrowActionModal'); document.getElementById('paidAmount').value = activeBorrow.penaltyAmount; openModal('paidModal'); }
async function submitPaid() { if(!activeBorrow.penaltyId) { alert('No penalty record'); return; } let fd = new FormData(); fd.append('action','mark_paid'); fd.append('penalty_id', activeBorrow.penaltyId); let res = await fetch('admin_borrow_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('Payment recorded'); closeModal('paidModal'); location.reload(); } else alert(data.error); }
window.openPaidModal = openPaidModal;
</script>
</body>
</html>