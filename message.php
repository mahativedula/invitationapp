<?php
require_once 'config.php';
requireLogin();

$user_id = getCurrentUserId();
$conn = getDBConnection();

// Handle viewing status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'toggle_viewed') {
        $message_id = $_POST['message_id'] ?? null;
        $viewed = $_POST['viewed'] ?? false;
        
        if ($message_id) {
            try {
                $updateStmt = $conn->prepare("
                    UPDATE messages 
                    SET viewed = :viewed 
                    WHERE message_id = :message_id AND recipient_id = :user_id
                ");
                $updateStmt->execute([
                    'viewed' => $viewed ? 1 : 0,
                    'message_id' => $message_id,
                    'user_id' => $user_id
                ]);
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating message status']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
        }
        exit();
    }
}

// Handle search
$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

// Fetch received messages
$stmt = $conn->prepare("
    SELECT 
        m.message_id,
        m.subject,
        m.content,
        m.viewed,
        m.sent_at,
        u.first_name as sender_first_name,
        u.last_name as sender_last_name,
        u.user_id as sender_id,
        e.event_name
    FROM messages m
    INNER JOIN users u ON m.sender_id = u.user_id
    LEFT JOIN events e ON m.event_id = e.event_id
    WHERE m.recipient_id = :user_id
    AND (m.subject LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)
    ORDER BY m.sent_at DESC
");
$stmt->execute([
    'user_id' => $user_id,
    'search' => $searchParam
]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages</title>
    <link rel="stylesheet" href="styles/message.css" />
    <meta name="author" content="Fiona Fitzsimons">
    <style>
        /* Modal styles only */
        .message-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: #000;
        }
        
        .message-link {
            color: #0066cc;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .message-link:hover {
            text-decoration: underline;
        }
        
        .unread {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <br>
    <div class="navbar">
        <a href="event-creation.html">Create Event</a>
        <a href="host-dashboard.html">My Events</a>
        <a href="invitation.html">My Invites</a>
        <a href="message.html">Messages</a>
        <a href="settings.html">Settings</a>
        <a href="index.html">Logout</a>
    </div>
    <br>
    <br>
    <br>

    <!-- HEADER -->
    <header>
        <!-- NAVIGATION MENU -->
        <h1>Messages</h1>
        <nav>
            <li><h1>Inbox</h1></li>
            <li><h1><a href="sent.php">Sent Items</a></h1></li>
        </nav>
        
        <!-- SEARCH BAR -->
        <div class="search-bar">
            <form method="GET" action="message.php">
                <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
    </header>

    <table>
        <tr>
            <th>Message Subject</th>
            <th>Date Sent</th>
            <th>Sender</th>
            <th>Viewed</th>
        </tr>
        <?php if (empty($messages)): ?>
        <tr>
            <td colspan="4" style="text-align: center;">No messages found</td>
        </tr>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
        <tr class="<?php echo !$msg['viewed'] ? 'unread' : ''; ?>">
            <td>
                <a href="#" class="message-link" 
                   data-message-id="<?php echo $msg['message_id']; ?>"
                   data-subject="<?php echo htmlspecialchars($msg['subject']); ?>"
                   data-content="<?php echo htmlspecialchars($msg['content']); ?>"
                   data-sender="<?php echo htmlspecialchars($msg['sender_first_name'] . ' ' . $msg['sender_last_name']); ?>"
                   data-date="<?php echo date('F j, Y g:i A', strtotime($msg['sent_at'])); ?>"
                   data-event="<?php echo htmlspecialchars($msg['event_name'] ?? 'N/A'); ?>">
                    <?php echo htmlspecialchars($msg['subject']); ?>
                </a>
            </td>
            <td><?php echo date('m/d/Y', strtotime($msg['sent_at'])); ?></td>
            <td><?php echo htmlspecialchars($msg['sender_first_name'] . ' ' . $msg['sender_last_name']); ?></td>
            <td>
                <input type="checkbox" 
                       class="viewed-checkbox" 
                       data-message-id="<?php echo $msg['message_id']; ?>"
                       <?php echo $msg['viewed'] ? 'checked' : ''; ?>>
            </td>
        </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Message Modal -->
    <div id="messageModal" class="message-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalSubject"></h2>
            <p><strong>From:</strong> <span id="modalSender"></span></p>
            <p><strong>Date:</strong> <span id="modalDate"></span></p>
            <p><strong>Event:</strong> <span id="modalEvent"></span></p>
            <hr>
            <p id="modalContent"></p>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('messageModal');
        const closeBtn = document.querySelector('.close');
        
        // Open modal when clicking message link
        document.querySelectorAll('.message-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                document.getElementById('modalSubject').textContent = this.dataset.subject;
                document.getElementById('modalSender').textContent = this.dataset.sender;
                document.getElementById('modalDate').textContent = this.dataset.date;
                document.getElementById('modalEvent').textContent = this.dataset.event;
                document.getElementById('modalContent').textContent = this.dataset.content;
                
                modal.style.display = 'block';
            });
        });
        
        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Handle viewed checkbox changes
        document.querySelectorAll('.viewed-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const messageId = this.dataset.messageId;
                const viewed = this.checked;
                
                fetch('message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_viewed&message_id=${messageId}&viewed=${viewed}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update row styling
                        const row = this.closest('tr');
                        if (viewed) {
                            row.classList.remove('unread');
                        } else {
                            row.classList.add('unread');
                        }
                    } else {
                        alert('Error updating message status');
                        this.checked = !viewed; // Revert checkbox
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                    this.checked = !viewed; // Revert checkbox
                });
            });
        });
    </script>
</body>
</html>
