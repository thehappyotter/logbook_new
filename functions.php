<?php
// functions.php

// Set secure session cookie parameters before starting the session.
if (session_status() === PHP_SESSION_NONE) {
    // Check if HTTPS is enabled.
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // use the default domain
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFToken() {
    return generateCSRFToken();
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Audit logging function.
if (!function_exists('logAudit')) {
    function logAudit($pdo, $user_id, $flight_id, $action, $details = "") {
        $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $flight_id, $action, $details]);
    }
}

// Helper to convert a base value (numeric id or free text) to a safe base name.
function getBaseName($pdo, $value) {
    if (is_numeric($value)) {
        $stmt = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
        $stmt->execute([$value]);
        if ($base = $stmt->fetch()) {
            return htmlspecialchars($base['base_name']);
        }
    }
    return htmlspecialchars($value);
}
?>
