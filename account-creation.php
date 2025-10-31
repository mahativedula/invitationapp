<?php
    // login functionality
    session_start();

    $error_message = '';

    try {
        $db = new PDO(
        "pgsql:host=db;port=5432;dbname=invitationapp_db",
        "localuser",
        "cs4640LocalUser!",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
      );  
    } catch (PDOException $e) {
        $error_message = "Error connecting to database: " . $e->getMessage();
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_pword = $_POST['confirm-pword'];

        if (empty($fname) || empty($lname) || empty($email) || empty($username) || empty($password) || empty($confirm_pword)) {
            $error_message = "Please fill in all fields.";
        } elseif ($password !== $confirm_pword) {
            $error_message = "Passwords do not match.";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user into the database
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, username, password_hash) VALUES (:fname, :lname, :email, :username, :password_hash)");
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password_hash', $password_hash);

            try {
                $stmt->execute();
                header("Location: host-dashboard.html");
                exit();
            } catch (PDOException $e) {
                if ($e->getCode() == 23505) { // Unique violation
                    $error_message = "Username or email already exists.";
                } else {
                    $error_message = "Error creating account: " . $e->getMessage();
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Account Creation</title>
        <link rel="stylesheet" href="styles/account-creation.css">
        <meta charset="UTF-8">
        <meta author content="Kayleen Do">
    </head>
    <body>
        <div id="create">
            <div id="create-container">
                <!-- Header -->
                <h1>Create an Account</h1>
                <!-- Account Creation Form -->
                <form id="create-form" action="" method="POST">
                    <div id="name">
                        <input type="text" class="form-text-input" placeholder="First Name" id="fname" name="fname">
                        <input type="text" class="form-text-input" placeholder="Last Name" id="lname" name="lname">
                    </div>
                    <input type="text" class="form-text-input" placeholder="Email" id="email" name="email">
                    <input type="text" class="form-text-input" placeholder="Username" id="username" name="username">
                    <input type="password" class="form-text-input" placeholder="Password" id="password" name="password">
                    <input type="password" class="form-text-input" placeholder="Confirm Password" id="confirm-pword" name="confirm-pword">
                    <input type="submit" value="Create Account" id="submit">
                </form>
            </div>
        </div>
    </body>
</html>