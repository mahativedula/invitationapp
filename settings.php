<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Load current user data
try {
    $stmt = $db->prepare("SELECT username, email FROM invitationapp_users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Error: User not found.";
        exit;
    }
} catch (PDOException $e) {
    echo "Error loading user data: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Settings</title>
    <link rel="stylesheet" href="styles/settings.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta author content="Mahati Vedula">
    <style>
        .sidebar div {
            cursor: pointer;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .sidebar div:hover {
            background-color: #f0f0f0;
        }
        
        .sidebar div.active {
            background-color: #007bff;
            color: white;
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head> 

<body>
    <br><br>
    <!-- menu navigation -->
    <div class="navbar">
        <a href="event-creation.php">Create Event</a>
        <a href="host-dashboard.php">My Events</a>
        <a href="invitation.php">My Invites</a>
        <a href="message.php">Messages</a>
        <a href="settings.php">Settings</a>
        <a href="login.php">Logout</a>
    </div>
    <br>
    <h1>Settings</h1>
    
    <div class="main-container">
        <!-- sidebar with setting-related concerns -->
        <div class="sidebar col-3">
            <div class="active" onclick="showSection('profile')">
                <strong>Profile</strong>
            </div>
            <div onclick="showSection('privacy')">
                <strong>Privacy</strong>
            </div>
            <div onclick="showSection('notifications')">
                <strong>Notifications</strong>
            </div>
            <div onclick="showSection('contacts')">
                <strong>Contacts</strong>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="main-content col-6">
            
            <!-- Profile Section -->
            <div id="profile-section" class="settings-section active">
                <h2>Account Settings</h2>
                <div id="profileMessage"></div>
                <form id="profileForm">
                    <label for="username">Update Username:</label><br>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br>
                    
                    <label for="email">Update Email:</label><br>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>
                    <br>
                    <input type="submit" value="Update Profile">
                </form>
                
                <br><hr><br>
                
                <h3>Change Password</h3>
                <div id="passwordMessage"></div>
                <form id="passwordForm">
                    <label for="currentpassword">Current Password:</label><br>
                    <input type="password" id="currentpassword" name="current_password" required><br>
                    
                    <label for="newpassword">New Password:</label><br>
                    <input type="password" id="newpassword" name="new_password" required minlength="8"><br>
                    
                    <label for="confirmpassword">Confirm New Password:</label><br>
                    <input type="password" id="confirmpassword" name="confirm_password" required><br>
                    
                    <input type="submit" value="Change Password">
                </form>
                
                <br><hr><br>
                
                <h3>Delete Account</h3>
                <button onclick="deleteAccount()" style="background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                    Delete Account
                </button>
            </div>
            
            <!-- Privacy Section -->
            <div id="privacy-section" class="settings-section">
                <h2>Privacy Settings</h2>
                <p>Privacy settings coming soon...</p>
                <p>Potential features:</p>
                <ul>
                    <li>Profile visibility</li>
                    <li>Event privacy defaults</li>
                </ul>
            </div>
            
            <!-- Notifications Section -->
            <div id="notifications-section" class="settings-section">
                <h2>Notification Settings</h2>
                <p>Notification settings coming soon...</p>
                <p>Potential features:</p>
                <ul>
                    <li>Notification preferences like...</li>
                    <li>RSVP reminders</li>
                    <li>Event update alerts</li>
                    <li>Message notifications</li>
                </ul>
            </div>
            
            <!-- Contacts Section -->
            <div id="contacts-section" class="settings-section">
                <h2>Contact Management</h2>
                <p>Contact management coming soon...</p>
                <p>Potential features:</p>
                <ul>
                    <li>Saved contacts list</li>
                    <li>Frequent guests</li>
                    <li>Groups</li>
                </ul>
            </div>
            
        </div>   
    </div>
    
    <script>
        // Show different settings sections
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(sec => {
                sec.classList.remove('active');
            });
            
            // Remove active class from all sidebar items
            document.querySelectorAll('.sidebar div').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(section + '-section').classList.add('active');
            
            // Add active class to clicked sidebar item
            event.target.closest('div').classList.add('active');
        }
        
        // Handle profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');
            
            fetch('update-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('profileMessage');
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success-message">' + data.message + '</div>';
                } else {
                    messageDiv.innerHTML = '<div class="error-message">' + data.error + '</div>';
                }
                
                // Clear message after 5 seconds
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('profileMessage').innerHTML = 
                    '<div class="error-message">Error updating profile. Please try again.</div>';
            });
        });
        
        // Handle password form submission
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newpassword').value;
            const confirmPassword = document.getElementById('confirmpassword').value;
            
            if (newPassword !== confirmPassword) {
                document.getElementById('passwordMessage').innerHTML = 
                    '<div class="error-message">New passwords do not match!</div>';
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'change_password');
            
            fetch('update-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('passwordMessage');
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success-message">' + data.message + '</div>';
                    // Clear the form
                    this.reset();
                } else {
                    messageDiv.innerHTML = '<div class="error-message">' + data.error + '</div>';
                }
                
                // Clear message after 5 seconds
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('passwordMessage').innerHTML = 
                    '<div class="error-message">Error changing password. Please try again.</div>';
            });
        });
        
        // Handle account deletion
        function deleteAccount() {
            const confirmation = confirm('Are you sure you want to delete your account?\n\nThis action cannot be undone. All your events and data will be permanently deleted.');
            
            if (!confirmation) return;
            
            const doubleConfirm = confirm('This is your last chance. Delete your account permanently?');
            
            if (!doubleConfirm) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_account');
            
            fetch('update-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your account has been deleted.');
                    window.location.href = 'login.php';
                } else {
                    alert('Error deleting account: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting account. Please try again.');
            });
        }
    </script>
    
</body>   

</html>