<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@admin.com' || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
$db = getDB();

$books = $db->query(
    "SELECT i.item_id, i.title, i.author, i.isbn, i.publisher, i.publication_year,
            i.quantity_available, i.shelf_location, i.item_status, i.cover_image,
            c.category_name, c.category_id
     FROM items i JOIN categories c ON i.category_id = c.category_id
     ORDER BY i.title"
)->fetchAll(PDO::FETCH_ASSOC);

$categories = $db->query("SELECT * FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$db = null;
$current_page = 'books';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>
    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">
                <h2>Manage Books</h2>
                <p>View and manage library books</p>
            </div>
        </div>
        <div class="content">
            <div class="toolbar">
                <input class="search-bar" id="bookSearch" placeholder="Search books…" oninput="filterBooks()">
                <button class="btn btn-add" onclick="openModal('addBookModal')">Add Book</button>
                <button class="btn btn-edit" onclick="openModal('editBookModal')">Edit Book</button>
                <button class="btn btn-del" onclick="openModal('deleteBookModal')">Delete Book</button>
            </div>
            <div class="table-wrap">
                <table id="booksTable">
                    <thead>
                        <tr><th>Books</th><th>ISBN</th><th>Category</th><th>Quantity</th><th>Shelf</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $b): ?>
                        <tr>
                            <td>
                                <div class="book-cell">
                                    <div class="book-thumb">
                                        <?php if (!empty($b['cover_image'])): ?>
                                            <img src="<?= htmlspecialchars($b['cover_image']) ?>" alt="cover">
                                        <?php else: ?>
                                            📖
                                        <?php endif; ?>
                                    </div>
                                    <div><strong><?= htmlspecialchars($b['title']) ?></strong><br><span style="font-size:0.8rem;"><?= htmlspecialchars($b['author']) ?></span></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($b['isbn'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['category_name']) ?></td>
                            <td><?= $b['quantity_available'] ?></td>
                            <td><?= htmlspecialchars($b['shelf_location'] ?? '—') ?></td>
                            <td><span class="badge <?= $b['item_status']==='Available'?'badge-available':'badge-borrowed' ?>"><?= $b['item_status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Book Modal with image upload -->
<div id="addBookModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('addBookModal')">&times;</button>
<h3>Add Book</h3>
<div id="addBookAlert" class="alert-err"></div>
<div class="field"><label>Title</label><input type="text" id="ab_title"></div>
<div class="field"><label>Author</label><input type="text" id="ab_author"></div>
<div class="row2"><div class="field"><label>ISBN</label><input type="text" id="ab_isbn"></div><div class="field"><label>Quantity</label><input type="number" id="ab_qty" value="1"></div></div>
<div class="row2"><div class="field"><label>Category</label><select id="ab_cat"><?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Shelf</label><input type="text" id="ab_shelf"></div></div>
<div class="row2"><div class="field"><label>Publisher</label><input type="text" id="ab_pub"></div><div class="field"><label>Year</label><input type="number" id="ab_year"></div></div>
<div class="field">
    <label>Cover Image</label>
    <input type="file" id="ab_cover" accept="image/*">
    <div id="ab_cover_preview" style="margin-top:8px; max-width:100px; display:none;">
        <img style="width:100%; border-radius:8px;">
    </div>
</div>
<button class="btn btn-add" style="width:100%;" onclick="submitAddBook()">Add Book</button>
</div>
</div>

<!-- Edit Book Modal with image upload -->
<div id="editBookModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('editBookModal')">&times;</button>
<h3>Edit Book</h3>
<input class="search-bar" id="editBookSearch" placeholder="Search title/author..." oninput="searchEditBooks()">
<div id="editBookResults"></div>
<div id="editFields" style="display:none;">
<input type="hidden" id="eb_id">
<div class="field"><label>Title</label><input type="text" id="eb_title"></div>
<div class="field"><label>Author</label><input type="text" id="eb_author"></div>
<div class="row2"><div class="field"><label>ISBN</label><input type="text" id="eb_isbn"></div><div class="field"><label>Quantity</label><input type="number" id="eb_qty"></div></div>
<div class="row2"><div class="field"><label>Category</label><select id="eb_cat"><?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Shelf</label><input type="text" id="eb_shelf"></div></div>
<div class="row2"><div class="field"><label>Publisher</label><input type="text" id="eb_pub"></div><div class="field"><label>Year</label><input type="number" id="eb_year"></div></div>
<div class="field"><label>Status</label><select id="eb_status"><option>Available</option><option>Borrowed</option></select></div>
<div class="field">
    <label>Cover Image</label>
    <input type="file" id="eb_cover" accept="image/*">
    <div id="eb_cover_preview" style="margin-top:8px; max-width:100px;">
        <img id="eb_cover_img" style="width:100%; border-radius:8px;">
    </div>
</div>
<button class="btn btn-edit" onclick="submitEditBook()">Save Changes</button>
</div>
</div>
</div>

<!-- Delete Book Modal -->
<div id="deleteBookModal" class="modal">
<div class="modal-box">
<button class="modal-close" onclick="closeModal('deleteBookModal')">&times;</button>
<h3>Delete Book</h3>
<input class="search-bar" id="deleteBookSearch" placeholder="Search..." oninput="searchDeleteBooks()">
<div id="deleteBookResults"></div>
</div>
</div>

<script>
const ALL_BOOKS = <?= json_encode($books) ?>;
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); }));
function filterBooks() { let q = document.getElementById('bookSearch').value.toLowerCase(); document.querySelectorAll('#booksTable tbody tr').forEach(r => { r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none'; }); }

document.getElementById('ab_cover').addEventListener('change', function(e) {
    let preview = document.getElementById('ab_cover_preview');
    if (e.target.files && e.target.files[0]) {
        let reader = new FileReader();
        reader.onload = function(ev) {
            preview.querySelector('img').src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

async function submitAddBook() {
    let fd = new FormData();
    fd.append('action','add');
    fd.append('title', document.getElementById('ab_title').value);
    fd.append('author', document.getElementById('ab_author').value);
    fd.append('isbn', document.getElementById('ab_isbn').value);
    fd.append('qty', document.getElementById('ab_qty').value);
    fd.append('cat_id', document.getElementById('ab_cat').value);
    fd.append('shelf', document.getElementById('ab_shelf').value);
    fd.append('publisher', document.getElementById('ab_pub').value);
    fd.append('year', document.getElementById('ab_year').value);
    let coverFile = document.getElementById('ab_cover').files[0];
    if (coverFile) fd.append('cover', coverFile);
    let res = await fetch('admin_book_api.php', {method:'POST', body:fd});
    let data = await res.json();
    if(data.success) { alert('Book added'); location.reload(); } else alert(data.error);
}

function searchEditBooks() {
    let q = document.getElementById('editBookSearch').value.toLowerCase();
    let box = document.getElementById('editBookResults');
    if(!q) { box.innerHTML=''; document.getElementById('editFields').style.display='none'; return; }
    let filtered = ALL_BOOKS.filter(b => b.title.toLowerCase().includes(q) || b.author.toLowerCase().includes(q));
    box.innerHTML = filtered.map(b => `<div style="padding:8px; border-bottom:1px solid #ddd; cursor:pointer;" onclick="selectEditBook(${b.item_id})"><strong>${b.title}</strong> by ${b.author}</div>`).join('');
}

function selectEditBook(id) {
    let b = ALL_BOOKS.find(x => x.item_id == id);
    if(!b) return;
    document.getElementById('eb_id').value = b.item_id;
    document.getElementById('eb_title').value = b.title;
    document.getElementById('eb_author').value = b.author;
    document.getElementById('eb_isbn').value = b.isbn||'';
    document.getElementById('eb_qty').value = b.quantity_available;
    document.getElementById('eb_shelf').value = b.shelf_location||'';
    document.getElementById('eb_pub').value = b.publisher||'';
    document.getElementById('eb_year').value = b.publication_year||'';
    document.getElementById('eb_status').value = b.item_status;
    let catSel = document.getElementById('eb_cat');
    for(let opt of catSel.options) if(opt.value == b.category_id) opt.selected=true;
    let previewImg = document.getElementById('eb_cover_img');
    if(b.cover_image) { previewImg.src = b.cover_image; previewImg.style.display = 'block'; }
    else { previewImg.style.display = 'none'; }
    document.getElementById('editFields').style.display='block';
    document.getElementById('editBookResults').innerHTML='';
    document.getElementById('editBookSearch').value='';
}

async function submitEditBook() {
    let fd = new FormData();
    fd.append('action','edit');
    fd.append('item_id', document.getElementById('eb_id').value);
    fd.append('title', document.getElementById('eb_title').value);
    fd.append('author', document.getElementById('eb_author').value);
    fd.append('isbn', document.getElementById('eb_isbn').value);
    fd.append('qty', document.getElementById('eb_qty').value);
    fd.append('cat_id', document.getElementById('eb_cat').value);
    fd.append('shelf', document.getElementById('eb_shelf').value);
    fd.append('publisher', document.getElementById('eb_pub').value);
    fd.append('year', document.getElementById('eb_year').value);
    fd.append('status', document.getElementById('eb_status').value);
    let coverFile = document.getElementById('eb_cover').files[0];
    if (coverFile) fd.append('cover', coverFile);
    let res = await fetch('admin_book_api.php', {method:'POST', body:fd});
    let data = await res.json();
    if(data.success) { alert('Book updated'); location.reload(); } else alert(data.error);
}

function searchDeleteBooks() {
    let q = document.getElementById('deleteBookSearch').value.toLowerCase();
    let box = document.getElementById('deleteBookResults');
    if(!q) { box.innerHTML=''; return; }
    let filtered = ALL_BOOKS.filter(b => b.title.toLowerCase().includes(q) || b.author.toLowerCase().includes(q));
    box.innerHTML = filtered.map(b => `<div style="display:flex; justify-content:space-between; padding:8px; border-bottom:1px solid #eee;"><span><strong>${b.title}</strong> by ${b.author}</span><button onclick="doDeleteBook(${b.item_id})" style="background:#fee2e2; border:none; border-radius:8px; padding:4px 12px;">Delete</button></div>`).join('');
}
async function doDeleteBook(id) { if(!confirm('Permanently delete this book?')) return; let fd = new FormData(); fd.append('action','delete'); fd.append('item_id', id); let res = await fetch('admin_book_api.php', {method:'POST', body:fd}); let data = await res.json(); if(data.success) { alert('Deleted'); location.reload(); } else alert(data.error); }
</script>
</body>
</html>