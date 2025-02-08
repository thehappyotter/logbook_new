<?php
// flight_edit.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function logAudit($pdo, $user_id, $flight_id, $action, $details = "") {
    $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $flight_id, $action, $details]);
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

$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

$error = [];
$success = [];
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        $flight_date = $_POST['flight_date'];
        $aircraft_id = $_POST['aircraft_id'];
        $flight_from = $_POST['flight_from'];
        $flight_to   = $_POST['flight_to'];
        $capacity    = $_POST['capacity'];
        $pilot_type  = $_POST['pilot_type'];
        $crew_names  = $_POST['crew_names'];
        $rotors_start = $_POST['rotors_start'];
        $rotors_stop  = $_POST['rotors_stop'];
        $night_vision = isset($_POST['night_vision']) ? 1 : 0;
        $night_vision_duration = $_POST['night_vision_duration'];
        $takeoffs    = $_POST['takeoffs'];
        $landings    = $_POST['landings'];
        $notes       = $_POST['notes'];

        try {
            $start = new DateTime($rotors_start);
            $stop  = new DateTime($rotors_stop);
        } catch (Exception $e) {
            $error[] = "Invalid time format.";
        }
        if (empty($error)) {
            $interval = $start->diff($stop);
            $flight_duration = $interval->format('%H:%I:%S');

            $stmt = $pdo->prepare("UPDATE flights SET flight_date = ?, aircraft_id = ?, flight_from = ?, flight_to = ?, capacity = ?, pilot_type = ?, crew_names = ?, rotors_start = ?, rotors_stop = ?, night_vision = ?, night_vision_duration = ?, takeoffs = ?, landings = ?, notes = ?, flight_duration = ? WHERE id = ?");
            if ($stmt->execute([$flight_date, $aircraft_id, $flight_from, $flight_to, $capacity, $pilot_type, $crew_names, $rotors_start, $rotors_stop, $night_vision, $night_vision_duration, $takeoffs, $landings, $notes, $flight_duration, $flight_id])) {
                logAudit($pdo, $_SESSION['user_id'], $flight_id, 'edit', 'Flight record updated.');
                $success[] = "Flight record updated successfully.";
                $stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
                $stmt->execute([$flight_id]);
                $flight = $stmt->fetch();
            } else {
                $error[] = "Failed to update flight record.";
            }
        }
    }
}

include('header.php');
?>
<div class="flight-entry-container">
  <h2>Edit Flight Record</h2>
  <?php 
    foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
    foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
  ?>
  <form method="post" action="flight_edit.php?id=<?php echo htmlspecialchars($flight_id); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <div class="form-group">
      <label for="flight_date">Date:</label>
      <input type="date" name="flight_date" id="flight_date" value="<?php echo htmlspecialchars($flight['flight_date']); ?>" required>
    </div>
    <div class="form-group">
      <label for="aircraft_id">Aircraft Registration:</label>
      <select name="aircraft_id" required>
        <option value="">Select Aircraft</option>
        <?php foreach ($aircraft_list as $aircraft): ?>
          <option value="<?php echo $aircraft['id']; ?>" <?php if ($aircraft['id'] == $flight['aircraft_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($aircraft['registration']); ?> – <?php echo htmlspecialchars($aircraft['type']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="flight_from">Flight From:</label>
      <input type="text" name="flight_from" id="flight_from" value="<?php echo htmlspecialchars($flight['flight_from']); ?>" required>
    </div>
    <div class="form-group">
      <label for="flight_to">Flight To:</label>
      <input type="text" name="flight_to" id="flight_to" value="<?php echo htmlspecialchars($flight['flight_to']); ?>" required>
    </div>
    <div class="form-group">
      <label for="capacity">Capacity:</label>
      <select name="capacity">
        <option value="pilot" <?php if ($flight['capacity'] == 'pilot') echo 'selected'; ?>>Pilot</option>
        <option value="crew" <?php if ($flight['capacity'] == 'crew') echo 'selected'; ?>>Crew</option>
      </select>
    </div>
    <div class="form-group">
      <label for="pilot_type">Pilot Type:</label>
      <select name="pilot_type">
        <option value="single" <?php if ($flight['pilot_type'] == 'single') echo 'selected'; ?>>Single Pilot</option>
        <option value="multi" <?php if ($flight['pilot_type'] == 'multi') echo 'selected'; ?>>Multi Pilot</option>
      </select>
    </div>
    <div class="form-group">
      <label for="crew_names">Crew Names (comma separated):</label>
      <input type="text" name="crew_names" id="crew_names" value="<?php echo htmlspecialchars($flight['crew_names']); ?>">
    </div>
    <div class="form-group">
      <label for="rotors_start">Rotors Start Time:</label>
      <input type="time" name="rotors_start" id="rotors_start" value="<?php echo htmlspecialchars($flight['rotors_start']); ?>" required>
    </div>
    <div class="form-group">
      <label for="rotors_stop">Rotors Stop Time:</label>
      <input type="time" name="rotors_stop" id="rotors_stop" value="<?php echo htmlspecialchars($flight['rotors_stop']); ?>" required>
    </div>
    <div class="form-group">
      <label for="night_vision">Night Vision Goggles used:</label>
      <input type="checkbox" name="night_vision" value="1" <?php if ($flight['night_vision']) echo 'checked'; ?>>
    </div>
    <div class="form-group">
      <label for="night_vision_duration">Night Vision Duration (minutes):</label>
      <input type="number" name="night_vision_duration" min="0" value="<?php echo htmlspecialchars($flight['night_vision_duration']); ?>">
    </div>
    <div class="form-group">
      <label for="takeoffs">Number of Takeoffs:</label>
      <input type="number" name="takeoffs" min="0" value="<?php echo htmlspecialchars($flight['takeoffs']); ?>">
    </div>
    <div class="form-group">
      <label for="landings">Number of Landings:</label>
      <input type="number" name="landings" min="0" value="<?php echo htmlspecialchars($flight['landings']); ?>">
    </div>
    <div class="form-group">
      <label for="notes">Notes:</label>
      <textarea name="notes" id="notes"><?php echo htmlspecialchars($flight['notes']); ?></textarea>
    </div>
    <div class="form-group">
      <input type="submit" value="Update Flight Record">
    </div>
  </form>
</div>
<?php include('footer.php'); ?>
