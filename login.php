<?php
// PHP Error Reporting (for development only, remove or set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session for managing login state and potentially captcha

// --- Database Configuration ---
define('DB_SERVER', 'localhost'); // Your database server (e.g., localhost)
define('DB_USERNAME', 'your_db_username'); // Your database username
define('DB_PASSWORD', 'your_db_password'); // Your database password
define('DB_NAME', 'your_database_name'); // Your database name

// --- Initialize variables for feedback messages ---
$username = $password = $captcha_input = "";
$username_err = $password_err = $captcha_err = $general_err = "";
$success_msg = "";

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine which action was requested (Login or Create Account)
    $action = $_POST['action'] ?? ''; // Using a hidden input for action

    // --- Database Connection ---
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $general_err = "ERROR: Could not connect to database. " . $e->getMessage();
    }

    // Only proceed if database connection is successful
    if (empty($general_err)) {
        // --- Input Validation and Sanitization (Common for both actions) ---
        if (empty(trim($_POST["username"]))) {
            $username_err = "Please enter username.";
        } else {
            $username = trim($_POST["username"]);
        }

        if (empty(trim($_POST["password"]))) {
            $password_err = "Please enter your password.";
        } else {
            $password = trim($_POST["password"]);
        }

        if (empty(trim($_POST["captcha"]))) {
            $captcha_err = "Please enter the captcha text.";
        } else {
            $captcha_input = trim($_POST["captcha"]);
            // --- IMPORTANT: REAL CAPTCHA VALIDATION IS NEEDED HERE ---
            // For now, it's just a placeholder. In a real system, you'd compare
            // $captcha_input with a value you stored in $_SESSION.
            // if (isset($_SESSION['captcha_code']) && $captcha_input !== $_SESSION['captcha_code']) {
            //     $captcha_err = "Invalid captcha code.";
            // }
            // unset($_SESSION['captcha_code']); // Clear captcha after use
            // For this example, we'll consider it valid if not empty.
        }

        if (empty($username_err) && empty($password_err) && empty($captcha_err)) {
            if ($action === 'login') {
                // --- LOGIN LOGIC ---
                $sql = "SELECT id, username, password_hash FROM users WHERE username = :username";
                if ($stmt = $pdo->prepare($sql)) {
                    $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                    $param_username = $username;
                    if ($stmt->execute()) {
                        if ($stmt->rowCount() == 1) {
                            if ($row = $stmt->fetch()) {
                                $id = $row["id"];
                                $hashed_password = $row["password_hash"];
                                if (password_verify($password, $hashed_password)) {
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $username;
                                    $success_msg = "Login successful! Welcome, " . htmlspecialchars($username) . ". Redirecting...";
                                    // You would typically redirect immediately:
// header("location: dashboard.php");
                                    // exit();
                                } else {
                                    $general_err = "Invalid username or password.";
                                }
                            }
                        } else {
                            $general_err = "Invalid username or password.";
                        }
                    } else {
                        $general_err = "Oops! Something went wrong with the login query. Please try again later.";
                    }
                    unset($stmt);
                }
            } elseif ($action === 'create_account') {
                // --- CREATE ACCOUNT LOGIC ---
                // Check if username already exists for creation
                $sql = "SELECT id FROM users WHERE username = :username";
                if ($stmt = $pdo->prepare($sql)) {
                    $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                    $param_username = $username;
                    if ($stmt->execute()) {
                        if ($stmt->rowCount() == 1) {
                            $username_err = "This username is already taken. Please choose another.";
                        }
                    } else {
                        $general_err = "Oops! Something went wrong checking username availability.";
                    }
                    unset($stmt);
                }

                // Only proceed to insert if no username error
                if (empty($username_err)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)";
                    if ($stmt = $pdo->prepare($sql)) {
                        $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                        $stmt->bindParam(":password_hash", $param_password_hash, PDO::PARAM_STR);
                        $param_username = $username;
                        $param_password_hash = $hashed_password;
                        if ($stmt->execute()) {
                            $success_msg = "Account created successfully! You can now log in.";
                        } else {
                            $general_err = "Something went wrong during account creation. Please try again later.";
                        }
                        unset($stmt);
                    }
                }
            }
        }
    }
    unset($pdo); // Close connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Log In</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* All the CSS code goes here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to the top */
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scrollbar during sidebar animation */
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1200px; /* Adjust as needed */
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            width: 250px;
            background-color: #333;
            color: #fff;
            padding: 20px 0;
            box-sizing: border-box;

transition: transform 0.3s ease-in-out;
            position: fixed; /* Position fixed to overlay content */
            top: 0;
            left: 0;
            height: 100%;
            transform: translateX(-250px); /* Initially hide sidebar */
            z-index: 1000; /* Ensure it's above other content */
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        }

        .sidebar.sidebar-open {
            transform: translateX(0); /* Show sidebar when this class is active */
        }

        .sidebar-header {
            text-align: center;
            padding: 15px 0;
            font-size: 1.2em;
            font-weight: bold;
            border-bottom: 1px solid #555;
            margin-bottom: 10px;
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-item {
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: #007bff;
        }

        .menu-item .material-icons {
            font-size: 20px;
        }

        .submenu {
            list-style: none;
            padding: 0;
            margin-top: 5px;
            background-color: #444;
            width: 100%;
        }

        .submenu li {
            padding: 8px 20px 8px 40px;
        }

        .submenu li a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submenu li a:hover,
        .submenu li.active a {
            background-color: #0069d9;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            width: 100%;
            box-sizing: border-box;
            padding-top: 70px; /* Space for the fixed header */
        }

        .header {
            background-color: #e9ecef;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: center; /* Center items horizontally */
            align-items: center;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            box-sizing: border-box;
        }

        .header .header-title {
            /* Handled by parent flexbox justify-content: center; */
        }

        .hamburger-menu {
            font-size: 28px;
            cursor: pointer;
            color: #333;
            display: block;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1002;
        }

        .login-form-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }

        .info-text {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9em;
            text-align: left;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            text-align: center;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            text-align: left;
        }

.captcha-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .captcha-display {
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #f0f0f0;
        }

        .captcha-image {
            vertical-align: middle;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            color: #fff;
        }

        .btn-login {
            background-color: #28a745;
        }

        .btn-login:hover {
            background-color: #218838;
        }

        .btn-cancel {
            background-color: #dc3545;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .forgot-password,
        .create-account {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9em;
        }

        .forgot-password a,
        .create-account a {
            color: #007bff;
            text-decoration: blue;
        }

        .forgot-password a:hover,
        .create-account a:hover {
            text-decoration: underline;
        }

        /* Overlay for when the sidebar is open */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
            transition: opacity 0.3s ease-in-out;
            opacity: 0;
        }
        .overlay.active {
            display: block;
            opacity: 1;
        }

        /* Styles for PHP messages */
        .php-message {
            margin-top: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: center;
        }
        .php-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .php-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .php-message .error-item {
            display: block;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="overlay" id="overlay"></div>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">Home</div>
            <ul class="menu">
                <li class="menu-item active">
                    <span class="material-icons">person</span>
                    Student
                    <ul class="submenu">
                        <li><a href="#" id="createAccountSidebarLink"><span class="material-icons">add_circle_outline</span> Create Account</a></li>
                        <li class="active"><a href="#" id="loginSidebarLink"><span class="material-icons">login</span> Log In</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <span class="material-icons">school</span>
                    College
                    <ul class="submenu">
                        <li><a href="#"><span class="material-icons">login</span> Log In</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <span class="material-icons">admin_panel_settings</span>
                    Admin / Support
                    <ul class="submenu">
                        <li><a href="#"><span class="material-icons">login</span> Log In</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

<main class="main-content" id="main-content">
            <header class="header">
                <span class="material-icons hamburger-menu" id="hamburger">menu</span>
                <span class="header-title">Student Log In</span>
            </header>

            <div class="login-form-container">
                <p class="info-text">
                    Use your previous sessions username and password for Login. Do not create new account! If you have created account in previous session it will not allow to create another account. Only one account per student is allowed.
                </p>

                <?php if (!empty($success_msg)): ?>
                    <div class="php-message success">
                        <?php echo $success_msg; ?>
                        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                            <script>
                                // Redirect only if login was successful
                                setTimeout(function() {
                                    window.location.href = "dashboard.php"; // Change to your actual dashboard page
                                }, 3000);
                            </script>
                        <?php endif; ?>
                    </div>
                <?php elseif (!empty($general_err) || !empty($password_err) || !empty($captcha_err)): ?>
                    <div class="php-message error">
                        <?php echo !empty($general_err) ? "<p>" . $general_err . "</p>" : ""; ?>
                        <?php echo !empty($username_err) ? "<p class='error-item'>Username: " . $username_err . "</p>" : ""; ?>
                        <?php echo !empty($password_err) ? "<p class='error-item'>Password: " . $password_err . "</p>" : ""; ?>
                        <?php echo !empty($captcha_err) ? "<p class='error-item'>Captcha: " . $captcha_err . "</p>" : ""; ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" action="index.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password">
                    </div>
                   
                    <div class="form-actions">
                        <button type="submit" name="action" value="login" class="btn btn-login">Log In</button>
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
                    </div>

                    <p class="forgot-password">
                        Don't remember password? <a href="#">Forgot Password</a>
                    </p>
                    <p class="create-account">
                        Don't have an account? <button type="submit" name="action" value="create_account" class="btn btn-link"><a href="index.php">Create Account</a></button>
                    </p>
                </form>
            </div>
        </main>
    </div>

<script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const createAccountSidebarLink = document.getElementById('createAccountSidebarLink');
        const loginSidebarLink = document.getElementById('loginSidebarLink');
        const loginForm = document.querySelector('.login-form'); // Get the form

        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('active');
        });

        // Handle clicks from sidebar links to submit the form with the correct action
        if (createAccountSidebarLink) {
            createAccountSidebarLink.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default link behavior
                // Set hidden input value or button name/value before submitting
                const createAccountBtn = loginForm.querySelector('button[name="action"][value="create_account"]');
                if (createAccountBtn) {
                    createAccountBtn.click(); // Simulate click on the create account button
                } else {
                    // Fallback if button not found (shouldn't happen with current HTML)
                    console.error("Create Account button not found in form.");
                }
            });
        }

        if (loginSidebarLink) {
            loginSidebarLink.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default link behavior
                const loginBtn = loginForm.querySelector('button[name="action"][value="login"]');
                if (loginBtn) {
                    loginBtn.click(); // Simulate click on the login button
                }
            });
        }

        // Keep form input values after submission on error
        // (PHP echo htmlspecialchars($username) handles this)

        // Clear password field on page load for security
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = '';
            }
        });
    </script>
</body>
</html>
