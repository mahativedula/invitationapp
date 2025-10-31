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
        <br>
        <br>
         <h1>Host Dashboard</h1>
        <div class="main-container">
            <!-- sidebar of all the host's events; can click to view more detail and make edits -->
            <div class="sidebar col-4">
                <!-- example events -->
                <h2>My Events</h2>
                <div class="event-item" onclick="openEvent('Birthday', 'June 15, 2025', '4 PM')">
                <strong>Birthday</strong>
                <p>June 15, 2025 - 4 PM</p>
                </div>

                <div class="event-item" onclick="openEvent('Graduation', 'May 31, 2025', '4-7 PM')">
                <strong>Graduation</strong>
                <p>May 31, 2025 - 4-7 PM</p>
                </div>

                <div class="event-item" onclick="openEvent('Dinner Party', 'Aug 13, 2025', '6-8 PM')">
                <strong>Dinner Party</strong>
                <p>Aug 13, 2025 - 6-8 PM</p>
                </div>
            </div>
            <!-- event view with more details and opportunity to edit + interact with the event -->
            <div class="col-6" style = "margin-left: 50px;"> 
                <div class="event-popup" id="eventPopup">
                <div class="event-header">
                    <div class="event-image">
                        🎈
                    </div>
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
                        <tr>
                            <th>Guest</th>
                            <th>RSVP</th>
                        </tr>
                        <tbody>
                        <tr><td>Person 1</td><td>Yes</td></tr>
                        <tr><td>Person 2</td><td>No</td></tr>
                        <tr><td>Person 3</td><td>Maybe</td></tr>
                        <tr><td>Person 4</td><td>No response</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="buttons">
                    <button>Add guests</button>
                    <button>Send announcement</button>
                </div>
                </div>
            </div>
        </div>

        <!-- Used ChatGPT to figure out how to change the event view based on which event is clicked on -->
        <script>
            function openEvent(title, date, time) {
            document.getElementById('popupTitle').textContent = title;
            document.getElementById('popupDate').textContent = date;
            document.getElementById('popupTime').textContent = time;
            document.getElementById('eventPopup').classList.add('active');
            }
        </script>

    </body>   

</html>    