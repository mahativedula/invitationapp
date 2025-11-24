<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = new PDO(
        "pgsql:host=db;port=5432;dbname=invitationapp_db",
        "localuser",
        "cs4640LocalUser!",

        // USE BELOW CODE INSTEAD WHEN ACTUALLY PUSHING TO SERVER
        // "pgsql:host=localhost;port=5432;dbname=COMPUTING_ID_HERE",
        // "COMPUTING_ID_HERE",
        // "phpgsql_password_here",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
?>
