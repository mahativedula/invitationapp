<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages</title>
    <link rel="stylesheet" href="styles/message.css" />
    <meta author content="Fiona Fitzsimons">
</head>

<body>
    <!-- HEADER -->
        <!-- NAVIGATION MENU -->
    <header>
        <nav>
            <li><h1><a href = "message.php">Inbox</a></h1></li>
            <li><h1>Sent</h1></li>
        </nav>

         <!-- SEARCH BAR -->
        <div class="search-bar">
            <input type="text" placeholder= "Search">
        </div>

        <button class="account-btn">
            <div class="account-icon"></div>
            <span><a href = "settings.php">Account</a></span>
        </button>
    </header>

    <table>
         <!-- messages sent table -->
        <tr>
            <th>Message Subject</th>
            <th>Date Sent</th>
            <th>Send to</th>
        </tr>
    </table>
</body>
</html>
