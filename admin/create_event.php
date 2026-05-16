<?php
session_start();
require_once "../config/database.php";

/* =========================
   1. ADMIN AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* =========================
   2. HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);

    /* =========================
       3. SAFE DATETIME FORMAT
    ========================= */
    $go_live_at = str_replace("T", " ", $_POST['go_live_at']) . ":00";

    /* =========================
       4. UPLOAD VALIDATION
    ========================= */
    if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== 0) {
        die("Image upload failed");
    }

    if ($_FILES['cover_image']['size'] > 2 * 1024 * 1024) {
        die("File too large (Max 2MB)");
    }

    /* =========================
       5. VERIFY REAL IMAGE
    ========================= */
    $imageInfo = getimagesize($_FILES['cover_image']['tmp_name']);
    if ($imageInfo === false) {
        die("Invalid image file");
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimes[$imageInfo['mime']])) {
        die("Only JPG, PNG, WEBP allowed");
    }

    $ext = $allowedMimes[$imageInfo['mime']];

    /* =========================
       6. SAFE FILE NAME
    ========================= */
    $fileName = bin2hex(random_bytes(16)) . "." . $ext;

    $uploadDir = __DIR__ . "/../uploads/events/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $targetPath = $uploadDir . $fileName;

    /* =========================
       7. MOVE FILE SECURELY
    ========================= */
    if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetPath)) {
        die("Failed to save image");
    }

    /* =========================
       8. INSERT EVENT
    ========================= */
    $stmt = $conn->prepare("
        INSERT INTO events (name, cover_image, go_live_at, status)
        VALUES (?, ?, ?, 'locked')
    ");

    $stmt->execute([$name, $fileName, $go_live_at]);

    header("Location: events.php");
    exit;
}
?>

<!-- =========================
     9. HTML FORM
========================= -->

<h2>Create Event</h2>

<form method="POST" enctype="multipart/form-data">

    <input type="text" name="name" placeholder="Event Name" required>
    <br><br>

    <input type="file" name="cover_image" required accept="image/*">
    <br><br>

    <input type="datetime-local" name="go_live_at" required>
    <br><br>

    <button type="submit">Create Event</button>

</form>