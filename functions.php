<?php
// functions.php
if (session_status() == PHP_SESSION_NONE) {
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

// Audit logging function (if not already defined)
if (!function_exists('logAudit')) {
    function logAudit($pdo, $user_id, $flight_id, $action, $details = "") {
        $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $flight_id, $action, $details]);
    }
}
?>
