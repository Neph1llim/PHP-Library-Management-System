<?php
$current_page = $current_page ?? 'dashboard';
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <svg viewBox="0 0 52 52" fill="none"><rect width="52" height="52" rx="8" fill="rgba(255,255,255,.15)"/>
      <path d="M8 38V14a2 2 0 012-2h12c3 0 4 2 4 2s1-2 4-2h12a2 2 0 012 2v24" stroke="#fff" stroke-width="2.2"/>
      <line x1="26" y1="14" x2="26" y2="38" stroke="#fff" stroke-width="2.2"/>
      <path d="M8 38h16s1 2 2 2 2-2 2-2h16" stroke="#fff" stroke-width="2.2"/>
    </svg>
    <span>LIBRAR-E</span>
    <small>Admin Panel</small>
  </div>
  <nav class="sidebar-nav">
    <a href="admindashboard.php" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
      Dashboard
    </a>
    <a href="manage_books.php" class="nav-item <?= $current_page === 'books' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
      Manage Books
    </a>
    <a href="manage_users.php" class="nav-item <?= $current_page === 'users' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Manage Users
    </a>
    <a href="manage_borrows.php" class="nav-item <?= $current_page === 'borrows' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
      Manage Borrows
    </a>
  </nav>
  <div class="sidebar-logout">
    <a href="../auth/logout.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>