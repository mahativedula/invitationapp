<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-connect.php';

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
    FROM invitationapp_events e
    INNER JOIN invitationapp_rsvps r ON e.event_id = r.event_id
    INNER JOIN invitationapp_users u ON e.host_id = u.user_id
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
                UPDATE invitationapp_rsvps 
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
        <a href="index.php?page=create-event">Create Event</a>
        <a href="index.php?page=host-dashboard">My Events</a>
        <a href="index.php?page=invitation">My Invites</a>
        <a href="index.php?page=messages">Messages</a>
        <a href="settings.html">Settings</a>
        <a href="index.php?page=login">Logout</a>
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
                <a href="index.php?page=compose-message&recipient_id=<?php echo $invite['host_id']; ?>&event_id=<?php echo $invite['event_id']; ?>">
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
        // Arrow function to handle RSVP changes with AJAX
        const updateRSVP = (rsvpId, response, selectElement, row) => {
            fetch('index.php?page=invitation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_rsvp&rsvp_id=${rsvpId}&response=${encodeURIComponent(response)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // DOM manipulation - visual feedback with style change
                    row.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 1000);
                    console.log('RSVP updated successfully');
                } else {
                    alert('Error updating RSVP: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating your RSVP');
            });
        };
        
        // Event listener for RSVP select changes
        document.querySelectorAll('.rsvp-select').forEach(select => {
            select.addEventListener('change', function() {
                const rsvpId = this.dataset.rsvpId;
                const response = this.value;
                const row = this.closest('tr');
                
                updateRSVP(rsvpId, response, this, row);
            });
        });
        
        // Style modification on event - button hover effects using arrow functions
        document.querySelectorAll('.message-btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'scale(1.05)';
                btn.style.transition = 'transform 0.2s ease';
                btn.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'scale(1)';
                btn.style.boxShadow = '';
            });
        });
        
        // Style modification on event - RSVP select focus
        document.querySelectorAll('.rsvp-select').forEach(select => {
            select.addEventListener('focus', function() {
                this.style.borderColor = 'lightgreen';
                this.style.boxShadow = '0 0 5px rgba(76, 175, 80, 0.5)';
            });
            
            select.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        });
    </script>
</body>
</html>