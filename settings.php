<!DOCTYPE html>
<html lang="en">

    <head>
        <title>Settings</title>
        <link rel="stylesheet" href="styles/settings.css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="UTF-8">
        <meta author content="Mahati Vedula">
    </head> 

    <body>
        <br>
        <br>
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
        <!-- sidebar with setting-related concerns; the buttons do not work yet so popup view does not update -->
        <div class="sidebar col-3">
            <div>
                <strong>Profile</strong>
            </div>
            <div>
                <strong>Privacy</strong>
            </div>
            <div>
                <strong>Notifications</strong>
            </div>
            <div>
                <strong>Contacts</strong>
            </div>
        </div>
        <!-- shows view for "Profile" sidebar selection where user can edit email, username, and passwords -->
        <div class="main-content col-6">
            <h2>Account Settings</h2>
            <form>
                <label for="username">Update Username:</label><br>
                <input type="text" id="username" name="username" value="current_username"><br>
                <label for="email">Update Email:</label><br>
                <input type="email" id="email" name="email" value="example@gmail.com"><br>
                <br>
                <h3>Change Password</h3>
                <label for="password">Current Password:</label><br>
                <input type="text" id="currentpassword" name="currentpassword"><br>
                <label for="newpassword">New Password:</label><br>
                <input type="text" id="newpassword" name="newpassword"><br>
                <label for="confirmpassword">Confirm New Password:</label><br>
                <input type="text" id="confirmpassword" name="confirmpassword"><br>
                <input type="submit" value="Save Changes">
            </form>
        </div>   
        </div>     
    </body>   

</html>    