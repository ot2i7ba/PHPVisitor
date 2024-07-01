<?php
/**
 * Simple VisitorTracker
 * Script to store visitor informations
 *
 * @copyright (c) 2024 ot2i7ba
 * https://github.com/ot2i7ba/
 * @license MIT License
 */

namespace VisitorTracker;

define(__NAMESPACE__ . '\\LOG_FILE_NAME', 'visitor.json');
define(__NAMESPACE__ . '\\NOTIFICATION', true);
define(__NAMESPACE__ . '\\EMAIL_TO', 'your-email@example.com');
define(__NAMESPACE__ . '\\EMAIL_CC_ENABLED', true); // Enable or disable CC notification
define(__NAMESPACE__ . '\\EMAIL_CC', 'cc-email@example.com');
define(__NAMESPACE__ . '\\EMAIL_SUBJECT', 'New Visitor Alert');
define(__NAMESPACE__ . '\\EMAIL_MESSAGE', 'A new visitor has accessed the site.');
define(__NAMESPACE__ . '\\EMAIL_FROM', 'sender-email@example.com'); // Define sender email
define(__NAMESPACE__ . '\\LOG_DIR', __DIR__ . '/logs'); // Directory for visitor.json
define(__NAMESPACE__ . '\\RATE_LIMIT', 100); // Maximum requests per hour
define(__NAMESPACE__ . '\\RATE_LIMIT_WINDOW', 3600); // Time window in seconds (1 hour)

set_error_handler(__NAMESPACE__ . '\\customErrorHandler');

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] in $errfile at line $errline: $errstr");
    if (ini_get('display_errors')) {
        echo "An error occurred. Please try again later.";
    }
    return true;
}

// Ensure log directory exists
if (!file_exists(LOG_DIR)) {
    if (!mkdir(LOG_DIR, 0755, true)) {
        die("Failed to create log directory: " . LOG_DIR);
    }
}

// Ensure .htaccess file exists to prevent direct access
$htaccessPath = LOG_DIR . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "Order allow,deny\nDeny from all";
    file_put_contents($htaccessPath, $htaccessContent);
}

$logFilePath = LOG_DIR . '/' . LOG_FILE_NAME;

function getClientIP() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return filter_var($_SERVER[$key], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
        }
    }
    return 'UNKNOWN';
}

function getVisitDuration() {
    return isset($_SESSION['start_time']) ? time() - $_SESSION['start_time'] : 0;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['start_time'])) {
        $_SESSION['start_time'] = time();
    }
}

function isRateLimited($ip) {
    $rateLimitFile = LOG_DIR . '/rate_limit.json';
    $rateLimits = [];

    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(file_get_contents($rateLimitFile), true);
    }

    $currentTime = time();
    $windowStart = $currentTime - RATE_LIMIT_WINDOW;

    foreach ($rateLimits as $ip => $timestamps) {
        $rateLimits[$ip] = array_filter($timestamps, function($timestamp) use ($windowStart) {
            return $timestamp >= $windowStart;
        });

        if (empty($rateLimits[$ip])) {
            unset($rateLimits[$ip]);
        }
    }

    if (!isset($rateLimits[$ip])) {
        $rateLimits[$ip] = [];
    }

    if (count($rateLimits[$ip]) >= RATE_LIMIT) {
        return true;
    }

    $rateLimits[$ip][] = $currentTime;

    file_put_contents($rateLimitFile, json_encode($rateLimits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return false;
}

$clientIP = getClientIP();
if (isRateLimited($clientIP)) {
    header('HTTP/1.1 429 Too Many Requests');
    echo "Too many requests. Please try again later.";
    exit();
}

$visitorData = [
    'ip_address' => filter_var($clientIP, FILTER_VALIDATE_IP),
    'visit_date' => date('Y-m-d'),
    'visit_time' => date('H:i:s'),
    'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8'),
    'referrer_url' => isset($_SERVER['HTTP_REFERER']) ? filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL) : 'Direct Access',
    'visit_duration' => getVisitDuration()
];

function logVisitorData($logFilePath, $visitorData) {
    $logEntries = [];
    if (file_exists($logFilePath)) {
        $logEntries = json_decode(file_get_contents($logFilePath), true);
        if (!is_array($logEntries)) {
            $logEntries = [];
        }
    }
    $logEntries[] = $visitorData;
    $result = file_put_contents($logFilePath, json_encode($logEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    
    // Debugging output
    if ($result === false) {
        echo "Error writing to log file: " . $logFilePath;
    } else {
        echo "Log file successfully written: " . $logFilePath;
    }
}

logVisitorData($logFilePath, $visitorData);

if (NOTIFICATION) {
    $headers = 'From: ' . EMAIL_FROM . "\r\n";
    if (EMAIL_CC_ENABLED) {
        $headers .= 'CC: ' . EMAIL_CC . "\r\n";
    }
    mail(EMAIL_TO, EMAIL_SUBJECT, EMAIL_MESSAGE, $headers);
}
?>
