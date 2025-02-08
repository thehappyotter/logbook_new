<?php
// flight_delete.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    die("Invalid request.");
}

if (!isset($_POST['id'])) {
    die("Flight ID not specified.");
}

$flight_id = $_POST['id'];

// Retrieve the flight record.
$stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

if (!$flight) {
    die("Flight record not found.");
}

// Ensure the user is authorized.
if ($flight['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

// First, delete any associated flight breakdown records.
$stmtBreakdown = $pdo->prepare("DELETE FROM flight_breakdown WHERE flight_id = ?");
$stmtBreakdown->execute([$flight_id]);

// Now delete the flight record.
$stmt = $pdo->prepare("DELETE FROM flights WHERE id = ?");
if ($stmt->execute([$flight_id])) {
    // Log the deletion.
    $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $flight_id, 'delete', 'Flight record deleted.']);
    header("Location: index.php");
    exit;
} else {
    die("Failed to delete flight record.");
}
?>
