<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in!']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle GET request - count recipients
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $event_id = (int)($_GET['event_id'] ?? 0);
    $filter = $_GET['filter'] ?? 'all';

    // Verify the event belongs to the logged-in user
    try {
        $verify_stmt = $db->prepare("SELECT event_id FROM invitationapp_events WHERE event_id = :event_id AND host_id = :host_id");
        $verify_stmt->execute([':event_id' => $event_id, ':host_id' => $user_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Event not found']);
            exit;
        }

        // Build query based on filter
        $sql = "SELECT COUNT(DISTINCT r.recipient_id) as count 
                FROM invitationapp_rsvps r 
                WHERE r.event_id = :event_id";
        
        $params = [':event_id' => $event_id];
        
        switch ($filter) {
            case 'going':
                $sql .= " AND r.response = 'Going'";
                break;
            case 'not_going':
                $sql .= " AND r.response = 'Not Going'";
                break;
            case 'maybe':
                $sql .= " AND r.response = 'Maybe'";
                break;
            case 'no_response':
                $sql .= " AND r.response = 'No Response'";
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => (int)$result['count']]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle POST request - send announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $filter = $_POST['recipient_filter'] ?? 'all';
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation
    if (empty($subject) || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
        exit;
    }

    if (strlen($subject) > 100) {
        echo json_encode(['success' => false, 'error' => 'Subject must be 100 characters or less']);
        exit;
    }

    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'error' => 'Message must be 5000 characters or less']);
        exit;
    }

    try {
        // Verify the event belongs to the logged-in user
        $verify_stmt = $db->prepare("SELECT event_id FROM invitationapp_events WHERE event_id = :event_id AND host_id = :host_id");
        $verify_stmt->execute([':event_id' => $event_id, ':host_id' => $user_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Event not found or access denied']);
            exit;
        }

        // Build query to get recipients based on filter
        $sql = "SELECT DISTINCT r.recipient_id 
                FROM invitationapp_rsvps r 
                WHERE r.event_id = :event_id";
        
        $params = [':event_id' => $event_id];
        
        switch ($filter) {
            case 'going':
                $sql .= " AND r.response = 'Going'";
                break;
            case 'not_going':
                $sql .= " AND r.response = 'Not Going'";
                break;
            case 'maybe':
                $sql .= " AND r.response = 'Maybe'";
                break;
            case 'no_response':
                $sql .= " AND r.response = 'No Response'";
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($recipients)) {
            echo json_encode(['success' => false, 'error' => 'No recipients found matching the selected filter']);
            exit;
        }

        // Begin transaction
        $db->beginTransaction();
        
        // Insert message for each recipient
        $insert_stmt = $db->prepare("
            INSERT INTO invitationapp_messages (event_id, sender_id, recipient_id, subject, content)
            VALUES (:event_id, :sender_id, :recipient_id, :subject, :content)
        ");
        
        $sent_count = 0;
        foreach ($recipients as $recipient_id) {
            $insert_stmt->execute([
                ':event_id' => $event_id,
                ':sender_id' => $user_id,
                ':recipient_id' => $recipient_id,
                ':subject' => $subject,
                ':content' => $content
            ]);
            $sent_count++;
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Announcement sent successfully',
            'recipients_count' => $sent_count
        ]);

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Invalid request method
echo json_encode(['success' => false, 'error' => 'Invalid request method']);