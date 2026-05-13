<?php
// login.php (alternative - doesn't require changing the stored procedure)
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $db = getDB();
        
        try {
            // First call: Login user
            $stmt = $db->prepare("CALL SP_LoginUser(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // Close first result set
            
            if ($user && password_verify($password, $user['password'])) {
                // Second call: Get user's full name (separate query)
                $nameStmt = $db->prepare("SELECT CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name FROM users WHERE user_id = ?");
                $nameStmt->execute([$user['user_id']]);
                $nameResult = $nameStmt->fetch(PDO::FETCH_ASSOC);
                $nameStmt->closeCursor();
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $nameResult['full_name'] ?? 'User';
                
                header("Location: ../admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Login failed: " . $e->getMessage();
        }
        
        $db = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Librar-E | Batangas State University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #1a2744;
            --navy-2: #0f1b35;
            --white: #ffffff;
            --gray: #f4f6fb;
            --border: #d1d9ec;
            --err: #c0392b;
            --radius: 50px;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: var(--white);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 0 var(--border);
        }
        .brand h1 { font-family: 'Playfair Display', serif; font-size: 1.35rem; color: var(--navy); }
        .brand p { font-size: 0.78rem; color: #5a6a8a; }
        .hero {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(10,18,40,.45);
        }
        .card {
            position: relative;
            background: var(--white);
            border-radius: 18px;
            padding: 44px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 24px 64px rgba(0,0,0,.22);
        }
        .card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--navy);
            text-align: center;
            margin-bottom: 26px;
        }
        .alert {
            background: #fdecea;
            border: 1px solid #f5c6c2;
            color: var(--err);
            border-radius: 10px;
            padding: 11px 16px;
            margin-bottom: 18px;
            text-align: center;
        }
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: #5a6a8a;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .field input {
            width: 100%;
            padding: 14px 20px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            background: var(--gray);
        }
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: var(--navy);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary:hover { background: var(--navy-2); }
        .card-footer {
            text-align: center;
            margin-top: 20px;
            font-size: .875rem;
        }
        .card-footer a { color: var(--navy); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<header>
    <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
        <rect width="52" height="52" rx="8" fill="white"/>
        <path d="M8 38V14a2 2 0 012-2h12c3 0 4 2 4 2s1-2 4-2h12a2 2 0 012 2v24" stroke="#1a2744" stroke-width="2.2"/>
        <line x1="26" y1="14" x2="26" y2="38" stroke="#1a2744" stroke-width="2.2"/>
        <path d="M8 38h16s1 2 2 2 2-2 2-2h16" stroke="#1a2744" stroke-width="2.2"/>
        <line x1="12" y1="22" x2="22" y2="22" stroke="#1a2744" stroke-width="1.8"/>
        <line x1="30" y1="22" x2="40" y2="22" stroke="#1a2744" stroke-width="1.8"/>
    </svg>
    <div class="brand">
        <h1>LIBRAR-E</h1>
        <p>Batangas State University</p>
    </div>
</header>
<div class="hero">
    <div class="card">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>
        <div class="card-footer">
            Don't have an account? <a href="signup.php">Register</a>
        </div>
    </div>
</div>
</body>
</html>