<?php
require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    $userId = $_SESSION['user']['id'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $error = "User not found.";

    } elseif (!password_verify($current, $user['password'])) {

        $error = "Current password is incorrect.";

    } elseif ($new !== $confirm) {

        $error = "Passwords do not match.";

    } elseif (strlen($new) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        $hashed = password_hash($new, PASSWORD_DEFAULT);

        $update = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $update->execute([$hashed, $userId]);

        $message = "Password updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Change Password — SwiftDrop</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>

    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    :root {
        --bg:      #07070d;
        --surface: #0f0f18;
        --surface2:#141420;
        --border:  rgba(255,255,255,0.07);
        --accent:  #ff4d1c;
        --accent2: #ff8c42;
        --text:    #f0ede8;
        --muted:   #5a5768;
        --muted2:  #888098;
    }

    body {
        background: var(--bg);
        color: var(--text);

        font-family: 'DM Sans', sans-serif;

        min-height: 100vh;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 2rem;

        overflow-x: hidden;
    }

    /* Grid background */
    body::before {
        content: '';

        position: fixed;
        inset: 0;

        background-image:
            linear-gradient(rgba(255,77,28,0.025) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,77,28,0.025) 1px, transparent 1px);

        background-size: 56px 56px;

        pointer-events: none;
    }

    .card {
        width: 100%;
        max-width: 430px;

        background: var(--surface);

        border: 1px solid var(--border);
        border-radius: 22px;

        padding: 2rem;

        position: relative;
        z-index: 1;
    }

    .logo {
        width: 58px;
        height: 58px;

        background: var(--accent);

        border-radius: 16px;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 26px;

        margin-bottom: 1.5rem;
    }

    h1 {
        font-family: 'Syne', sans-serif;
        font-size: 2rem;
        font-weight: 800;

        letter-spacing: -1px;

        margin-bottom: 0.4rem;
    }

    .sub {
        color: var(--muted2);

        font-size: 14px;
        line-height: 1.5;

        margin-bottom: 2rem;
    }

    .alert {
        padding: 12px 14px;

        border-radius: 12px;

        font-size: 14px;

        margin-bottom: 1.2rem;
    }

    .alert.success {
        background: rgba(34,197,94,0.12);
        border: 1px solid rgba(34,197,94,0.25);
        color: #86efac;
    }

    .alert.error {
        background: rgba(239,68,68,0.12);
        border: 1px solid rgba(239,68,68,0.25);
        color: #fca5a5;
    }

    .field {
        margin-bottom: 1.2rem;
    }

    label {
        display: block;

        margin-bottom: 8px;

        font-size: 13px;
        font-weight: 500;

        color: var(--muted2);
    }

    input {
        width: 100%;

        padding: 14px 16px;

        background: var(--surface2);

        border: 1px solid var(--border);
        border-radius: 12px;

        color: var(--text);

        font-size: 14px;

        outline: none;

        transition: all 0.2s ease;
    }

    input:focus {
        border-color: rgba(255,77,28,0.45);

        box-shadow: 0 0 0 3px rgba(255,77,28,0.08);
    }

    .btn {
        width: 100%;

        padding: 14px;

        background: var(--accent);

        border: none;
        border-radius: 12px;

        color: white;

        font-family: 'Syne', sans-serif;
        font-size: 15px;
        font-weight: 700;

        cursor: pointer;

        transition: all 0.2s ease;

        margin-top: 0.5rem;
    }

    .btn:hover {
        background: #ff6635;

        transform: translateY(-1px);

        box-shadow: 0 8px 24px rgba(255,77,28,0.28);
    }

    .back {
        display: inline-block;

        margin-top: 1.3rem;

        text-decoration: none;

        color: var(--muted2);

        font-size: 13px;

        transition: color 0.2s;
    }

    .back:hover {
        color: var(--text);
    }

    @media (max-width: 500px) {

        body {
            padding: 1rem;
        }

        .card {
            padding: 1.5rem;
        }

        h1 {
            font-size: 1.7rem;
        }

    }

</style>
</head>

<body>

<div class="card">

    <div class="logo">⚡</div>

    <h1>Change Password</h1>

    <p class="sub">
        Update your SwiftDrop account password securely.
    </p>

    <?php if (!empty($message)): ?>
        <div class="alert success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="field">
            <label for="current_password">
                Current Password
            </label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                required
            >
        </div>

        <div class="field">
            <label for="new_password">
                New Password
            </label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                required
            >
        </div>

        <div class="field">
            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >
        </div>

        <button type="submit" class="btn">
            Update Password
        </button>

    </form>

    <a href="index.php" class="back">
        ← Back to Marketplace
    </a>

</div>

</body>
</html>