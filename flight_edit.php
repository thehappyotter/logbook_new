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
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Edit Flight Record</h2>
  </div>
  <div class="card-body">
    <?php 
      foreach ($error as $msg) { 
          echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>"; 
      }
      foreach ($success as $msg) { 
          echo "<div class='alert alert-success'>" . htmlspecialchars($msg) . "</div>"; 
      }
    ?>
    <form method="post" action="flight_edit.php?id=<?php echo htmlspecialchars($flight_id); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <div class="mb-3 row">
        <label for="flight_date" class="col-sm-2 col-form-label">Date:</label>
        <div class="col-sm-4">
          <input type="date" name="flight_date" id="flight_date" class="form-control" value="<?php echo htmlspecialchars($flight['flight_date']); ?>" required>
        </div>
      </div>
      <div class="mb-3 row">
        <label for="aircraft_id" class="col-sm-2 col-form-label">Aircraft:</label>
        <div class="col-sm-6">
          <select name="aircraft_id" id="aircraft_id" class="form-select" required>
            <option value="">Select Aircraft</option>
            <?php foreach ($aircraft_list as $aircraft): ?>
              <option value="<?php echo $aircraft['id']; ?>" <?php if ($aircraft['id'] == $flight['aircraft_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($aircraft['registration']); ?> &ndash; <?php echo htmlspecialchars($aircraft['type']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3 row">
        <label for="flight_from" class="col-sm-2 col-form-label">From:</label>
        <div class="col-sm-4">
          <input type="text" name="flight_from" id="flight_from" class="form-control" value="<?php echo htmlspecialchars($flight['flight_from']); ?>" required>
        </div>
        <label for="flight_to" class="col-sm-2 col-form-label">To:</label>
        <div class="col-sm-4">
          <input type="text" name="flight_to" id="flight_to" class="form-control" value="<?php echo htmlspecialchars($flight['flight_to']); ?>" required>
        </div>
      </div>
      <div class="mb-3 row">
        <label for="capacity" class="col-sm-2 col-form-label">Capacity:</label>
        <div class="col-sm-4">
          <select name="capacity" id="capacity" class="form-select">
            <option value="pilot" <?php if ($flight['capacity'] == 'pilot') echo 'selected'; ?>>Pilot</option>
            <option value="crew" <?php if ($flight['capacity'] == 'crew') echo 'selected'; ?>>Crew</option>
          </select>
        </div>
        <label for="pilot_type" class="col-sm-2 col-form-label">Pilot Type:</label>
        <div class="col-sm-4">
          <select name="pilot_type" id="pilot_type" class="form-select">
            <option value="single" <?php if ($flight['pilot_type'] == 'single') echo 'selected'; ?>>Single Pilot</option>
            <option value="multi" <?php if ($flight['pilot_type'] == 'multi') echo 'selected'; ?>>Multi Pilot</option>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label for="crew_names" class="form-label">Crew Names:</label>
        <input type="text" name="crew_names" id="crew_names" class="form-control" value="<?php echo htmlspecialchars($flight['crew_names']); ?>">
      </div>
      <div class="mb-3 row">
        <label for="rotors_start" class="col-sm-2 col-form-label">Rotors Start:</label>
        <div class="col-sm-4">
          <input type="time" name="rotors_start" id="rotors_start" class="form-control" value="<?php echo htmlspecialchars($flight['rotors_start']); ?>" required oninput="recalcBreakdown();">
        </div>
        <label for="rotors_stop" class="col-sm-2 col-form-label">Rotors Stop:</label>
        <div class="col-sm-4">
          <input type="time" name="rotors_stop" id="rotors_stop" class="form-control" value="<?php echo htmlspecialchars($flight['rotors_stop']); ?>" required oninput="recalcBreakdown();">
        </div>
      </div>
      <div class="mb-3">
        <label for="notes" class="form-label">Notes:</label>
        <textarea name="notes" id="notes" class="form-control"><?php echo htmlspecialchars($flight['notes']); ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Update Flight Record</button>
    </form>
  </div>
</div>
<?php include('footer.php'); ?>
