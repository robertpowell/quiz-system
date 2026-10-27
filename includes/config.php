<?php
// Configuration file for Quiz System

// Data and logs live outside the web root (RoPo, 2026-10-27). Under
// /var/www/html they were downloadable; Apache now 404s the old paths.
define('DATA_DIR', '/var/lib/quiz-system/data');

// Database configuration
define('DB_PATH', DATA_DIR . '/quiz_database.sqlite');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', DATA_DIR . '/quizzes');
define('LOG_PATH', '/var/log/quiz-system');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Application settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('MAX_PARTICIPANTS_PER_QUIZ', 100);
define('DEFAULT_QUIZ_TIME_LIMIT', 600); // 10 minutes in seconds
define('COLORS_FILE', ASSETS_PATH . '/colors.json');

// Timezone
date_default_timezone_set('UTC');

// Error reporting.
//
// display_errors is OFF, as the old comment here ("disable in production")
// always intended. This app was the only thing on the box turning it back on --
// /etc/php/8.4/fpm/php.ini has it Off globally -- so a PHP notice or warning
// anywhere in quiz-system was rendered into the response body and shipped to
// the visitor. That leaks file paths and internals, and it is also what turned
// every admin page into a 403 once: ModSecurity's outbound rules correctly read
// a rendered PHP warning as information disclosure.
//
// Errors are still recorded: log_errors is On globally, so they go to the
// PHP-FPM log. Nothing is being silenced, only kept out of the page.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// Session configuration (only for web requests, not CLI)
if (php_sapi_name() !== 'cli') {
    // Only settable before a session starts. The admin gate
    // (includes/admin_auth.php) starts one first and already sets HttpOnly,
    // Secure and SameSite=Strict on its own cookie, so calling these with an
    // active session just emits a warning -- which ModSecurity then blocks as
    // information disclosure, turning every admin page into a 403.
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
    }

    // CSRF Protection
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Helper function to verify CSRF token
function verify_csrf_token($token) {
    if (php_sapi_name() === 'cli') return true;
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to get CSRF token
function get_csrf_token() {
    if (php_sapi_name() === 'cli') return '';
    return $_SESSION['csrf_token'] ?? '';
}

// Logging function
function log_activity($type, $message, $data = []) {
    $logFile = LOG_PATH . '/' . $type . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf(
        "[%s] %s | Data: %s\n",
        $timestamp,
        $message,
        json_encode($data)
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Sanitization helper
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// JSON response helper
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Generate random session code
function generate_session_code() {
    return strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
}

// Get random color from pool
function get_random_color($exclude = []) {
    $colors = json_decode(file_get_contents(COLORS_FILE), true);

    // Filter out excluded colors
    if (!empty($exclude)) {
        $colors = array_filter($colors, function($color) use ($exclude) {
            return !in_array($color['name'], $exclude);
        });
        $colors = array_values($colors); // Re-index array
    }

    if (empty($colors)) {
        // Fallback if all colors are used
        $allColors = json_decode(file_get_contents(COLORS_FILE), true);
        return $allColors[array_rand($allColors)];
    }

    return $colors[array_rand($colors)];
}

// Ensure required directories exist
$dirs = [UPLOAD_PATH, LOG_PATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

?>
