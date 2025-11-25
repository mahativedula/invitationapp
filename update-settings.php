<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ==================== UPDATE PROFILE ====================
if ($action === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Username and email are required']);
        exit;
    }

    // Validate username format (alphanumeric, underscores, 3-30 chars)
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        echo json_encode(['success' => false, 'error' => 'Username must be 3-30 characters and contain only letters, numbers, and underscores']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    try {
        // Check if username is taken by another user
        $stmt = $db->prepare("SELECT user_id FROM invitationapp_users WHERE username = :username AND user_id != :user_id");
        $stmt->execute([':username' => $username, ':user_id' => $user_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username is already taken']);
            exit;
        }
        
        // Check if email is taken by another user
        $stmt = $db->prepare("SELECT user_id FROM invitationapp_users WHERE email = :email AND user_id != :user_id");
        $stmt->execute([':email' => $email, ':user_id' => $user_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email is already registered to another account']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE invitationapp_users SET username = :username, email = :email WHERE user_id = :user_id");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':user_id' => $user_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== CHANGE PASSWORD ====================
if ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'error' => 'All password fields are required']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters long']);
        exit;
    }

    try {
        // Get current password hash
        $stmt = $db->prepare("SELECT password_hash FROM invitationapp_users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        if (!password_verify($current_password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
            exit;
        }
        
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE invitationapp_users SET password_hash = :password_hash WHERE user_id = :user_id");
        $stmt->execute([
            ':password_hash' => $new_password_hash,
            ':user_id' => $user_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== DELETE ACCOUNT ====================
if ($action === 'delete_account') {
    try {
        $db->beginTransaction();
        
        // Delete user account (CASCADE will handle related data)
        $stmt = $db->prepare("DELETE FROM invitationapp_users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        $db->commit();
        
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}


echo json_encode(['success' => false, 'error' => 'Invalid action']);