<?php
// signup.php
session_start();

if (isset($_SESSION['user_id'])) {
<<<<<<< HEAD
    header("Location: ../admin/dashboard.php");
=======
    header("Location: ../users/dashboard.php");
>>>>>>> a433c1c (Signup and Login (FIXED))
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
    $user_type      = 'User'; // Default user type from your schema

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
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Call the stored procedure
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
            
            // Get the result (new_user_id)
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['new_user_id'])) {
                $success = "Account created! You can now <a href='login.php'>log in</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            
            // Close the cursor to allow further queries
            $stmt->closeCursor();
            
        } catch (PDOException $e) {
            // Check if it's the duplicate email error
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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #1a2744;
            --navy-2: #0f1b35;
            --white:  #ffffff;
            --gray:   #f4f6fb;
            --border: #d1d9ec;
            --ok:     #1a6e3c;
            --err:    #c0392b;
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
            position: relative;
            z-index: 10;
        }
        .brand h1 { font-family: 'Playfair Display', serif; font-size: 1.35rem; color: var(--navy); line-height: 1.1; }
        .brand p  { font-size: 0.78rem; color: #5a6a8a; letter-spacing: 0.02em; }

        .hero {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=1600&q=80')
                        center/cover no-repeat;
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
            pointer-events: none;
        }

        .card {
            position: relative;
            background: var(--white);
            border-radius: 18px;
            padding: 44px 44px 38px;
            width: 100%;
            max-width: 620px;
            box-shadow: 0 24px 64px rgba(0,0,0,.22);
            animation: rise .55s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes rise {
            from { opacity:0; transform:translateY(28px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
            color: var(--navy);
            text-align: center;
            margin-bottom: 26px;
        }

        .alert {
            border-radius: 10px;
            padding: 11px 16px;
            font-size: .875rem;
            margin-bottom: 18px;
            text-align: center;
        }
        .alert.error   { background:#fdecea; border:1px solid #f5c6c2; color:var(--err); }
        .alert.success { background:#e6f4ed; border:1px solid #b2dfc3; color:var(--ok); }
        .alert.success a { color: var(--ok); font-weight: 600; }

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: #5a6a8a;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-bottom: 5px;
            padding-left: 6px;
        }
        .field label .optional {
            font-weight: normal;
            color: #8a9abf;
            text-transform: none;
            font-size: 0.7rem;
        }
        .field input, .field textarea {
            width: 100%;
            padding: 14px 20px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            color: var(--navy);
            background: var(--gray);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .field textarea {
            border-radius: 20px;
            resize: vertical;
            min-height: 70px;
        }
        .field input::placeholder, .field textarea::placeholder { color: #b0bcd4; }
        .field input:focus, .field textarea:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(26,39,68,.1);
            background: var(--white);
        }

        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .row-3 { display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 12px; }

        .btn-primary {
            width: 100%;
            padding: 15px;
            background: var(--navy);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .04em;
            cursor: pointer;
            margin-top: 8px;
            transition: background .2s, transform .15s, box-shadow .2s;
        }
        .btn-primary:hover {
            background: var(--navy-2);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(15,27,53,.28);
        }

        .card-footer {
            text-align: center;
            margin-top: 20px;
            font-size: .875rem;
            color: #5a6a8a;
        }
        .card-footer a { color: var(--navy); font-weight: 600; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }

        .hint { font-size: .76rem; color: #8a9abf; padding-left: 6px; margin-top: 4px; }

        @media (max-width: 600px) { 
            .row, .row-3 { grid-template-columns: 1fr; } 
            .card { padding: 36px 24px 30px; } 
        }
    </style>
</head>
<body>

<header>
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