<?php

    // Start the session
    session_start();

    // Database connection
    $error_message = '';

    try {
        $db = new PDO(
        "pgsql:host=localhost;port=5432;dbname=COMPUTING_ID_HERE",
        "COMPUTING_ID_HERE",
        "phpgsql_password_here",
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
            require 'login.php';
            break;
        case 'account-creation':
            require 'account-creation.php';
            break;
        case 'host-dashboard':
            require 'host-dashboard.php';
            break;
        case 'create-event':
            require 'create-event.php';
            break;    
        case 'delete-event':
            require 'delete-event.php';
            break;
        case 'invitation':
            require 'invitation.php';
            break;
        case 'messages':    
            require 'message.php';
            break;
        case 'sent':
            require 'sent.php';
            break;
        default:
            header("Location: login.php");
            break;
    }
?>