<?php
// Database connection ($db) and session already available from index.php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get recipient and event from URL parameters
$recipient_id = $_GET['recipient_id'] ?? null;
$event_id = $_GET['event_id'] ?? null;

// Fetch recipient info if provided
$recipient = null;
if ($recipient_id) {
    $stmt = $db->prepare("SELECT user_id, first_name, last_name FROM invitationapp_users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $recipient_id]);
    $recipient = $stmt->fetch();
}

// Fetch event info if provided
$event = null;
if ($event_id) {
    $stmt = $db->prepare("SELECT event_id, event_name FROM invitationapp_events WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $event_id]);
    $event = $stmt->fetch();
}

// Fetch all users for recipient selection
$stmt = $db->prepare("SELECT user_id, first_name, last_name FROM invitationapp_users WHERE user_id != :user_id ORDER BY first_name, last_name");
$stmt->execute(['user_id' => $user_id]);
$all_users = $stmt->fetchAll();

// Handle message submission
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id'] ?? null;
    $event_id = $_POST['event_id'] ?? null;
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (!$recipient_id) {
        $error = "Please select a recipient";
    } elseif (empty($subject)) {
        $error = "Please enter a subject";
    } elseif (empty($content)) {
        $error = "Please enter a message";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO invitationapp_messages (event_id, sender_id, recipient_id, subject, content)
                VALUES (:event_id, :sender_id, :recipient_id, :subject, :content)
            ");
            
            $stmt->execute([
                'event_id' => $event_id ?: null,
                'sender_id' => $user_id,
                'recipient_id' => $recipient_id,
                'subject' => $subject,
                'content' => $content
            ]);
            
            $success = true;
        } catch (Exception $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Compose Message</title>
    <link rel="stylesheet" href="styles/compose.css" />
    <meta name="author" content="Fiona Fitzsimons">
</head>

<body>
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
    <br>

    <!-- HEADER -->
    <header>
        <h1>Messages</h1>
        <nav>
            <li><h1><a href="index.php?page=messages">Inbox</a></h1></li>
            <li><h1><a href="index.php?page=sent">Sent Items</a></h1></li>
            <li><h1>Compose</h1></li>
        </nav>
        
        <!-- SEARCH BAR (empty for compose page) -->
        <div class="search-bar">
        </div>
    </header>

    <div class="compose-form">
        <h2>Compose Message</h2>
        
        <?php if ($success): ?>
        <div class="success-message">
            Message sent successfully! <a href="index.php?page=sent">View sent messages</a> | <a href="index.php?page=messages">Back to inbox</a>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="index.php?page=compose-message<?php echo $event_id ? '&event_id=' . $event_id : ''; ?>">
            <div class="form-group">
                <label for="recipient_id">To:</label>
                <select name="recipient_id" id="recipient_id" required <?php echo $recipient ? 'disabled' : ''; ?>>
                    <option value="">-- Select Recipient --</option>
                    <?php foreach ($all_users as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>" 
                            <?php echo ($recipient && $recipient['user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($recipient): ?>
                <input type="hidden" name="recipient_id" value="<?php echo $recipient['user_id']; ?>">
                <?php endif; ?>
            </div>
            
            <?php if ($event): ?>
            <div class="form-group">
                <label>Event:</label>
                <input type="text" value="<?php echo htmlspecialchars($event['event_name']); ?>" readonly>
                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" name="subject" id="subject" maxlength="100" required>
            </div>
            
            <div class="form-group">
                <label for="content">Message:</label>
                <textarea name="content" id="content" maxlength="5000" required></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Send Message</button>
            <button type="button" class="btn-cancel" onclick="window.history.back()">Cancel</button>
        </form>
    </div>
</body>
</html>



