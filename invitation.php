<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=invitation");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all events where the user has been invited
$stmt = $db->prepare("
    SELECT 
        e.event_id,
        e.event_name,
        e.date,
        e.start_time,
        e.end_time,
        e.location,
        e.description,
        r.response,
        r.rsvp_id,
        u.first_name as host_first_name,
        u.last_name as host_last_name,
        u.user_id as host_id
    FROM events e
    INNER JOIN rsvps r ON e.event_id = r.event_id
    INNER JOIN users u ON e.host_id = u.user_id
    WHERE r.recipient_id = :user_id
    AND e.date >= CURRENT_DATE
    ORDER BY e.date ASC, e.start_time ASC
");
$stmt->execute(['user_id' => $user_id]);
$invitations = $stmt->fetchAll();

// Handle RSVP updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_rsvp') {
    header('Content-Type: application/json');
    
    $rsvp_id = $_POST['rsvp_id'] ?? null;
    $response = $_POST['response'] ?? null;
    
    if ($rsvp_id && $response && in_array($response, ['Going', 'Not Going', 'Maybe'])) {
        try {
            $updateStmt = $db->prepare("
                UPDATE rsvps 
                SET response = :response, responded_at = CURRENT_TIMESTAMP 
                WHERE rsvp_id = :rsvp_id AND recipient_id = :user_id
            ");
            $updateStmt->execute([
                'response' => $response,
                'rsvp_id' => $rsvp_id,
                'user_id' => $user_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'RSVP updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating RSVP']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invites</title>
    <link rel="stylesheet" href="styles/invitation.css" />
    <meta name="author" content="Fiona Fitzsimons">
</head>

<body>
    <!-- HEADER -->
    <br>
    <div class="navbar">
        <a href="event-creation.php">Create Event</a>
        <a href="host-dashboard.php">My Events</a>
        <a href="invitation.php">My Invites</a>
        <a href="message.php">Messages</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </div>
    <br>
    <br>
    <h1>Upcoming Invites</h1>
    <br>

    <table>
        <tr>
            <th>Event</th>
            <th>Date</th>
            <th>RSVP Status</th>
            <th>Message Host</th>
        </tr>
        <?php if (empty($invitations)): ?>
        <tr>
            <td colspan="4" style="text-align: center;">No upcoming invitations</td>
        </tr>
        <?php else: ?>
            <?php foreach ($invitations as $invite): ?>
        <tr>
            <td>
                <div class="event-cell">
                    <div class="event-image"></div>
                    <span><?php echo htmlspecialchars($invite['event_name']); ?></span>
                </div>
                <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                    <?php echo htmlspecialchars($invite['location']); ?><br>
                    <?php echo date('g:i A', strtotime($invite['start_time'])); ?>
                    <?php if ($invite['end_time']): ?>
                        - <?php echo date('g:i A', strtotime($invite['end_time'])); ?>
                    <?php endif; ?>
                </div>
            </td>
            <td><?php echo date('m/d/Y', strtotime($invite['date'])); ?></td>
            <td>
                <select class="rsvp-select" data-rsvp-id="<?php echo $invite['rsvp_id']; ?>">
                    <option value="Going" <?php echo $invite['response'] === 'Going' ? 'selected' : ''; ?>>Going</option>
                    <option value="Not Going" <?php echo $invite['response'] === 'Not Going' ? 'selected' : ''; ?>>Not Going</option>
                    <option value="Maybe" <?php echo $invite['response'] === 'Maybe' ? 'selected' : ''; ?>>Maybe</option>
                </select>
            </td>
            <td>
                <a href="compose-message.php?recipient_id=<?php echo $invite['host_id']; ?>&event_id=<?php echo $invite['event_id']; ?>">
                    <button class="message-btn">
                        <?php echo htmlspecialchars($invite['host_first_name'] . ' ' . $invite['host_last_name']); ?>
                    </button>
                </a>
            </td>
        </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <script>
        // Handle RSVP changes
        document.querySelectorAll('.rsvp-select').forEach(select => {
            select.addEventListener('change', function() {
                const rsvpId = this.dataset.rsvpId;
                const response = this.value;
                
                fetch('invitation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_rsvp&rsvp_id=${rsvpId}&response=${encodeURIComponent(response)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Optional: Show success message
                        console.log('RSVP updated successfully');
                    } else {
                        alert('Error updating RSVP: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating your RSVP');
                });
            });
        });
    </script>
</body>
</html>