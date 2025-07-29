<!-- // PHP_EOL is a constant that represents the "End Of Line" character. It's good practice
// to use it for consistent line breaks across different operating systems when building
// string outputs, especially for logs or plaintext emails.
// For browser output, <br> is used for line breaks.

// Initialize an array to store validation errors
$errors = [];
$success_message = '';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Sanitize and Retrieve Form Data ---
    // Using htmlspecialchars() to prevent XSS attacks by converting special characters to HTML entities.
    // The ?? '' is the null coalescing operator, ensuring an empty string if the POST variable is not set.
    $nationality = htmlspecialchars($_POST['nationality'] ?? '');
    $studentName = htmlspecialchars($_POST['studentName'] ?? '');
    $emailId = htmlspecialchars($_POST['emailId'] ?? '');
    $mobileNumber = htmlspecialchars($_POST['mobileNumber'] ?? '');

    // --- 2. Server-Side Validation ---

    // Validate Nationality
    if (empty($nationality)) {
        $errors[] = "Nationality is required.";
    } elseif ($nationality !== "indian" && $nationality !== "other") {
        $errors[] = "Invalid Nationality selected.";
    }

    // Validate Student Name
    if (empty($studentName)) {
        $errors[] = "Student Name is required.";
    } elseif (strlen($studentName)  3) {
        $errors[] = "Student Name must be at least 3 characters long.";
    }

    // Validate Email ID
    if (empty($emailId)) {
        $errors[] = "Email Id is required.";
    } elseif (!filter_var($emailId, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Email Id format. Please enter a valid email address.";
    }

    // Validate Mobile Number
    // Assumes a 10-digit Indian mobile number for simplicity.
    if (empty($mobileNumber)) {
        $errors[] = "Mobile Number is required.";
    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobileNumber)) { // Basic Indian mobile number pattern
        $errors[] = "Invalid Mobile Number. Please enter a 10-digit Indian mobile number (starts with 6, 7, 8, or 9).";
    }

    // --- 3. Process Data if No Errors ---
    if (empty($errors)) {
        // --- IMPORTANT: This is where you would integrate your core logic ---

        // Database Connection Example (replace with your actual credentials and secure connection)
        // For a real application, consider using PDO for database interactions.
        $servername = "localhost";
        $db_username = "your_db_username"; // !!! REPLACE THIS !!!
        $db_password = "your_db_password"; // !!! REPLACE THIS !!!
        $db_name = "your_database_name";   // !!! REPLACE THIS !!!

        // Attempt to establish a database connection
        $conn = new mysqli($servername, $db_username, $db_password, $db_name);

        // Check connection
        if ($conn->connect_error) {
            $errors[] = "Database connection failed: " . $conn->connect_error;
            // In a production environment, you would log this error and show a generic message to the user.
        } else {
            // Check if email or mobile already exists (IMPORTANT for preventing duplicate accounts)
            // Assuming you have a 'users' table or similar for checking existing accounts
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email_id = ? OR mobile_number = ? LIMIT 1");
            if ($stmt_check) {
                $stmt_check->bind_param("ss", $emailId, $mobileNumber);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $errors[] = "An account with this Email ID or Mobile Number already exists. Please try logging in.";
                }
                $stmt_check->close();
            } else {
                $errors[] = "Database query preparation failed for checking existing user: " . $conn->error;
            }
// Proceed only if no new errors were found (e.g., from DB check)
            if (empty($errors)) {
                // --- OTP Generation and Storage ---
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // Generate a 6-digit OTP
                $otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes')); // OTP valid for 10 minutes

                // Prepare SQL to store temporary registration data and OTP
                // Assuming 'otp_verifications' table exists with email_id and mobile_number as UNIQUE keys
                $stmt_insert_otp = $conn->prepare(
                    "INSERT INTO otp_verifications (email_id, mobile_number, otp, expires_at) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), is_used = FALSE"
                );

                if ($stmt_insert_otp) {
                    $stmt_insert_otp->bind_param("ssss", $emailId, $mobileNumber, $otp, $otp_expires_at);

                    if ($stmt_insert_otp->execute()) {
                        // --- OTP Sending (SIMULATED) ---
                        // In a real application, you would use an external service or library here.
                        // Example: PHPMailer for email, or a service like Twilio/Msg91 for SMS.
                        // For this example, we'll just log it or output a message.

                        $otp_log_message = "OTP for {$emailId} / {$mobileNumber} is: {$otp}. It expires at {$otp_expires_at}." . PHP_EOL;
                        error_log($otp_log_message, 3, "otp_log.log"); // Log OTP to a file for development/debugging

                        $success_message = "A verification OTP has been sent to your Email ID and Mobile Number. Please check your inbox/messages.";

                        // --- Redirect to OTP Verification Page ---
                        // Use JavaScript for redirection after alert, as PHP header() must be called
                        // before any output. Since we might have outputted some hidden HTML earlier
                        // if PHP code was processed (e.g., for retaining form values),
                        // JS redirect is safer here for immediate action after a message.
                        echo "<script>alert('{$success_message}'); window.location.href = 'otp_verification.php?email=" . urlencode($emailId) . "';</script>";
                        exit(); // Exit to prevent further PHP execution and HTML rendering
                    } else {
                        $errors[] = "Failed to store OTP: " . $stmt_insert_otp->error;
                    }
                    $stmt_insert_otp->close();
                } else {
                    $errors[] = "Failed to prepare OTP insertion statement: " . $conn->error;
                }
            }
            $conn->close(); // Close the database connection
        }
    }
} -->


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Student Identity Manager</title>
    <style>
        /* Basic CSS for layout - you'll want to replace this with a proper CSS framework or detailed styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

.header h2 {
            margin: 0;
            color: #333;
        }

        .instructions {
            background-color: #e0f7fa;
            border-left: 5px solid #00bcd4;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            color: #01579b;
        }

        .instructions h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #00838f;
        }

        .instructions ul {
            list-style: disc;
            padding-left: 20px;
            margin: 0;
        }

        .instructions li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group select:focus {
            border-color: #00bcd4;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 188, 212, 0.2);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }

        .button-group button {
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .button-group .verify-btn {
            background-color: #2196f3;
            color: white;
        }

        .button-group .verify-btn:hover {
            background-color: #1976d2;
        }

        .button-group .cancel-btn {
            background-color: #f44336;
            color: white;
        }

        .button-group .cancel-btn:hover {
            background-color: #d32f2f;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #555;
        }

        .login-link a {
            color: #00bcd4;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Create Account</h2>
            <button
                style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">सुभाष
                सामवेदी</button>
        </div>

        <div class="instructions">
            <h3><span style="color: #00bcd4;">&#x2139;</span> Instructions</h3>
            <ul>
                <li>Do not open this website in multiple tabs in same browser to avoid difficulties in account/profile
                    creation.</li>
                <li>Do not create new account if you have created account in previous session.</li>
                <li>Each student needs only one account.</li>
                <li>For annual creation one year valid Aadhaar Number (only for Indian students)/Passport Number (only
                    for International Students) is mandatory for profile creation.</li>
                <li>Provide valid Email Id and Mobile Number because an OTP will be sent to verify your Email Id
Mobile Number for account activation. If unable to login use forgot password functionality.</li>
                <li>Use strong password.</li>
                <li>If you don't have aadhaar number get then go to your college and ask for Temporary Number for
                    Aadhaar. That Temporary Number needs to be entered in place of aadhaar number.</li>
            </ul>
        </div>

        <!-- php
        // This part displays errors and success messages.
         It's placed here so messages appear above the form,
         after the instructions. -->
        <!--  The success message will typically trigger a redirect, so it might not always be seen here
         unless the JS redirect is prevented or fails.
        if (!empty($success_message) && empty($errors)) { // Only show success if no errors occurred -->
        <!-- <div style="border: 1px solid lightgray;  padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <strong>Success:</strong>
        </div> -->

        <form action="" method="POST">
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <select id="nationality" name="nationality" required>
                    <option value="">Select</option>
                    <option value="indian" <?php echo (isset($nationality) && $nationality='indian' ) ? 'selected' : ''
                        ; ?>Indian</option>
                    <option value="other" <?php echo (isset($nationality) && $nationality='other' ) ? 'selected' : "" ;
                        ?>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="studentName">Student Name</label>
                <input type="text" id="studentName" name="studentName"
                    placeholder="Enter Name As Per Your Previous Marksheet" ; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="emailId">Email Id</label>
                    <input type="email" id="emailId" name="emailId" placeholder="Enter Email Id" ); ?>
                </div>
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number</label>
                    <input type="text" id="mobileNumber" name="mobileNumber" placeholder="Enter Mobile Number"
                        pattern="[0-9]{10}" title="Please enter a 10 digit mobile number" ; ?>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" name="verify_and_create" class="verify-btn">Verify Email and Mobile No.</button>
                <button type="reset" class="cancel-btn">Cancel</button>
            </div>
        </form>

        <div class="login-link">
            I already have an account? <a href="#">Login</a>
        </div>
    </div>
</body>

</html>
