<?php
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error_message = "Please enter both username and password.";
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php?page=host-dashboard");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        }
    
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
        <link rel="stylesheet" href="styles/main.css">
        <meta charset="UTF-8">
        <meta author content="Kayleen Do">
    </head>
    <body>
        <!-- Link to our site: https://cs4640.cs.virginia.edu/nsc3sj/invitationapp/ -->
        <div id="login">
            <div id="login-container">
                <!-- Header -->
                <h1>Invitation System</h1>
                <h2>Login</h2>

                <?php
                    if (!empty($error_message)) {
                        echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
                    }
                ?>

                <!-- Login Form -->
                <form id="login-form" method="POST" action="index.php?page=login">
                    <input type="text" class="form-input form-text-input" id="username" placeholder="Username" name="username" required>
                    <input type="password" class="form-input form-text-input" id="password" placeholder="Password" name="password" required>
                    <div id="password-container">
                        <input type="checkbox" id="remember-me">
                        <label for="remember-me" id="remember-label">Remember Me</label>
                        <a id="forgot">Forgot Password</a>
                    </div>
                    <input type="submit" class="form-input" id="submit" value="Login">
                </form>
                <!-- Account creation section -->
                <p id="account-creation">Don't have an account?</p>
                <a id="register" href="index.php?page=account-creation">Register</a>
                <img src="google-logo.webp" id="google" alt="Google Login">
            </div>
        </div>
    </body>
</html>