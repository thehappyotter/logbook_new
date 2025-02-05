<?php
// flight_delete.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Flight ID not specified.");
}

$flight_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

if (!$flight) {
    die("Flight record not found.");
}

if ($flight['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

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
