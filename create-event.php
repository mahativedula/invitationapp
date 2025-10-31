<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>You must be logged in to create an event.</p>";
    exit;
}

// ------------------ USER-DEFINED FUNCTIONS ------------------ //

/**
 * Validate event form input
 */
function validate_event_data($event_name, $date, $time, $location, $guests) {
    $errors = [];

    // basic required field check
    if (empty($event_name) || empty($date) || empty($time) || empty($location)) {
        $errors[] = "Please fill out all required fields.";
    }

    // event name: allow letters, numbers, punctuation
    $event_name_pattern = '/^[A-Za-z0-9\s.,!?\'"-]{3,100}$/';
    if (!preg_match($event_name_pattern, $event_name)) {
        $errors[] = "Event name must be 3–100 characters and contain only letters, numbers, or punctuation.";
    }

    // location: simple check (no special characters like < >)
    $location_pattern = '/^[A-Za-z0-9\s.,#-]{3,255}$/';
    if (!preg_match($location_pattern, $location)) {
        $errors[] = "Location must be valid and cannot contain special symbols.";
    }

    // guest email validation
    $email_pattern = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    foreach ($guests as $guest) {
        $email = trim($guest['email'] ?? '');
        if ($email && !preg_match($email_pattern, $email)) {
            $errors[] = "Invalid guest email: " . htmlspecialchars($email);
        }
    }

    return $errors;
}

/**
 * Insert a new event and return its ID
 */
function create_event($db, $host_id, $event_name, $date, $time, $location, $description) {
    $stmt = $db->prepare("
        INSERT INTO events (host_id, event_name, date, start_time, location, description)
        VALUES (:host_id, :event_name, :date, :start_time, :location, :description)
        RETURNING event_id;
    ");
    $stmt->execute([
        ':host_id' => $host_id,
        ':event_name' => $event_name,
        ':date' => $date,
        ':start_time' => $time,
        ':location' => $location,
        ':description' => $description
    ]);
    return $stmt->fetchColumn();
}

/**
 * Add RSVPs for all valid registered users (default 'No Response')
 */
function invite_registered_guests($db, $event_id, $guests) {
    $rsvp_stmt = $db->prepare("
        INSERT INTO rsvps (event_id, recipient_id, response)
        VALUES (:event_id, :recipient_id, 'No Response')
        ON CONFLICT DO NOTHING
    ");
    $user_lookup = $db->prepare("SELECT user_id FROM users WHERE email = :email");

    foreach ($guests as $guest) {
        $email = trim($guest['email'] ?? '');
        if (!$email) continue;

        $user_lookup->execute([':email' => $email]);
        $recipient_id = $user_lookup->fetchColumn();

        if ($recipient_id) {
            $rsvp_stmt->execute([
                ':event_id' => $event_id,
                ':recipient_id' => $recipient_id
            ]);
        }
    }
}

/**
 * Display error messages nicely
 */
function display_errors($errors) {
    foreach ($errors as $err) {
        echo "<p style='color:red;'>" . htmlspecialchars($err) . "</p>";
    }
}

// ------------------ MAIN FORM HANDLING ------------------ //

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $host_id = $_SESSION['user_id'];
    $guest_data_json = $_POST['guest_data'] ?? '[]';
    $guests = json_decode($guest_data_json, true);

    // 1️⃣ validate
    $errors = validate_event_data($event_name, $date, $time, $location, $guests);
    if (!empty($errors)) {
        display_errors($errors);
        exit;
    }

    try {
        $db->beginTransaction();

        // 2️⃣ create event
        $event_id = create_event($db, $host_id, $event_name, $date, $time, $location, $description);

        // 3️⃣ add RSVPs
        invite_registered_guests($db, $event_id, $guests);

        $db->commit();

        header("Location: host-dashboard.php?status=created");
        exit;

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p style='color:red;'>Error creating event: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
