<?php

$host = getenv('MYSQLHOST') ?: 'db';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'root';
$database = getenv('MYSQLDATABASE') ?: 'sisgizi';

$conn = mysqli_connect($host, $user, $password, $database, (int) $port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
