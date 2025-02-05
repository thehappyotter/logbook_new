<?php
// export.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT f.*, a.registration FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE f.user_id = ? ORDER BY f.flight_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$flights = $stmt->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=flight_log.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Aircraft', 'From', 'To', 'Duration', 'Notes']);
foreach ($flights as $flight) {
    fputcsv($output, [
        $flight['flight_date'],
        $flight['registration'],
        $flight['flight_from'],
        $flight['flight_to'],
        $flight['flight_duration'],
        $flight['notes']
    ]);
}
fclose($output);
exit;
?>
