<?php
session_start();

if (isset($_SESSION['user_id'])) {
<<<<<<< Updated upstream
    header("Location: ../users/dashboard.php");
=======

    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/admindashboard.php");
    } else {
        header("Location: ../users/dashboard.php");
    }

>>>>>>> Stashed changes
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
            $stmt = $db->prepare("CALL SP_LoginUser(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($user && password_verify($password, $user['password'])) {
                $nameStmt = $db->prepare("SELECT TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) as full_name FROM users WHERE user_id = ?");
                $nameStmt->execute([$user['user_id']]);
                $nameResult = $nameStmt->fetch(PDO::FETCH_ASSOC);
                $nameStmt->closeCursor();
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $nameResult['full_name'] ?? 'User';
<<<<<<< Updated upstream
                
                header("Location: ../users/dashboard.php");
=======

                // Redirect based on user type
                if ($user['user_type'] === 'Admin') {

                    header("Location: ../admin/admindashboard.php");

                } else {

                    header("Location: ../users/dashboard.php");
                }

>>>>>>> Stashed changes
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <div class="logo">
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
    </div>
</header>
<div class="hero">
    <div class="card">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
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