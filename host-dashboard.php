<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db-connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host_id = $_SESSION['user_id'];

// --- Handle deletion if form submitted ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = (int) $_POST['event_id'];

    try {
        $stmt = $db->prepare("DELETE FROM events WHERE event_id = :event_id AND host_id = :host_id");
        $stmt->execute([':event_id' => $event_id, ':host_id' => $host_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: host-dashboard.php?status=deleted");
        } else {
            header("Location: host-dashboard.php?status=notfound");
        }
        exit;
    } catch (PDOException $e) {
        header("Location: host-dashboard.php?status=error");
        exit;
    }
}

// --- Load events for this host ---
try {
    $stmt = $db->prepare("SELECT event_id, event_name, date, start_time, end_time FROM events WHERE host_id = :host_id ORDER BY date ASC");
    $stmt->execute([':host_id' => $host_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error loading your events: " . htmlspecialchars($e->getMessage()) . "</p>";
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Host Dashboard</title>
    <link rel="stylesheet" href="styles/host-dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta author content="Mahati Vedula">
</head>

<body>
    <br><br>
    <!-- header menu -->
    <div class="navbar">
        <a href="event-creation.php">Create Event</a>
        <a href="host-dashboard.php">My Events</a>
        <a href="invitation.php">My Invites</a>
        <a href="message.php">Messages</a>
        <a href="settings.php">Settings</a>
        <a href="login.php">Logout</a>
    </div>
    <br><br>
    <h1>Host Dashboard</h1>
    <div class="main-container">
        <!-- sidebar of all the host's events -->
        <div class="sidebar col-4">
            <h2>My Events</h2>

            <?php if (empty($events)): ?>
                <p>You haven't created any events yet.</p>
                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.getElementById('eventPopup').style.display = 'none';
                });
                </script>
            <?php else: ?>
                <?php foreach ($events as $index => $event): ?>
                    <div 
                        class="event-item" 
                        id="<?php echo $index === 0 ? 'first-event' : ''; ?>"
                        onclick="openEvent(
                            '<?php echo htmlspecialchars(addslashes($event['event_name'])); ?>', 
                            '<?php echo htmlspecialchars(date('F j, Y', strtotime($event['date']))); ?>', 
                            '<?php echo htmlspecialchars(substr($event['start_time'], 0, 5)); ?>',
                            <?php echo (int)$event['event_id']; ?>
                        )">
                        <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                        <p>
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($event['date']))); ?> 
                            - <?php echo htmlspecialchars(substr($event['start_time'], 0, 5)); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- event view -->
        <div class="col-6" style="margin-left: 50px;"> 
            <div class="event-popup" id="eventPopup">
                <div class="event-header">
                    <div class="event-image">🎈</div>
                    <div class="event-details">
                        <h2 id="popupTitle">Event Title</h2>
                        <p><strong>Date:</strong> <span id="popupDate"></span></p>
                        <p><strong>Time:</strong> <span id="popupTime"></span></p>
                        <button>Edit details</button>
                    </div>
                </div>

                <!-- Guest list -->
                <div class="guest-list">
                    <h3>Guest List</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>RSVP</th>
                            </tr>
                        </thead>
                        <tbody id="guestTableBody">
                            <tr><td colspan="2">Select an event to view guests</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="buttons">
                    <button>Add guests</button>
                    <button>Send announcement</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="deleteEventId" name="event_id">
                        <button type="submit" id="deleteButton" onclick="return confirm('Are you sure you want to delete this event?')">
                            Delete Event
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Updated JS -->
    <script>
        function openEvent(title, date, time, eventId) {
            document.getElementById('popupTitle').textContent = title;
            document.getElementById('popupDate').textContent = date;
            document.getElementById('popupTime').textContent = time;
            document.getElementById('eventPopup').classList.add('active');

            // Set the event ID for delete form
            document.getElementById('deleteEventId').value = eventId;

            // Load guests dynamically (same as before)
            fetch(`get-guests.php?event_id=${eventId}`)
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('guestTableBody');
                    tbody.innerHTML = '';
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2">No guests yet.</td></tr>';
                    } else {
                        data.forEach(g => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${g.name}</td>
                                    <td>${g.response}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('guestTableBody').innerHTML =
                        '<tr><td colspan="2" style="color:red;">Error loading guests</td></tr>';
                });
        }

    </script>
    <script>
    // After the page loads, automatically open the first event (if it exists)
    document.addEventListener('DOMContentLoaded', function() {
        const firstEventDiv = document.getElementById('first-event');
        if (firstEventDiv) {
            firstEventDiv.click(); // triggers openEvent() for the first event
        }
    });
    </script>

</body>
</html>
