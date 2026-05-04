<?php
ob_start();
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["user_type"] = $user["user_type"];

        header("Location: ../user/books.php");
        exit();

    } else {
        echo "Invalid email or password!";
    }
}
?>

<form method="POST">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>