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

// (Optional) Verify that the user is allowed to view this flight.
if ($flight['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

// Retrieve aircraft details if applicable.
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

// Retrieve base names for From and To if needed.
function getBaseName($pdo, $value) {
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

$fromName = getBaseName($pdo, $flight['flight_from']);
$toName = getBaseName($pdo, $flight['flight_to']);

// Retrieve flight breakdown details.
$stmtBreakdown = $pdo->prepare("SELECT role, duration_minutes FROM flight_breakdown WHERE flight_id = ?");
$stmtBreakdown->execute([$flight_id]);
$breakdowns = $stmtBreakdown->fetchAll();

include('header.php');
?>
<div class="flight-view-container" style="max-width:800px; margin:20px auto; padding:20px; border:1px solid #ccc; border-radius:5px; background:#fff;">
  <h2>Full Flight Data</h2>
  
  <!-- Flight Details Section -->
  <h3>Flight Details</h3>
  <table>
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
  
  <!-- Flight Times / Breakdown Section -->
  <h3>Flight Role Breakdown</h3>
  <?php if ($breakdowns): ?>
  <table>
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
  
  <!-- NVG Section -->
  <h3>NVG</h3>
  <table>
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
  
  <!-- Instrument Flight Section -->
  <h3>Instrument Flight</h3>
  <table>
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
  
  <p><a href="index.php">Back to Flight Log</a></p>
</div>
<?php include('footer.php'); ?>
