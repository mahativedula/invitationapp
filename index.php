<?php

    // Start the session
    session_start();

    // Database connection
    $error_message = '';

    try {
        $db = new PDO(
        "pgsql:host=db;port=5432;dbname=invitationapp_db",
        "localuser",
        "cs4640LocalUser!",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
      );  
    } catch (PDOException $e) {
        $error_message = "Error connecting to database: " . $e->getMessage();
        exit();
    }

    // Login page redirection
    $page = $_GET['page'] ?? 'login';

    // Redirect based on the page parameter
    switch ($page) {
        case 'login':
            header("Location: login.php");
            exit();
        case 'account-creation':
            header("Location: account-creation.php");
            exit();
        default:
            header("Location: login.php");
            exit();
    }
?>