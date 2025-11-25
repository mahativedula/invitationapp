<?php

// In this file, I used AI (Claude) to help write the code for the pop-up windows/modal because it is not an element we learned about in class.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db-connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host_id = $_SESSION['user_id'];

// --- Handle deletion if form submitted ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $event_id = (int) $_POST['event_id'];

    try {
        $stmt = $db->prepare("DELETE FROM invitationapp_events WHERE event_id = :event_id AND host_id = :host_id");
        $stmt->execute([':event_id' => $event_id, ':host_id' => $host_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: host-dashboard.php?status=deleted");
        } else {
            header("Location: host-dashboard.php?status=notfound");
        }
        exit;
    } catch (PDOException $e) {
        header("Location: host-dashboard.php?status=error");
        exit;
    }
}

// --- Load events for this host ---
try {
    $stmt = $db->prepare("SELECT event_id, event_name, date, start_time, end_time, location, description FROM invitationapp_events WHERE host_id = :host_id ORDER BY date ASC");
    $stmt->execute([':host_id' => $host_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error loading your events: " . htmlspecialchars($e->getMessage()) . "</p>";
    $events = [];
}

function formatTime($time) {
    if (empty($time)) return '';
    $timestamp = strtotime($time);
    return date('g:i A', $timestamp);
}

function formatTimeRange($start_time, $end_time) {
    $start = formatTime($start_time);
    if (!empty($end_time)) {
        $end = formatTime($end_time);
        return $start . ' - ' . $end;
    }
    return $start;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Host Dashboard</title>
    <link rel="stylesheet" href="styles/host-dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta author content="Mahati Vedula">
    <link rel="stylesheet" href="styles/modal.css">
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
    <br><br>
    <h1>Host Dashboard</h1>
    <div class="main-container">
        <!-- sidebar of all the host's events -->
        <div class="sidebar col-4">
            <h2>My Events</h2>

            <?php if (empty($events)): ?>
                <p>You haven't created any events yet.</p>
                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.getElementById('eventPopup').style.display = 'none';
                });
                </script>
            <?php else: ?>
                <?php foreach ($events as $index => $event): ?>
                    <div 
                        class="event-item" 
                        id="<?php echo $index === 0 ? 'first-event' : ''; ?>"
                        data-event-id="<?php echo (int)$event['event_id']; ?>"
                        data-event-name="<?php echo htmlspecialchars($event['event_name']); ?>"
                        data-event-date="<?php echo htmlspecialchars($event['date']); ?>"
                        data-start-time="<?php echo htmlspecialchars($event['start_time']); ?>"
                        data-end-time="<?php echo htmlspecialchars($event['end_time']); ?>"
                        data-location="<?php echo htmlspecialchars($event['location']); ?>"
                        data-description="<?php echo htmlspecialchars($event['description']); ?>"
                        
                        onclick="openEvent(
                            '<?php echo htmlspecialchars(addslashes($event['event_name'])); ?>', 
                            '<?php echo htmlspecialchars(date('F j, Y', strtotime($event['date']))); ?>', 
                            '<?php echo htmlspecialchars(formatTimeRange($event['start_time'], $event['end_time'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($event['location'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($event['description'])); ?>',
                            <?php echo (int)$event['event_id']; ?>
                        )">
                        <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                        <p>
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($event['date']))); ?> 
                            - <?php echo htmlspecialchars(formatTimeRange($event['start_time'], $event['end_time'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- event view -->
        <div class="col-6" style="margin-left: 50px;"> 
            <div class="event-popup" id="eventPopup">
                <div class="event-header">
                    <div class="event-image">🎈</div>
                    <div class="event-details">
                        <h2 id="popupTitle">Event Title</h2>
                        <p><strong>Date:</strong> <span id="popupDate"></span></p>
                        <p><strong>Time:</strong> <span id="popupTime"></span></p>
                        <p><strong>Location:</strong> <span id="popupLocation"></span></p>
                        <p><strong>Description:</strong> <span id="popupDescription"></span></p>
                        <button onclick="openEditModal()">Edit details</button>
                    </div>
                </div>

                <!-- Guest list -->
                <div class="guest-list">
                    <h3>Guest List</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>RSVP</th>
                            </tr>
                        </thead>
                        <tbody id="guestTableBody">
                            <tr><td colspan="2">Select an event to view guests</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="buttons">
                    <button>Add guests</button>
                    <button onclick="openAnnouncementModal()">Send announcement</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" id="deleteEventId" name="event_id">
                        <button type="submit" id="deleteButton" onclick="return confirm('Are you sure you want to delete this event?')">
                            Delete Event
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Event</h2>
            <form id="editEventForm" class="modal-form">
                <input type="hidden" id="editEventId" name="event_id">
                
                <label for="editEventName">Event Name:</label>
                <input type="text" id="editEventName" name="event_name" required>
                
                <label for="editDescription">Description:</label>
                <textarea id="editDescription" name="description"></textarea>
                
                <label for="editDate">Date:</label>
                <input type="date" id="editDate" name="date" required>
                
                <div class="time-inputs">
                    <div>
                        <label for="editStartTime">Start Time:</label>
                        <input type="time" id="editStartTime" name="start_time" required>
                    </div>
                    <div>
                        <label for="editEndTime">End Time:</label>
                        <input type="time" id="editEndTime" name="end_time">
                    </div>
                </div>
                
                <label for="editLocation">Location:</label>
                <input type="text" id="editLocation" name="location" required>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAnnouncementModal()">&times;</span>
            <h2>Send Announcement</h2>
            <form id="announcementForm" class="modal-form">
                <input type="hidden" id="announcementEventId" name="event_id">
                
                <label>Send to:</label>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin: 5px 0; font-weight: normal;">
                        <input type="radio" name="recipient_filter" value="all" checked>
                        All guests
                    </label>
                    <label style="display: block; margin: 5px 0; font-weight: normal;">
                        <input type="radio" name="recipient_filter" value="going">
                        Only guests going
                    </label>
                    <label style="display: block; margin: 5px 0; font-weight: normal;">
                        <input type="radio" name="recipient_filter" value="not_going">
                        Only guests not going
                    </label>
                    <label style="display: block; margin: 5px 0; font-weight: normal;">
                        <input type="radio" name="recipient_filter" value="maybe">
                        Only guests who selected "Maybe"
                    </label>
                    <label style="display: block; margin: 5px 0; font-weight: normal;">
                        <input type="radio" name="recipient_filter" value="no_response">
                        Only guests who haven't responded
                    </label>
                </div>
                
                <label for="announcementSubject">Subject:</label>
                <input type="text" id="announcementSubject" name="subject" maxlength="100" required placeholder="e.g. Important update about the event">
                
                <label for="announcementContent">Message:</label>
                <textarea id="announcementContent" name="content" maxlength="5000" required placeholder="Write your announcement here..." style="min-height: 150px;"></textarea>
                
                <p style="font-size: 12px; color: #666; margin-top: 5px;">
                    <span id="recipientCount">Loading recipients...</span>
                </p>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAnnouncementModal()">Cancel</button>
                    <button type="submit" class="btn-save">Send Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Updated JS -->
    <script>

        let currentEventId = null;

        function openEvent(title, date, time, location, description, eventId) {
            currentEventId = eventId;

            document.getElementById('popupTitle').textContent = title;
            document.getElementById('popupDate').textContent = date;
            document.getElementById('popupTime').textContent = time;
            document.getElementById('popupLocation').textContent = location
            document.getElementById('popupDescription').textContent = description;
            document.getElementById('eventPopup').classList.add('active');

            // Set the event ID for delete form
            document.getElementById('deleteEventId').value = eventId;

            // Load guests dynamically (same as before)
            fetch(`get-guests.php?event_id=${eventId}`)
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('guestTableBody');
                    tbody.innerHTML = '';
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2">No guests yet.</td></tr>';
                    } else {
                        data.forEach(g => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${g.name}</td>
                                    <td>${g.response}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('guestTableBody').innerHTML =
                        '<tr><td colspan="2" style="color:red;">Error loading guests</td></tr>';
                });
        }
        function openEditModal() {
            if (!currentEventId) return;
            
            // Find the event data from the sidebar
            const eventDiv = document.querySelector(`[data-event-id="${currentEventId}"]`);
            if (!eventDiv) return;

            // Populate the form
            document.getElementById('editEventId').value = currentEventId;
            document.getElementById('editEventName').value = eventDiv.dataset.eventName;
            document.getElementById('editDescription').value = eventDiv.dataset.description;
            document.getElementById('editDate').value = eventDiv.dataset.eventDate;
            document.getElementById('editLocation').value = eventDiv.dataset.location;
            
            // Format times - remove seconds if present
            const startTime = eventDiv.dataset.startTime.substring(0, 5);
            const endTime = eventDiv.dataset.endTime ? eventDiv.dataset.endTime.substring(0, 5) : '';
            
            document.getElementById('editStartTime').value = startTime;
            document.getElementById('editEndTime').value = endTime;

            // Show the modal
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Handle form submission
        document.getElementById('editEventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update-event.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Event updated successfully!');
                    closeEditModal();
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    alert('Error updating event: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating event. Please try again.');
            });
        });

        // Announcement Modal Functions
        function openAnnouncementModal() {
            if (!currentEventId) return;
            
            document.getElementById('announcementEventId').value = currentEventId;
            document.getElementById('announcementModal').style.display = 'block';
            
            // Update recipient count when filter changes
            updateRecipientCount();
            
            // Add event listeners for radio buttons
            document.querySelectorAll('input[name="recipient_filter"]').forEach(radio => {
                radio.addEventListener('change', updateRecipientCount);
            });
        }

        function closeAnnouncementModal() {
            document.getElementById('announcementModal').style.display = 'none';
            document.getElementById('announcementForm').reset();
        }

        function updateRecipientCount() {
            const filter = document.querySelector('input[name="recipient_filter"]:checked').value;
            const eventId = currentEventId;
            
            fetch(`send-announcement.php?event_id=${eventId}&filter=${filter}`)
                .then(res => res.json())
                .then(data => {
                    const countSpan = document.getElementById('recipientCount');
                    if (data.count === 0) {
                        countSpan.textContent = 'No recipients match this filter.';
                        countSpan.style.color = 'red';
                    } else if (data.count === 1) {
                        countSpan.textContent = 'This will be sent to 1 guest.';
                        countSpan.style.color = '#666';
                    } else {
                        countSpan.textContent = `This will be sent to ${data.count} guests.`;
                        countSpan.style.color = '#666';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('recipientCount').textContent = 'Error loading recipient count.';
                });
        }

        // Handle announcement form submission
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading state
            const submitBtn = this.querySelector('.btn-save');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            fetch('send-announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Announcement sent successfully to ${data.recipients_count} guest(s)!`);
                    closeAnnouncementModal();
                } else {
                    alert('Error sending announcement: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending announcement. Please try again.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const announcementModal = document.getElementById('announcementModal');
            
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == announcementModal) {
                closeAnnouncementModal();
            }
        }

    </script>

    <script>
    // After the page loads, automatically open the first event (if it exists)
    document.addEventListener('DOMContentLoaded', function() {
        const firstEventDiv = document.getElementById('first-event');
        if (firstEventDiv) {
            firstEventDiv.click(); // triggers openEvent() for the first event
        }
    });
    </script>

</body>
</html>
