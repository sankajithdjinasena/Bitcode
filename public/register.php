<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // VALIDATION
    if (empty($name) || empty($email) || empty($password)) {

        $message = "All fields are required";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match";

    } else {

        // CHECK DUPLICATE EMAIL
        $check = $conn->prepare("
            SELECT id FROM users
            WHERE email = ?
        ");

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "Email already exists";

        } else {

            // HASH PASSWORD
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // INSERT USER
            $stmt = $conn->prepare("
                INSERT INTO users
                (name, email, password, role, status)
                VALUES (?, ?, ?, 'customer', 'active')
            ");

            $stmt->execute([
                $name,
                $email,
                $hashedPassword
            ]);

            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
</head>

<body>

<h2>Customer Register</h2>

<?php if($message): ?>
    <p style="color:red;">
        <?= $message ?>
    </p>
<?php endif; ?>

<form method="POST">

    <input
        type="text"
        name="name"
        placeholder="Display Name"
        required
    >

    <br><br>

    <input
        type="email"
        name="email"
        placeholder="Email"
        required
    >

    <br><br>

    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    >

    <br><br>

    <input
        type="password"
        name="confirm_password"
        placeholder="Confirm Password"
        required
    >

    <br><br>

    <button type="submit">
        Register
    </button>

</form>

<br>

<a href="login.php">
    Already have an account?
</a>

</body>
</html>