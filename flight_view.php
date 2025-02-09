<?php
// flight_view.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Flight ID not specified.");
}

$flight_id = $_GET['id'];

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

// Retrieve aircraft details.
$aircraftInfo = "";
if ($flight['aircraft_id'] !== null) {
    $stmtA = $pdo->prepare("SELECT registration, type FROM aircraft WHERE id = ?");
    $stmtA->execute([$flight['aircraft_id']]);
    $aircraft = $stmtA->fetch(PDO::FETCH_ASSOC);
    if ($aircraft) {
        $aircraftInfo = htmlspecialchars($aircraft['registration']) . " - " . htmlspecialchars($aircraft['type']);
    }
} else {
    $aircraftInfo = htmlspecialchars($flight['custom_aircraft_details']);
}

// Helper to get base name.
function getBaseNameDisplay($pdo, $value) {
    if (is_numeric($value)) {
        $stmtB = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
        $stmtB->execute([$value]);
        $base = $stmtB->fetch(PDO::FETCH_ASSOC);
        if ($base) {
            return htmlspecialchars($base['base_name']);
        }
    }
    return htmlspecialchars($value);
}

$fromName = getBaseNameDisplay($pdo, $flight['flight_from']);
$toName   = getBaseNameDisplay($pdo, $flight['flight_to']);

// Retrieve flight breakdown details.
$stmtBreakdown = $pdo->prepare("SELECT role, duration_minutes FROM flight_breakdown WHERE flight_id = ?");
$stmtBreakdown->execute([$flight_id]);
$breakdowns = $stmtBreakdown->fetchAll();

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Flight Details</h2>
  </div>
  <div class="card-body">
    <div class="mb-3">
      <a class="btn btn-secondary" href="flight_view_pdf.php?id=<?php echo htmlspecialchars($flight_id); ?>" target="_blank">Download PDF</a>
    </div>
    <h4>Flight Information</h4>
    <table class="table table-striped">
      <tr>
        <th>Date</th>
        <td><?php echo htmlspecialchars($flight['flight_date']); ?></td>
      </tr>
      <tr>
        <th>Aircraft</th>
        <td><?php echo $aircraftInfo; ?></td>
      </tr>
      <tr>
        <th>From</th>
        <td><?php echo $fromName; ?></td>
      </tr>
      <tr>
        <th>To</th>
        <td><?php echo $toName; ?></td>
      </tr>
      <tr>
        <th>Capacity</th>
        <td><?php echo htmlspecialchars($flight['capacity']); ?></td>
      </tr>
      <tr>
        <th>Pilot Type</th>
        <td><?php echo htmlspecialchars($flight['pilot_type']); ?></td>
      </tr>
      <tr>
        <th>Crew Names</th>
        <td><?php echo htmlspecialchars($flight['crew_names']); ?></td>
      </tr>
      <tr>
        <th>Rotors Start</th>
        <td><?php echo htmlspecialchars($flight['rotors_start']); ?></td>
      </tr>
      <tr>
        <th>Rotors Stop</th>
        <td><?php echo htmlspecialchars($flight['rotors_stop']); ?></td>
      </tr>
      <tr>
        <th>Flight Duration</th>
        <td><?php echo htmlspecialchars($flight['flight_duration']); ?></td>
      </tr>
    </table>
    <h4>Flight Role Breakdown</h4>
    <?php if ($breakdowns): ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Role</th>
            <th>Duration (minutes)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($breakdowns as $bd): ?>
          <tr>
            <td><?php echo htmlspecialchars($bd['role']); ?></td>
            <td><?php echo htmlspecialchars($bd['duration_minutes']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No breakdown details available.</p>
    <?php endif; ?>
    
    <h4>NVG</h4>
    <table class="table table-bordered">
      <tr>
        <th>NVG Time (minutes)</th>
        <td><?php echo htmlspecialchars($flight['nvg_time']); ?></td>
      </tr>
      <tr>
        <th>NVG Takeoffs</th>
        <td><?php echo htmlspecialchars($flight['nvg_takeoffs']); ?></td>
      </tr>
      <tr>
        <th>NVG Landings</th>
        <td><?php echo htmlspecialchars($flight['nvg_landings']); ?></td>
      </tr>
    </table>
    
    <h4>Instrument Flight</h4>
    <table class="table table-bordered">
      <tr>
        <th>Sim IF</th>
        <td><?php echo htmlspecialchars($flight['sim_if']); ?></td>
      </tr>
      <tr>
        <th>Act IF</th>
        <td><?php echo htmlspecialchars($flight['act_if']); ?></td>
      </tr>
      <tr>
        <th>ILS Approaches</th>
        <td><?php echo htmlspecialchars($flight['ils_approaches']); ?></td>
      </tr>
      <tr>
        <th>RNP</th>
        <td><?php echo htmlspecialchars($flight['rnp']); ?></td>
      </tr>
      <tr>
        <th>NPA</th>
        <td><?php echo htmlspecialchars($flight['npa']); ?></td>
      </tr>
    </table>
    <a class="btn btn-primary" href="index.php">Back to Flight Log</a>
  </div>
</div>
<?php include('footer.php'); ?>
