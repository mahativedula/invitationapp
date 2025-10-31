<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = new PDO(
        "pgsql:host=localhost;port=5432;dbname=COMPUTING_ID_HERE",
        "COMPUTING_ID_HERE",
        "phpgsql_password_here",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
?>
