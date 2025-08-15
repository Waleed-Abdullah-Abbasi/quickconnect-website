<?php
// middleware/auth.php
// Admin Authentication Middleware

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../../config/database.php';

class AdminAuth
{
    private static $sessionTimeout = 1800; // 30 minutes

    /**
     * Check if admin is logged in
     */
    public static function isLoggedIn()
    {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['admin_last_activity'])) {
            if (time() - $_SESSION['admin_last_activity'] > self::$sessionTimeout) {
                self::logout();
                return false;
            }
        }

        // Update last activity
        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    /**
     * Login admin user
     */
    public static function login($username, $email, $userId)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_login_time'] = time();

        // Generate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Log successful login
        self::logActivity("Admin login successful: " . $username . " (IP: " . self::getClientIP() . ")");
    }

    /**
     * Get current admin user info
     */
    public static function getAdminUser()
    {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['admin_user_id'],
                'username' => $_SESSION['admin_username'],
                'email' => $_SESSION['admin_email'],
                'login_time' => $_SESSION['admin_login_time'] ?? time()
            ];
        }
        return null;
    }

    /**
     * Logout admin user
     */
    public static function logout()
    {
        if (isset($_SESSION['admin_username'])) {
            self::logActivity("Admin logout: " . $_SESSION['admin_username']);
        }

        // Unset all admin session variables
        $sessionVars = [
            'admin_logged_in',
            'admin_user_id',
            'admin_username',
            'admin_email',
            'admin_last_activity',
            'admin_login_time',
            'csrf_token'
        ];

        foreach ($sessionVars as $var) {
            unset($_SESSION[$var]);
        }

        // Regenerate session ID
        session_regenerate_id(true);
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get client IP address
     */
    private static function getClientIP()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }

        return 'Unknown';
    }

    /**
     * Log security activities
     */
    private static function logActivity($message)
    {
        $logDir = __DIR__ . '/../logs';
        $logFile = $logDir . '/security.log';

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Authenticate admin user (for login process)
     */
    public static function authenticate($username, $password)
    {
        try {
            $database = new Database();
            $db = $database->getConnection();

            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $stmt = $db->prepare("SELECT id, username, email, password FROM admin_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }

            // Log failed attempt
            self::logActivity("Failed login attempt for: " . $username . " (IP: " . self::getClientIP() . ")");
            return false;
        } catch (Exception $e) {
            self::logActivity("Login error: " . $e->getMessage());
            return false;
        }
    }
}
