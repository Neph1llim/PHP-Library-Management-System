<?php
// signup.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../users/dashboard.php");
    exit;
}

require_once '../config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name     = trim($_POST['first_name'] ?? '');
    $middle_name    = trim($_POST['middle_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm        = $_POST['confirm'] ?? '';
    $user_type      = 'User';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Please fill in all required fields (First Name, Last Name, Email, Password).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $db = getDB();
        
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("CALL SP_RegisterUser(?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $hashed_password,
                $contact_number,
                $address,
                $user_type
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result && isset($result['new_user_id'])) {
                $success = "Account created! You can now <a href='login.php'>log in</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Email already registered') !== false) {
                $error = "Email already exists. Please use another email.";
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
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
    <title>Register — Librar-E | Batangas State University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header>
    <div class="logo">
        <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="52" height="52" rx="8" fill="white"/>
            <path d="M8 38V14a2 2 0 012-2h12c3 0 4 2 4 2s1-2 4-2h12a2 2 0 012 2v24" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
            <line x1="26" y1="14" x2="26" y2="38" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M8 38h16s1 2 2 2 2-2 2-2h16" stroke="#1a2744" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
        <div class="brand">
            <h1>LIBRAR-E</h1>
            <p>Batangas State University</p>
        </div>
    </div>
</header>

<div class="hero">
    <div class="card">
        <h2>Register</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="signup.php" novalidate>
            <div class="row-3">
                <div class="field">
                    <label>First Name *</label>
                    <input type="text" name="first_name" placeholder="Juan"
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label>Middle Name </label>
                    <input type="text" name="middle_name" placeholder="Santos"
                           value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz"
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="field">
                <label>Email Address *</label>
                <input type="email" name="email" placeholder="2X-XXXXX@g.batstate-u.edu.ph"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="row">
                <div class="field">
                    <label>Contact Number <span class="optional">(optional)</span></label>
                    <input type="tel" name="contact_number" placeholder="09** *** ****"
                           value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Address <span class="optional">(optional)</span></label>
                    <input type="text" name="address" placeholder="City, Province"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="field">
                    <label>Password *</label>
                    <input type="password" name="password" placeholder="••••••••" required autocomplete="new-password">
                    <div class="hint">Minimum 8 characters</div>
                </div>
                <div class="field">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm" placeholder="••••••••" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        <?php endif; ?>

        <div class="card-footer">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>

</body>
</html>