<?php
// Database connection ($db) and session already available from index.php - not anymore
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=sent");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle search
$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

// Fetch sent messages
$stmt = $db->prepare("
    SELECT 
        m.message_id,
        m.subject,
        m.content,
        m.sent_at,
        u.first_name as recipient_first_name,
        u.last_name as recipient_last_name,
        u.user_id as recipient_id,
        e.event_name
    FROM invitationapp_messages m
    INNER JOIN invitationapp_users u ON m.recipient_id = u.user_id
    LEFT JOIN invitationapp_events e ON m.event_id = e.event_id
    WHERE m.sender_id = :user_id
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
    <title>Sent Messages</title>
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
            background-color: white;
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
    </style>
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
        <h1>Messages</h1>
        <nav>
            <li><h1><a href="index.php?page=messages">Inbox</a></h1></li>
            <li><h1>Sent</h1></li>
            <li><h1><a href="index.php?page=compose-message">Compose</a></h1></li>
        </nav>

        <!-- SEARCH BAR -->
        <div class="search-bar">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="sent">
                <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
    </header>

    <table>
        <tr>
            <th>Message Subject</th>
            <th>Date Sent</th>
            <th>Sent To</th>
        </tr>
        <?php if (empty($messages)): ?>
        <tr>
            <td colspan="3" style="text-align: center;">No sent messages found</td>
        </tr>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
        <tr>
            <td>
                <a href="#" class="message-link"
                   data-message-id="<?php echo $msg['message_id']; ?>"
                   data-subject="<?php echo htmlspecialchars($msg['subject']); ?>"
                   data-content="<?php echo htmlspecialchars($msg['content']); ?>"
                   data-recipient="<?php echo htmlspecialchars($msg['recipient_first_name'] . ' ' . $msg['recipient_last_name']); ?>"
                   data-date="<?php echo date('F j, Y g:i A', strtotime($msg['sent_at'])); ?>"
                   data-event="<?php echo htmlspecialchars($msg['event_name'] ?? 'N/A'); ?>">
                    <?php echo htmlspecialchars($msg['subject']); ?>
                </a>
            </td>
            <td><?php echo date('m/d/Y g:i A', strtotime($msg['sent_at'])); ?></td>
            <td><?php echo htmlspecialchars($msg['recipient_first_name'] . ' ' . $msg['recipient_last_name']); ?></td>
        </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Message Modal -->
    <div id="messageModal" class="message-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalSubject"></h2>
            <p><strong>To:</strong> <span id="modalRecipient"></span></p>
            <p><strong>Date:</strong> <span id="modalDate"></span></p>
            <p><strong>Event:</strong> <span id="modalEvent"></span></p>
            <hr>
            <p id="modalContent"></p>
        </div>
    </div>

    <script>
        // Self-invoking anonymous function to encapsulate modal logic
        (function() {
            const modal = document.getElementById('messageModal');
            const closeBtn = document.querySelector('.close');
            
            // Arrow function for opening modal with event listener
            const openModal = (e) => {
                e.preventDefault();
                
                document.getElementById('modalSubject').textContent = e.currentTarget.dataset.subject;
                document.getElementById('modalRecipient').textContent = e.currentTarget.dataset.recipient;
                document.getElementById('modalDate').textContent = e.currentTarget.dataset.date;
                document.getElementById('modalEvent').textContent = e.currentTarget.dataset.event;
                document.getElementById('modalContent').textContent = e.currentTarget.dataset.content;
                
                modal.style.display = 'block';
            };
            
            // Arrow function for closing modal
            const closeModal = () => {
                modal.style.display = 'none';
            };
            
            // Open modal when clicking message link - using arrow function in forEach
            document.querySelectorAll('.message-link').forEach(link => {
                link.addEventListener('click', openModal);
            });
            
            // Close modal handlers
            closeBtn.addEventListener('click', closeModal);
            
            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
        
        // Style modification on event - search box interactions
        const searchInput = document.querySelector('input[name="search"]');
        
        searchInput.addEventListener('focus', function() {
            this.style.backgroundColor = 'white';
            this.style.borderColor = 'lightgreen';
            this.style.boxShadow = '0 0 5px rgba(76, 175, 80, 0.5)';
            this.style.transition = 'all 0.3s ease';
        });
        
        searchInput.addEventListener('blur', function() {
            this.style.backgroundColor = '';
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    </script>
</body>
</html>