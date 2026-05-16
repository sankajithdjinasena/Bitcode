<?php
session_start();
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        if ($user['status'] == 'deactivated') {
            die("Account Deactivated");
        }

        $_SESSION['user'] = $user;

        header("Location: marketplace.php");
        exit;
    } else {
        echo "Invalid Credentials";
    }
}
?>

<form method="POST">

    <h2>Login</h2>

    <input type="email" name="email" placeholder="Email" required>
    <br><br>

    <input type="password" name="password" placeholder="Password" required>
    <br><br>

    <button type="submit">Login</button>

</form>