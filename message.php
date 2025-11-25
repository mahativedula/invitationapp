<?php
// Database connection ($db) and session already available from index.php - UPDATE BASED on Mahati changes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=messages");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle viewing status update 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'toggle_viewed') {
        $message_id = $_POST['message_id'] ?? null;
        $viewed = $_POST['viewed'] ?? false;
        
        if ($message_id) {
            try {
                $updateStmt = $db->prepare("
                    UPDATE invitationapp_messages 
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
$stmt = $db->prepare("
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
    FROM invitationapp_messages m
    INNER JOIN invitationapp_users u ON m.sender_id = u.user_id
    LEFT JOIN invitationapp_events e ON m.event_id = e.event_id
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
</head>

<body>
    <br>
    <div class="navbar">
        <a href="index.php?page=create-event">Create Event</a>
        <a href="index.php?page=host-dashboard">My Events</a>
        <a href="index.php?page=invitation">My Invites</a>
        <a href="index.php?page=messages">Messages</a>
        <a href="index.php?page=settings">Settings</a>
        <a href="index.php?page=login">Logout</a>
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
            <li><h1><a href="index.php?page=sent">Sent Items</a></h1></li>
            <li><h1><a href="index.php?page=compose-message">Compose</a></h1></li>
        </nav>
        
        <!-- SEARCH BAR -->
        <div class="search-bar">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="messages">
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

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Using jQuery for all interactions on this page
        $(document).ready(function() {
            // Modal functionality with jQuery animations
            $('.message-link').on('click', function(e) {
                e.preventDefault();
                
                $('#modalSubject').text($(this).data('subject'));
                $('#modalSender').text($(this).data('sender'));
                $('#modalDate').text($(this).data('date'));
                $('#modalEvent').text($(this).data('event'));
                $('#modalContent').text($(this).data('content'));
                
                $('#messageModal').fadeIn(300);
            });
            
            // Close modal
            $('.close').on('click', function() {
                $('#messageModal').fadeOut(300);
            });
            
            $(window).on('click', function(event) {
                if ($(event.target).is('#messageModal')) {
                    $('#messageModal').fadeOut(300);
                }
            });
            
            // Handle viewed checkbox changes with jQuery AJAX
            $('.viewed-checkbox').on('change', function() {
                const $checkbox = $(this);
                const messageId = $checkbox.data('message-id');
                const viewed = $checkbox.is(':checked');
                
                $.ajax({
                    url: 'index.php?page=messages',
                    method: 'POST',
                    data: {
                        action: 'toggle_viewed',
                        message_id: messageId,
                        viewed: viewed
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            const $row = $checkbox.closest('tr');
                            if (viewed) {
                                $row.removeClass('unread').fadeOut(100).fadeIn(100);
                            } else {
                                $row.addClass('unread').fadeOut(100).fadeIn(100);
                            }
                        } else {
                            alert('Error updating message status');
                            $checkbox.prop('checked', !viewed);
                        }
                    },
                    error: function() {
                        alert('An error occurred');
                        $checkbox.prop('checked', !viewed);
                    }
                });
            });
            
            // Search box enhancement with jQuery (style modification on event)
            $('input[name="search"]').on('focus', function() {
                $(this).css({
                    'background-color': 'white',
                    'border-color': 'lightgreen'
                });
            }).on('blur', function() {
                $(this).css({
                    'background-color': '',
                    'border-color': ''
                });
            });
        });
    </script>
</body>
</html>
