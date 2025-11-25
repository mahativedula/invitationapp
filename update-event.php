<?php
header('Content-Type: application/json');

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once 'db-connect.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success' => false, 'message' => 'Not logged in!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method!']);
    exit;
}

$host_id = $_SESSION['user_id'];
$event_id = (int)($_POST['event_id'] ?? 0);
$event_name = trim($_POST['event_name'] ?? '');
$date = $_POST['date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

if(empty($end_time)){
    $end_time = null;
}

$errors = [];

if(empty($event_name) || empty($date) || empty($start_time) || empty($location) || empty($description)){
    $errors[] = 'Please fill out all required fields!';
}

// Event name validation
$event_name_pattern = '/^[A-Za-z0-9\s.,!?\'"-]{3,100}$/';
if (!preg_match($event_name_pattern, $event_name)) {
    $errors[] = "Event name must be 3–100 characters and contain only letters, numbers, or punctuation.";
}

// Location validation
$location_pattern = '/^[A-Za-z0-9\s.,#-]{3,255}$/';
if (!preg_match($location_pattern, $location)) {
    $errors[] = "Location must be valid and cannot contain special symbols.";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE invitationapp_events 
        SET event_name = :event_name, 
        date = :date, 
        start_time = :start_time, 
        end_time = :end_time, 
        location = :location, 
        description = :description 
        WHERE event_id = :event_id AND host_id = :host_id
    ");

    $stmt->execute([
        ':event_name' => $event_name,
        ':date' => $date,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':location' => $location,
        ':description' => $description,
        ':event_id' => $event_id,
        ':host_id' => $host_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Event not found or no changes made']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}