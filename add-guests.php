<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$host_id = $_SESSION['user_id'];
$event_id = (int)($_POST['event_id'] ?? 0);
$guests_json = $_POST['guests_data'] ?? '[]';
$guests = json_decode($guests_json, true);

// Validation
if (empty($guests) || !is_array($guests)) {
    echo json_encode(['success' => false, 'error' => 'No guests provided']);
    exit;
}

try {
    // Verify the event belongs to the logged-in user
    $verify_stmt = $db->prepare("SELECT event_id FROM invitationapp_events WHERE event_id = :event_id AND host_id = :host_id");
    $verify_stmt->execute([':event_id' => $event_id, ':host_id' => $host_id]);
    
    if (!$verify_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Event not found or access denied']);
        exit;
    }

    // Validate guest data
    $email_pattern = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    foreach ($guests as $guest) {
        $email = trim($guest['email'] ?? '');
        $name = trim($guest['name'] ?? '');
        
        if (empty($email) || empty($name)) {
            echo json_encode(['success' => false, 'error' => 'All guests must have a name and email']);
            exit;
        }
        
        if (!preg_match($email_pattern, $email)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email: ' . htmlspecialchars($email)]);
            exit;
        }
    }

    // Begin transaction
    $db->beginTransaction();
    
    // Prepare statements
    $user_lookup = $db->prepare("SELECT user_id FROM invitationapp_users WHERE email = :email");
    $rsvp_insert = $db->prepare("
        INSERT INTO invitationapp_rsvps (event_id, recipient_id, response)
        VALUES (:event_id, :recipient_id, 'No Response')
        ON CONFLICT (event_id, recipient_id) DO NOTHING
    ");
    
    $added_count = 0;
    $skipped_count = 0;
    $not_registered = [];
    
    foreach ($guests as $guest) {
        $email = trim($guest['email']);
        $name = trim($guest['name']);
        
        // Look up if user exists
        $user_lookup->execute([':email' => $email]);
        $recipient_id = $user_lookup->fetchColumn();
        
        if ($recipient_id) {
            // User exists, add RSVP
            $rsvp_insert->execute([
                ':event_id' => $event_id,
                ':recipient_id' => $recipient_id
            ]);
            
            // Check if row was actually inserted (not a duplicate)
            if ($rsvp_insert->rowCount() > 0) {
                $added_count++;
            } else {
                $skipped_count++;
            }
        } else {
            // User doesn't exist in system
            $not_registered[] = $email;
        }
    }
    
    $db->commit();
    
    // Build response message
    $message = "Successfully added $added_count guest(s)";
    if ($skipped_count > 0) {
        $message .= " ($skipped_count already invited)";
    }
    if (!empty($not_registered)) {
        $message .= ". Note: " . count($not_registered) . " email(s) not registered in system: " . implode(', ', $not_registered);
    }
    
    echo json_encode([
        'success' => true,
        'added_count' => $added_count,
        'skipped_count' => $skipped_count,
        'not_registered' => $not_registered,
        'message' => $message
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}