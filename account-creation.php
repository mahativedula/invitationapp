<?php
    $error_message = '';
    $stmt = $db->prepare("SELECT * FROM invitationapp_users WHERE username = :username");

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
        } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
            $error_message = "Weak password: Please include a mix of lower and upper case letters, numbers, and be at least 8 characters long.";
        } elseif (!preg_match("/^[\w\.-]+@[\w\.-]+\.\w{2,}$/", $email)) {
            $error_message = "Invalid email format.";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user into the database
            $stmt = $db->prepare("INSERT INTO invitationapp_users (first_name, last_name, email, username, password_hash) VALUES (:fname, :lname, :email, :username, :password_hash)");
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password_hash', $password_hash);

            try {
                $stmt->execute();
                header("Location: index.php?page=host-dashboard");
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

                <?php
                    if (!empty($error_message)) {
                        echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
                    }
                ?>

                <!-- Account Creation Form -->
                <form id="create-form" action="index.php?page=account-creation" method="POST">
                    <div id="name">
                        <input type="text" class="form-text-input" placeholder="First Name" id="fname" name="fname">
                        <input type="text" class="form-text-input" placeholder="Last Name" id="lname" name="lname">
                    </div>
                    <input type="email" class="form-text-input" placeholder="Email" id="email" name="email">
                    <input type="text" class="form-text-input" placeholder="Username" id="username" name="username">
                    <input type="password" class="form-text-input" placeholder="Password" id="password" name="password">
                    <input type="password" class="form-text-input" placeholder="Confirm Password" id="confirm-pword" name="confirm-pword">
                    <input type="submit" value="Create Account" id="submit">
                </form>
                <a id="login-link" href="index.php?page=login">Already have an account? Login</a>
            </div>
        </div>
    </body>
</html>