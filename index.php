
<?php

    // working link https://cs4640.cs.virginia.edu/kur2xk/invitationapp/
    // working link https://cs4640.cs.virginia.edu/nsc3sj/invitationapp/
    // working link https://cs4640.cs.virginia.edu/xyx4pf/invitationapp/


    // Start the session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database connection
    require_once 'db-connect.php';

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
            require 'event-creation.php';
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
        case 'compose-message':
            require 'compose-message.php';
            break;
        case 'settings':
            require 'settings.php'; 
            break;
        default:
            header("Location: login.php");
            exit();
            break;
    }
?>
