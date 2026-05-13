<?php
// add book 
// edit book
// delete book
// manage users
// manage borrows
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Add a fallback in case full_name isn't set
$full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — LIBRAR-E</title> <!-- Fixed title -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Your existing styles remain the same */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --navy:#1a2744; --white:#fff; --gray:#f4f6fb; --border:#d1d9ec; }
        body { font-family:'DM Sans',sans-serif; background:var(--gray); min-height:100vh; }

        header {
            background:var(--white);
            padding:14px 32px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            box-shadow:0 1px 0 var(--border);
        }
        .logo { display:flex; align-items:center; gap:14px; }
        .brand h1 { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--navy); }
        .brand p  { font-size:.78rem; color:#5a6a8a; }

        .user-info { display:flex; align-items:center; gap:16px; font-size:.9rem; color:#5a6a8a; }
        .user-info strong { color:var(--navy); }
        .logout {
            padding:8px 20px;
            background:var(--navy);
            color:var(--white);
            text-decoration:none;
            border-radius:50px;
            font-size:.85rem;
            font-weight:600;
            transition:background .2s;
        }
        .logout:hover { background:#0f1b35; }

        main { max-width:900px; margin:60px auto; padding:0 20px; }

        .welcome {
            background:var(--white);
            border-radius:16px;
            padding:40px;
            box-shadow:0 4px 24px rgba(0,0,0,.07);
            text-align:center;
            animation:rise .5s ease both;
        }
        @keyframes rise { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .welcome h2 {
            font-family:'Playfair Display',serif;
            font-size:2rem;
            color:var(--navy);
            margin-bottom:10px;
        }
        .welcome p { color:#5a6a8a; font-size:1rem; }
        .badge {
            display:inline-block;
            margin-top:20px;
            padding:8px 20px;
            background:var(--gray);
            border-radius:50px;
            color:var(--navy);
            font-size:.85rem;
            font-weight:600;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <svg width="44" height="44" viewBox="0 0 52 52" fill="none">
            <path d="M8 38V14a2 2 0 012-2h12c3 0 4 2 4 2s1-2 4-2h12a2 2 0 012 2v24" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="26" y1="14" x2="26" y2="38" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M8 38h16s1 2 2 2 2-2 2-2h16" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
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
    <div class="welcome">
        <h2>Welcome back, <?= $full_name ?>! 📚</h2>
        <p>You are successfully logged into the LIBRAR-E portal.</p>
    </div>
</main>

</body>
</html>