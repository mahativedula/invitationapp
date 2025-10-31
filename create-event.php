<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>You must be logged in to create an event.</p>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $host_id = $_SESSION['user_id'];

    // Guests (array of { name, email }) from hidden input JSON
    $guest_data_json = $_POST['guest_data'] ?? '[]';
    $guests = json_decode($guest_data_json, true);

    if (empty($event_name) || empty($date) || empty($time) || empty($location)) {
        echo "<p style='color:red;'>Please fill out all required fields.</p>";
        exit;
    }

    try {
        // Begin transaction so event + invites are atomic
        $db->beginTransaction();

        // 1️⃣ Insert event
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

        $event_id = $stmt->fetchColumn();

        // 2️⃣ Invite guests by adding them to rsvps (default 'No Response')
        if (!empty($guests) && is_array($guests)) {
            $rsvp_stmt = $db->prepare("
                INSERT INTO rsvps (event_id, recipient_id, response)
                VALUES (:event_id, :recipient_id, 'No Response')
            ");

            // Lookup each guest by email (since you don’t have guest_name/guest_email in rsvps)
            $user_lookup = $db->prepare("SELECT user_id FROM users WHERE email = :email");

            foreach ($guests as $guest) {
                $guest_email = trim($guest['email'] ?? '');

                if (empty($guest_email)) {
                    continue;
                }

                // Find user by email
                $user_lookup->execute([':email' => $guest_email]);
                $recipient_id = $user_lookup->fetchColumn();

                // Only invite registered users
                if ($recipient_id) {
                    $rsvp_stmt->execute([
                        ':event_id' => $event_id,
                        ':recipient_id' => $recipient_id
                    ]);
                }
            }
        }

        // Commit all changes
        $db->commit();

        // Redirect to host dashboard
        header("Location: index.php?page=host-dashboard&created_event={$event_id}");
        exit;

    } catch (PDOException $e) {
        $db->rollBack();
        echo "<p style='color:red;'>Error creating event: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
