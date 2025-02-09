<?php
// export.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    // Get export criteria
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date   = isset($_POST['end_date'])   ? trim($_POST['end_date'])   : '';

    // Build query criteria
    $whereClauses = ["user_id = ?"];
    $params = [$_SESSION['user_id']];
    if (!empty($start_date)) {
        $whereClauses[] = "flight_date >= ?";
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $whereClauses[] = "flight_date <= ?";
        $params[] = $end_date;
    }
    $whereSQL = implode(" AND ", $whereClauses);

    // Retrieve matching flight records (removed notes column)
    $stmt = $pdo->prepare("SELECT flight_date, aircraft_id, custom_aircraft_details, flight_from, flight_to, flight_duration 
                           FROM flights 
                           WHERE $whereSQL 
                           ORDER BY flight_date DESC");
    $stmt->execute($params);
    $flights = $stmt->fetchAll();

    // Set headers for CSV download.
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="flight_log_export.csv"');

    $output = fopen('php://output', 'w');
    // CSV header row (removed "Notes")
    fputcsv($output, ['Date', 'Aircraft', 'From', 'To', 'Duration']);
    // Loop through flights and write CSV rows.
    foreach ($flights as $flight) {
        // Determine aircraft details.
        if ($flight['aircraft_id'] !== null) {
            $stmtA = $pdo->prepare("SELECT registration FROM aircraft WHERE id = ?");
            $stmtA->execute([$flight['aircraft_id']]);
            $aircraft = $stmtA->fetch(PDO::FETCH_ASSOC);
            $aircraft_reg = ($aircraft && isset($aircraft['registration'])) ? $aircraft['registration'] : 'Unknown';
        } else {
            $aircraft_reg = $flight['custom_aircraft_details'];
        }
        // Convert flight_from and flight_to to base names if numeric.
        $from = $flight['flight_from'];
        if (is_numeric($from)) {
            $stmtB = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
            $stmtB->execute([$from]);
            $base = $stmtB->fetch(PDO::FETCH_ASSOC);
            if ($base) {
                $from = $base['base_name'];
            }
        }
        $to = $flight['flight_to'];
        if (is_numeric($to)) {
            $stmtB = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
            $stmtB->execute([$to]);
            $base = $stmtB->fetch(PDO::FETCH_ASSOC);
            if ($base) {
                $to = $base['base_name'];
            }
        }
        fputcsv($output, [
            $flight['flight_date'],
            $aircraft_reg,
            $from,
            $to,
            $flight['flight_duration']
        ]);
    }
    fclose($output);
    exit;
} else {
    // Show the export form
    include('header.php');
    ?>
    <div class="flight-entry-container">
      <h2>Export Flight Log</h2>
      <form method="post" action="export.php">
        <div class="form-group">
          <label for="start_date">Start Date (optional):</label>
          <input type="date" name="start_date" id="start_date">
        </div>
        <div class="form-group">
          <label for="end_date">End Date (optional):</label>
          <input type="date" name="end_date" id="end_date">
        </div>
        <!-- Additional filters can be added here -->
        <div class="form-group">
          <input type="submit" name="export" value="Export CSV">
        </div>
      </form>
    </div>
    <?php
    include('footer.php');
}
?>
