<?php
// config/database.php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'quickconnect_db');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $exception) {
            logError("Database connection error: " . $exception->getMessage());
            throw $exception;
        }

        return $this->conn;
    }
}

// Create global PDO connection for compatibility with index.php
try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($pdo === null) {
        throw new Exception("Failed to establish database connection");
    }

    logError("Database connection established successfully");
} catch (Exception $e) {
    logError("Database initialization failed: " . $e->getMessage());
    $pdo = null;

    // In development, you might want to see the error
    // Comment out the next line in production
    die("Database connection failed: " . $e->getMessage());
}

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@quickconnect.com');
define('FROM_NAME', 'QuickConnect');
define('ADMIN_EMAIL', 'admin@quickconnect.com');

// Site configuration
define('SITE_URL', 'http://localhost/quickconnect');
define('ADMIN_SESSION_TIMEOUT', 3600);

// Utility functions
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sendEmail($to, $subject, $message)
{
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
}

function logError($message)
{
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Additional utility functions for the application
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirectWithMessage($url, $message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}
