<!DOCTYPE html>
<html>
    <head>
        <title>Event Creation</title>
        <link rel="stylesheet" href="styles/event-creation.css">
        <meta charset="UTF-8">
        <meta author content="Kayleen Do + Mahati Vedula">
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const guestList = [];
                const attendeeListDiv = document.getElementById("attendee-list");
                const nameInput = document.getElementById("attendee-name");
                const emailInput = document.getElementById("attendee-email");
                const inviteBtn = document.getElementById("invite");
                const hiddenGuestInput = document.getElementById("guest-data");

                // Add guest to temporary list
                inviteBtn.addEventListener("click", function(e) {
                    e.preventDefault();

                    const name = nameInput.value.trim();
                    const email = emailInput.value.trim();

                    if (name === "" || email === "") {
                        alert("Please enter both name and email.");
                        return;
                    }

                    // Add to guest list array
                    guestList.push({ name, email });

                    // Update display
                    updateGuestList();

                    // Update hidden input for PHP
                    hiddenGuestInput.value = JSON.stringify(guestList);

                    // Clear inputs
                    nameInput.value = "";
                    emailInput.value = "";
                });

                // Update displayed guest list
                function updateGuestList() {
                    attendeeListDiv.innerHTML = `
                        <div id="name-container">
                            <h4>Names:</h4>
                            ${guestList.map(g => `<p>${g.name}</p>`).join("")}
                        </div>
                        <div id="email-container">
                            <h4>Emails:</h4>
                            ${guestList.map(g => `<p>${g.email}</p>`).join("")}
                        </div>
                    `;
                }
            });
            // Note to self: add check to make sure guest email is registered in DB before adding to guest list; or, 
            // inplement functionality to send invite to unregistered emails prompting them to create accountity
        </script>
    </head>
    <body>
        <br>
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
        <h1>Event Creation Page</h1>
        <div id="event-page">
        <!-- Preview Section of Event Creation -->
        <div id="left-page">
            <div id="event-preview">
                <div id="event-details">
                    <h2 id="event-name">Event Name</h2>
                    <p id="date">Date: mm/dd/yyyy</p>
                    <p id="time">Time: --:--</p>
                    <p id="location">Location: ______________</p>
                    <p id="description">Description</p>
                </div>
            </div>
            <div id="template">
                <button class="arrow">&lt</button>
                <p>Choose a Template</p>
                <button class="arrow">&gt</button>
            </div>
        </div>
        <!-- Form Input for Event Creation -->
        <div id="right-page">
            <form method="POST" action="index.php?page=create-event">
                <label for="event-name-input">Event Name:</label>
                <input type="text" id="event-name-input" name="event_name" required>
                <label for="description-input">Description:</label>
                <input type="text" id="description-input" name="description">
                <div id="form-date-time">
                    <label for="date-input">Date:</label>
                    <input type="date" id="date-input" name="date" required>
                    <label for="time-input">Time:</label>
                    <input type="time" id="time-input" name="time" required>
                </div>
                <label for="location-input">Location:</label>
                <input type="text" id="location-input" name="location" required>
            
                <h3>Guest List</h3>
                    <div id="attendee-list">
                        <div id="name-container"><h4>Names:</h4></div>
                        <div id="email-container"><h4>Emails:</h4></div>
                    </div>
                    <div id="attendee-container">
                        <label for="attendee-name">Attendee Name: </label>
                        <input type="text" id="attendee-name" placeholder="e.g. Jane Doe">
                        <label for="attendee-email">Attendee Email: </label>
                        <input type="email" id="attendee-email" placeholder="e.g. jane@example.com">
                        <button id="invite">Add Guest</button>
                    </div>

                    <!-- Hidden input to hold guest data -->
                    <input type="hidden" id="guest-data" name="guest_data">
                    <br>
                    <div id="create-event-button">
                    <input type="submit" id="create-event" value="Create Event">
                    </div>
            </form>
        </div>
        </div>
        <script>
            function handleNameInput() {
                const nameInput = document.getElementById('event-name-input');
                const previewName = document.getElementById('event-name');

                previewName.textContent = nameInput.value || "Event Name";
            }

            function handleDescriptionInput() {
                const descriptionInput = document.getElementById('description-input');
                const description = document.getElementById('description');

                description.textContent = descriptionInput.value || "Description";
            }

            function handleDateInput() {
                const dateInput = document.getElementById('date-input');
                const date = document.getElementById('date');
                const raw = dateInput.value;

                const [year, month, day] = raw.split("-");
                const formatted = `${month}/${day}/${year}`;

                date.textContent = "Date: " + formatted;
            }

            function handleTimeInput() {
                const timeInput = document.getElementById('time-input');
                const time = document.getElementById('time');
                const raw = timeInput.value;

                if (!raw) {
                    time.textContent = "Time: --:--";
                    return;
                }

                let [hour, minute] = raw.split(":");

                hour = parseInt(hour);

                const ampm = hour >= 12 ? "PM" : "AM";
                let hour12 = hour % 12;
                if (hour12 === 0) hour12 = 12;

                const formatted = `${hour12}:${minute} ${ampm}`;

                time.textContent = "Time: " + formatted;
            }

            function handleLocationInput() {
                const locationInput = document.getElementById('location-input');
                const location = document.getElementById('location');

                location.textContent = "Location: " + locationInput.value;
            }

            function handleTemplateCarosoul() {
                
            }

            document.addEventListener('DOMContentLoaded', function() {
                const nameInput = document.getElementById('event-name-input');
                const descriptionInput = document.getElementById('description-input');
                const dateInput = document.getElementById('date-input');
                const timeInput = document.getElementById('time-input');
                const locationInput = document.getElementById('location-input');

                nameInput.addEventListener('input', handleNameInput);
                descriptionInput.addEventListener('input', handleDescriptionInput);
                dateInput.addEventListener('input', handleDateInput);
                timeInput.addEventListener('input', handleTimeInput);
                locationInput.addEventListener('input', handleLocationInput);
            });
        </script>
    </body>
</html>
