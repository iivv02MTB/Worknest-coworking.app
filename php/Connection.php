<?php
// connection.php
// Central MySQL connection file using PDO.
// Every other PHP script will include this file instead of repeating the connection.
echo "CONNECTION CORRECTO";
exit;

$host = 'localhost';
$dbname = 'dbname';
$user = 'Userforchange';       // replace with your real MySQL user
$password = 'Passwordforchange';       // replace with your real MySQL password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw exceptions on failure
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // results as associative arrays
        ]
    );
} catch (PDOException $e) {
    // In production, never show the raw error to the user, just log it.
    die('Database connection error: ' . $e->getMessage());
}
