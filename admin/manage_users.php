<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com' || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
$db = getDB();
$users = $db->query("SELECT user_id, first_name, middle_name, last_name, email, contact_number, address, user_type, registration_date FROM users ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
$db = null;
$current_page = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>
    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">
                <h2>Manage Users</h2>
                <p>View and manage library users</p>
            </div>
        </div>
        <div class="content">
            <div class="toolbar">
                <input class="search-bar" id="userSearch" placeholder="Search users…" oninput="filterUsers()">
                <button class="btn btn-add" onclick="openModal('addUserModal')">Add User</button>
            </div>
            <div class="table-wrap">
                <table id="usersTable">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): $full = trim($u['first_name'].' '.($u['middle_name']?$u['middle_name'][0].'. ':'').$u['last_name']); ?>
                        <tr>
                            <td><?= htmlspecialchars($full) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge <?= $u['user_type']==='Admin'?'badge-admin':'badge-student' ?>"><?= $u['user_type']==='User'?'Student':'Admin' ?></span></td>
                            <td><?= $u['registration_date'] ?></td>
                            <td><button class="dots-btn" onclick="openUserProfile(<?= $u['user_id'] ?>)">View</button> <button class="dots-btn" onclick="confirmDeactivate(<?= $u['user_id'] ?>,'<?= addslashes($full) ?>')">Deactivate</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="addUserModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
<h3>Add User</h3>
<div class="row2"><div class="field"><label>First Name</label><input id="au_first"></div><div class="field"><label>Last Name</label><input id="au_last"></div></div>
<div class="row2"><div class="field"><label>Middle Name</label><input id="au_mid"></div><div class="field"><label>Role</label><select id="au_role"><option>User</option><option>Admin</option></select></div></div>
<div class="field"><label>Email</label><input type="email" id="au_email"></div>
<div class="row2"><div class="field"><label>Password</label><input type="password" id="au_pass"></div><div class="field"><label>Confirm</label><input type="password" id="au_pass2"></div></div>
<div class="field"><label>Contact</label><input id="au_contact"></div>
<div class="field"><label>Address</label><textarea id="au_address"></textarea></div>
<button class="btn btn-add" onclick="submitAddUser()">Create User</button>
</div>
</div>

<div id="userProfileModal" class="modal">
<div class="modal-box wide">
<button class="modal-close" onclick="closeModal('userProfileModal')">&times;</button>
<div id="profileContent">Loading...</div>
</div>
</div>

<div id="deactivateModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('deactivateModal')">&times;</button>
<p>Deactivate <span id="deactivateName"></span>?</p>
<div class="confirm-buttons">
<button class="btn-confirm-del" onclick="submitDeactivate()">Deactivate</button>
<button class="btn-cancel" onclick="closeModal('deactivateModal')">Cancel</button>
</div>
</div>
</div>

<script>
let deactivateUserId = null;
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); }));
function filterUsers() { let q = document.getElementById('userSearch').value.toLowerCase(); document.querySelectorAll('#usersTable tbody tr').forEach(r => { r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none'; }); }
async function submitAddUser() { let fd = new FormData(); fd.append('action','register'); fd.append('first_name', document.getElementById('au_first').value); fd.append('last_name', document.getElementById('au_last').value); fd.append('middle_name', document.getElementById('au_mid').value); fd.append('email', document.getElementById('au_email').value); fd.append('password', document.getElementById('au_pass').value); fd.append('user_type', document.getElementById('au_role').value); fd.append('contact_number', document.getElementById('au_contact').value); fd.append('address', document.getElementById('au_address').value); let res = await fetch('admin_user_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('User created'); location.reload(); } else alert(data.error); }
async function openUserProfile(userId) { openModal('userProfileModal'); let res = await fetch(`admin_user_api.php?action=profile&user_id=${userId}`); let data = await res.json(); if(!data.success) { document.getElementById('profileContent').innerHTML = '<p>Error</p>'; return; } document.getElementById('profileContent').innerHTML = `<div class="profile-header"><div class="profile-avatar">${(data.full_name||'U')[0]}</div><div><div class="profile-name">${data.full_name}</div><div class="profile-email">${data.email}</div></div></div><div class="profile-row"><span>Contact</span><span>${data.contact_number||'N/A'}</span></div><div class="profile-row"><span>Address</span><span>${data.address||'N/A'}</span></div><div class="profile-row"><span>Role</span><span>${data.user_type === 'User' ? 'Student' : 'Admin'}</span></div><div class="profile-row"><span>Penalty</span><span>₱${parseFloat(data.total_penalty||0).toFixed(2)}</span></div><div class="profile-row"><span>Borrowed Books</span><span>${data.borrowed_count}</span></div><div style="margin-top:16px;"><strong>Borrow History</strong><div>${data.borrows.map(b => `<div class="borrow-history-item">${b.title} (${b.borrowed_date} - ${b.due_date})<br>Status: ${b.borrow_status}</div>`).join('')||'No borrows'}</div></div>`; }
function confirmDeactivate(userId, name) { deactivateUserId = userId; document.getElementById('deactivateName').innerText = name; openModal('deactivateModal'); }
async function submitDeactivate() { let fd = new FormData(); fd.append('action','deactivate'); fd.append('user_id', deactivateUserId); let res = await fetch('admin_user_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('User deactivated'); location.reload(); } else alert(data.error); }
</script>
</body>
</html>