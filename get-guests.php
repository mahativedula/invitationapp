<?php
require_once 'db-connect.php';

$event_id = $_GET['event_id'] ?? null;

if (!$event_id || !is_numeric($event_id)) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            r.response
        FROM invitationapp_rsvps r
        JOIN invitationapp_users u ON r.recipient_id = u.user_id
        WHERE r.event_id = :event_id
        ORDER BY name ASC
    ");
    $stmt->execute([':event_id' => $event_id]);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($guests);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
