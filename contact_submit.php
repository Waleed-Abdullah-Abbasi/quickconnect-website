<?php
// Prevent any output before we set headers
ob_start();

// Start session
session_start();

// Set content type for JSON response and prevent caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

// Function to safely output JSON and exit
function outputResponse($response)
{
    // Clear any buffered output
    if (ob_get_level()) {
        ob_clean();
    }

    // Ensure we only output JSON
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method';
        outputResponse($response);
    }

    // Debug: Log the received data
    error_log("Contact form submission received: " . print_r($_POST, true));

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'message'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // Validate email format
    if (isset($_POST['email']) && !empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['message'] = implode('. ', $errors);
        outputResponse($response);
    }

    // Sanitize input data
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $company = isset($_POST['company']) ? trim($_POST['company']) : '';
    $inquiry_type = isset($_POST['inquiry_type']) ? trim($_POST['inquiry_type']) : 'general';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Try to save to database
    $db_saved = false;
    $db_error = '';

    try {
        // Check if database config file exists
        if (file_exists('config/database.php')) {
            include_once 'config/database.php';

            if (isset($pdo) && $pdo !== null) {
                // Check if table exists
                $table_check = $pdo->query("SHOW TABLES LIKE 'contacts'");
                if ($table_check->rowCount() > 0) {
                    // First, let's check what columns exist in the table
                    $columns_query = $pdo->query("DESCRIBE contacts");
                    $existing_columns = $columns_query->fetchAll(PDO::FETCH_COLUMN);
                    $response['debug'][] = "Existing columns: " . implode(', ', $existing_columns);

                    // Prepare SQL statement based on your database schema
                    // Using 'service' column instead of 'inquiry_type' to match your database
                    $sql = "INSERT INTO contacts (name, email, phone, service, message) 
                            VALUES (:name, :email, :phone, :service, :message)";

                    $stmt = $pdo->prepare($sql);

                    // Execute with parameters
                    $full_name = $first_name . ' ' . $last_name;

                    // Map inquiry_type to service column
                    $service = $inquiry_type;

                    $db_saved = $stmt->execute([
                        ':name' => $full_name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':service' => $service,
                        ':message' => $message
                    ]);

                    if ($db_saved) {
                        error_log("Contact form submission saved to database for: " . $email);
                        $response['debug'][] = "Database save successful";
                    } else {
                        $db_error = "Failed to execute database insert";
                        $errorInfo = $stmt->errorInfo();
                        if ($errorInfo[2]) {
                            $db_error .= ": " . $errorInfo[2];
                        }
                    }
                } else {
                    $db_error = "Contacts table does not exist";
                }
            } else {
                $db_error = "Database connection is null";
            }
        } else {
            $db_error = "Database config file not found";
        }
    } catch (Exception $e) {
        $db_error = "Database error: " . $e->getMessage();
        error_log("Database error in contact form: " . $e->getMessage());
    }

    if (!empty($db_error)) {
        $response['debug'][] = $db_error;
    }

    // Send email notification
    $email_sent = false;
    $email_error = '';

    try {
        // Email configuration - UPDATE THIS EMAIL ADDRESS
        $to_email = "connectquickconnect@gmail.com"; // Updated with your email

        // Only proceed if email is configured
        if ($to_email !== "your-email@example.com") {
            $from_email = $email;
            $from_name = $first_name . ' ' . $last_name;

            // Email subject
            $subject = "New Contact Form Submission from " . $from_name;

            // Email message
            $email_message = "
            <html>
            <head>
                <title>New Contact Form Submission</title>
            </head>
            <body>
                <h2>New Contact Form Submission</h2>
                <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>" . htmlspecialchars($first_name . ' ' . $last_name) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>" . htmlspecialchars($email) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td>" . htmlspecialchars($phone) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Company:</strong></td>
                        <td>" . htmlspecialchars($company) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Service/Inquiry Type:</strong></td>
                        <td>" . htmlspecialchars(ucfirst($inquiry_type)) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Message:</strong></td>
                        <td>" . nl2br(htmlspecialchars($message)) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Submitted:</strong></td>
                        <td>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>
            </body>
            </html>
            ";

            // Email headers
            $headers = array(
                'MIME-Version' => '1.0',
                'Content-type' => 'text/html; charset=UTF-8',
                'From' => $from_name . ' <' . $from_email . '>',
                'Reply-To' => $from_email,
                'X-Mailer' => 'PHP/' . phpversion()
            );

            // Convert headers array to string
            $headers_string = '';
            foreach ($headers as $key => $value) {
                $headers_string .= $key . ': ' . $value . "\r\n";
            }

            // Send email
            $email_sent = mail($to_email, $subject, $email_message, $headers_string);

            if ($email_sent) {
                error_log("Contact form email sent successfully to: " . $to_email);
                $response['debug'][] = "Email sent successfully";
            } else {
                $email_error = "Failed to send email";
            }
        } else {
            $email_error = "Email not configured (please update to_email in contact_submit.php)";
        }
    } catch (Exception $e) {
        $email_error = "Email error: " . $e->getMessage();
        error_log("Email error in contact form: " . $e->getMessage());
    }

    if (!empty($email_error)) {
        $response['debug'][] = $email_error;
    }

    // Determine success based on database save or email send
    if ($db_saved || $email_sent) {
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you soon.';

        // Log successful submission
        error_log("Contact form submission successful - DB: " . ($db_saved ? 'Yes' : 'No') . ", Email: " . ($email_sent ? 'Yes' : 'No'));
    } else {
        $response['message'] = 'Unable to process your request at this time. Please try again later.';
        if (!empty($response['debug'])) {
            $response['message'] .= ' (Issues: ' . implode(', ', $response['debug']) . ')';
        }
    }
} catch (Throwable $e) {
    error_log("Contact form fatal error: " . $e->getMessage());
    $response['message'] = 'A system error occurred. Please try again later.';
    $response['debug'][] = 'Fatal error: ' . $e->getMessage();
}

// Output the final response
outputResponse($response);
