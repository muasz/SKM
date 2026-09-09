<?php

session_start();

require_once __DIR__ . "/../config/db.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$username = mysqli_real_escape_string($conn, $username);

$query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 1) {

    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_nama'] = $user['nama'];

        header("Location: ../dashboard.php");
        exit;

    } else {
        header("Location: ../login.php?error=1");
        exit;
    }

} else {
    header("Location: ../login.php?error=1");
    exit;
}