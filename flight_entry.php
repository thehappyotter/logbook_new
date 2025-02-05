<?php
// flight_entry.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Function to log audit trail events.
function logAudit($pdo, $user_id, $flight_id, $action, $details = "") {
    $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $flight_id, $action, $details]);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $flight_date = $_POST['flight_date'];
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

    // Determine if "Other" is selected.
    $use_custom_aircraft = isset($_POST['other_aircraft']) && $_POST['other_aircraft'] == 'on';
    if ($use_custom_aircraft) {
         $custom_aircraft_details = trim($_POST['custom_aircraft_details']);
         $aircraft_id = null;  // Not using an ID from the master list.
    } else {
         $aircraft_id = $_POST['aircraft_id'];
         $custom_aircraft_details = null;
    }

    // Calculate flight duration.
    try {
         $start = new DateTime($rotors_start);
         $stop  = new DateTime($rotors_stop);
    } catch (Exception $e) {
         $error = "Invalid time format.";
    }
    if (!$error) {
         $interval = $start->diff($stop);
         $flight_duration = $interval->format('%H:%I:%S');

         $stmt = $pdo->prepare("INSERT INTO flights (user_id, flight_date, aircraft_id, custom_aircraft_details, flight_from, flight_to, capacity, pilot_type, crew_names, rotors_start, rotors_stop, night_vision, night_vision_duration, takeoffs, landings, notes, flight_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
         if ($stmt->execute([$_SESSION['user_id'], $flight_date, $aircraft_id, $custom_aircraft_details, $flight_from, $flight_to, $capacity, $pilot_type, $crew_names, $rotors_start, $rotors_stop, $night_vision, $night_vision_duration, $takeoffs, $landings, $notes, $flight_duration])) {
              $flight_id = $pdo->lastInsertId();
              logAudit($pdo, $_SESSION['user_id'], $flight_id, 'create', 'Flight record created.');
              $success = "Flight record added successfully.";
         } else {
              $error = "Failed to add flight record.";
         }
    }
}

// Retrieve master aircraft list for the dropdown.
$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

// Retrieve the user’s default capacity for preselection.
$stmt = $pdo->prepare("SELECT default_role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch();
$default_capacity = $user_info['default_role'];

include('header.php');
?>
<h2>Enter New Flight Record</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<?php if ($success) { echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; } ?>
<form method="post" action="flight_entry.php">
  <label for="flight_date">Date:</label>
  <input type="date" name="flight_date" required>
  
  <!-- Master list dropdown -->
  <label for="aircraft_id">Aircraft Registration:</label>
  <select name="aircraft_id" id="aircraftDropdown">
    <option value="">Select Aircraft</option>
    <?php foreach ($aircraft_list as $aircraft): ?>
      <option value="<?php echo $aircraft['id']; ?>">
        <?php echo htmlspecialchars($aircraft['registration']); ?> – <?php echo htmlspecialchars($aircraft['type']); ?>
      </option>
    <?php endforeach; ?>
  </select>
  
  <!-- "Other" checkbox -->
  <label>
    <input type="checkbox" name="other_aircraft" id="otherAircraftCheckbox"> Other (not in list)
  </label>
  
  <!-- Custom aircraft details fields (hidden by default) -->
  <div id="customAircraftDetails" style="display: none;">
    <label for="custom_aircraft_details">Enter Aircraft Details:</label>
    <textarea name="custom_aircraft_details" placeholder="Enter details (Registration, Type, etc.)"></textarea>
  </div>
  
  <label for="flight_from">Flight From:</label>
  <input type="text" name="flight_from" required>
  
  <label for="flight_to">Flight To:</label>
  <input type="text" name="flight_to" required>
  
  <label for="capacity">Capacity:</label>
  <select name="capacity">
    <option value="pilot" <?php if ($default_capacity=='pilot') echo 'selected'; ?>>Pilot</option>
    <option value="crew" <?php if ($default_capacity=='crew') echo 'selected'; ?>>Crew</option>
  </select>
  
  <label for="pilot_type">Pilot Type:</label>
  <select name="pilot_type">
    <option value="single">Single Pilot</option>
    <option value="multi">Multi Pilot</option>
  </select>
  
  <label for="crew_names">Crew Names (comma separated):</label>
  <input type="text" name="crew_names">
  
  <label for="rotors_start">Rotors Start Time:</label>
  <input type="time" name="rotors_start" required>
  
  <label for="rotors_stop">Rotors Stop Time:</label>
  <input type="time" name="rotors_stop" required>
  
  <label for="night_vision">Night Vision Goggles used:</label>
  <input type="checkbox" name="night_vision" value="1">
  
  <label for="night_vision_duration">Night Vision Duration (minutes):</label>
  <input type="number" name="night_vision_duration" min="0" value="0">
  
  <label for="takeoffs">Number of Takeoffs:</label>
  <input type="number" name="takeoffs" min="0" value="0">
  
  <label for="landings">Number of Landings:</label>
  <input type="number" name="landings" min="0" value="0">
  
  <label for="notes">Notes:</label>
  <textarea name="notes"></textarea>
  
  <input type="submit" value="Add Flight Record">
</form>

<script>
// JavaScript to toggle custom aircraft details fields when "Other" is checked.
document.getElementById('otherAircraftCheckbox').addEventListener('change', function() {
    var customDiv = document.getElementById('customAircraftDetails');
    if (this.checked) {
        customDiv.style.display = 'block';
        // Optionally, disable the master dropdown if custom details are used.
        document.getElementById('aircraftDropdown').disabled = true;
    } else {
        customDiv.style.display = 'none';
        document.getElementById('aircraftDropdown').disabled = false;
    }
});
</script>

<?php include('footer.php'); ?>
