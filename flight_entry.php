<?php
// flight_entry.php - Updated Flight Entry with Extended Fields and Role Breakdown
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Function to log audit events (if needed)
function logAudit($pdo, $user_id, $flight_id, $action, $details = "") {
    $stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, flight_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $flight_id, $action, $details]);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve basic flight details.
    $flight_date = $_POST['flight_date'];
    
    // Aircraft: Determine whether to use the master list or manual entry.
    $aircraft_option = $_POST['aircraft_option']; // "master" or "manual"
    if ($aircraft_option === 'master') {
        $aircraft_id = $_POST['aircraft_id'];
        $custom_aircraft_details = "";
    } else {
        $aircraft_id = null;
        $aircraft_type = trim($_POST['aircraft_type']);
        $aircraft_registration = trim($_POST['aircraft_registration']);
        $custom_aircraft_details = "Type: " . $aircraft_type . ", Registration: " . $aircraft_registration;
    }
    
    // From field: Choose dropdown or manual.
    $from_option = $_POST['from_option']; // "dropdown" or "manual"
    if ($from_option === 'dropdown') {
        $flight_from = $_POST['from_base'];
    } else {
        $flight_from = trim($_POST['from_other']);
    }
    
    // To field: Choose dropdown or manual.
    $to_option = $_POST['to_option']; // "dropdown" or "manual"
    if ($to_option === 'dropdown') {
        $flight_to = $_POST['to_base'];
    } else {
        $flight_to = trim($_POST['to_other']);
    }
    
    // Other basic fields.
    $capacity = $_POST['capacity'];       // "pilot" or "crew"
    $pilot_type = $_POST['pilot_type'];     // "single" or "multi"
    $crew_names = trim($_POST['crew_names']);
    $rotors_start = $_POST['rotors_start'];
    $rotors_stop  = $_POST['rotors_stop'];
    
    // Calculate flight duration from rotors times.
    try {
        $start = new DateTime($rotors_start);
        $stop  = new DateTime($rotors_stop);
        $interval = $start->diff($stop);
        $flight_duration = $interval->format('%H:%I:%S');
    } catch (Exception $e) {
        $error = "Invalid time format for rotors start/stop.";
    }
    
    if (!$error) {
        // Insert the main flight record.
        $stmt = $pdo->prepare("INSERT INTO flights (user_id, flight_date, aircraft_id, custom_aircraft_details, flight_from, flight_to, capacity, pilot_type, crew_names, rotors_start, rotors_stop, flight_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$_SESSION['user_id'], $flight_date, $aircraft_id, $custom_aircraft_details, $flight_from, $flight_to, $capacity, $pilot_type, $crew_names, $rotors_start, $rotors_stop, $flight_duration]);
        if ($result) {
            $flight_id = $pdo->lastInsertId();
            logAudit($pdo, $_SESSION['user_id'], $flight_id, 'create', 'Flight record created with extended details.');
            
            // Process the flight role breakdown details.
            if (isset($_POST['role']) && isset($_POST['duration'])) {
                $roles = $_POST['role'];
                $durations = $_POST['duration'];
                $count = count($roles);
                for ($i = 0; $i < $count; $i++) {
                    $role = trim($roles[$i]);
                    $duration = intval($durations[$i]);
                    if ($role != "" && $duration > 0) {
                        $stmtBreakdown = $pdo->prepare("INSERT INTO flight_breakdown (flight_id, role, duration_minutes) VALUES (?, ?, ?)");
                        $stmtBreakdown->execute([$flight_id, $role, $duration]);
                    }
                }
            }
            $success = "Flight record added successfully.";
        } else {
            $error = "Failed to add flight record.";
        }
    }
}

// Fetch master list of aircraft.
$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

// Define bases for "From" and "To" fields (update these values as needed).
$bases = ["Base A", "Base B", "Base C"];

include('header.php');
?>
<h2>Enter New Flight Record</h2>
<?php 
if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; }
if ($success) { echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; } 
?>
<form method="post" action="flight_entry.php">
  <fieldset>
    <legend>Basic Flight Details</legend>
    
    <label for="flight_date">Date:</label>
    <input type="date" name="flight_date" id="flight_date" required>
    
    <!-- Aircraft Section -->
    <p><strong>Aircraft:</strong></p>
    <label>
      <input type="radio" name="aircraft_option" value="master" checked onclick="toggleAircraftFields();"> Use Master List
    </label>
    <label>
      <input type="radio" name="aircraft_option" value="manual" onclick="toggleAircraftFields();"> Enter Manually
    </label>
    <div id="aircraftMaster">
      <label for="aircraft_id">Select Aircraft (Registration - Type):</label>
      <select name="aircraft_id" id="aircraft_id">
        <option value="">Select Aircraft</option>
        <?php foreach ($aircraft_list as $ac): ?>
          <option value="<?php echo $ac['id']; ?>">
            <?php echo htmlspecialchars($ac['registration']); ?> - <?php echo htmlspecialchars($ac['type']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="aircraftManual" style="display: none;">
      <label for="aircraft_type">Aircraft Type:</label>
      <input type="text" name="aircraft_type" id="aircraft_type">
      <label for="aircraft_registration">Aircraft Registration:</label>
      <input type="text" name="aircraft_registration" id="aircraft_registration">
    </div>
    
    <!-- From Field -->
    <p><strong>From:</strong></p>
    <label>
      <input type="radio" name="from_option" value="dropdown" checked onclick="toggleFromFields();"> Select from list
    </label>
    <label>
      <input type="radio" name="from_option" value="manual" onclick="toggleFromFields();"> Enter manually
    </label>
    <div id="fromDropdown">
      <select name="from_base">
        <?php foreach ($bases as $base): ?>
          <option value="<?php echo htmlspecialchars($base); ?>"><?php echo htmlspecialchars($base); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="fromManual" style="display: none;">
      <input type="text" name="from_other" placeholder="Enter base">
    </div>
    
    <!-- To Field -->
    <p><strong>To:</strong></p>
    <label>
      <input type="radio" name="to_option" value="dropdown" checked onclick="toggleToFields();"> Select from list
    </label>
    <label>
      <input type="radio" name="to_option" value="manual" onclick="toggleToFields();"> Enter manually
    </label>
    <div id="toDropdown">
      <select name="to_base">
        <?php foreach ($bases as $base): ?>
          <option value="<?php echo htmlspecialchars($base); ?>"><?php echo htmlspecialchars($base); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="toManual" style="display: none;">
      <input type="text" name="to_other" placeholder="Enter base">
    </div>
    
    <!-- Other Basic Fields -->
    <label for="capacity">Capacity:</label>
    <select name="capacity" id="capacity">
      <option value="pilot">Pilot</option>
      <option value="crew">Crew</option>
    </select>
    
    <label for="pilot_type">Pilot Type (if applicable):</label>
    <select name="pilot_type" id="pilot_type">
      <option value="single">Single Pilot</option>
      <option value="multi">Multi Pilot</option>
    </select>
    
    <label for="crew_names">Crew Names (comma separated):</label>
    <input type="text" name="crew_names" id="crew_names" placeholder="Enter names">
    
    <label for="rotors_start">Rotors Start Time:</label>
    <input type="time" name="rotors_start" id="rotors_start" required>
    
    <label for="rotors_stop">Rotors Stop Time:</label>
    <input type="time" name="rotors_stop" id="rotors_stop" required>
  </fieldset>
  
  <fieldset>
    <legend>Flight Role Breakdown</legend>
    <p>Detail the breakdown of your flight time by role (in minutes):</p>
    <table id="breakdownTable" style="width:100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Role</th>
          <th>Duration (minutes)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <select name="role[]">
              <?php if ($_SESSION['default_role'] === 'crew'): ?>
                <option value="Crew" selected>Crew</option>
                <option value="Day P1">Day P1</option>
                <option value="Day P2">Day P2</option>
                <option value="Day Pilot under training">Day Pilot under training</option>
                <option value="Night P1">Night P1</option>
                <option value="Night P2">Night P2</option>
                <option value="Night Pilot under training">Night Pilot under training</option>
                <option value="Simulator">Simulator</option>
              <?php else: ?>
                <option value="Day P1" selected>Day P1</option>
                <option value="Day P2">Day P2</option>
                <option value="Day Pilot under training">Day Pilot under training</option>
                <option value="Night P1">Night P1</option>
                <option value="Night P2">Night P2</option>
                <option value="Night Pilot under training">Night Pilot under training</option>
                <option value="Simulator">Simulator</option>
                <option value="Crew">Crew</option>
              <?php endif; ?>
            </select>
          </td>
          <td>
            <input type="number" name="duration[]" min="0" placeholder="Minutes" required>
          </td>
          <td>
            <button type="button" onclick="removeRow(this);">Remove</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button type="button" onclick="addRow();">Add Role</button>
  </fieldset>
  
  <input type="submit" value="Add Flight Record">
</form>

<script>
// Toggle Aircraft fields.
function toggleAircraftFields() {
    var option = document.querySelector('input[name="aircraft_option"]:checked').value;
    if (option === 'master') {
        document.getElementById('aircraftMaster').style.display = 'block';
        document.getElementById('aircraftManual').style.display = 'none';
    } else {
        document.getElementById('aircraftMaster').style.display = 'none';
        document.getElementById('aircraftManual').style.display = 'block';
    }
}

// Toggle "From" fields.
function toggleFromFields() {
    var option = document.querySelector('input[name="from_option"]:checked').value;
    if (option === 'dropdown') {
        document.getElementById('fromDropdown').style.display = 'block';
        document.getElementById('fromManual').style.display = 'none';
    } else {
        document.getElementById('fromDropdown').style.display = 'none';
        document.getElementById('fromManual').style.display = 'block';
    }
}

// Toggle "To" fields.
function toggleToFields() {
    var option = document.querySelector('input[name="to_option"]:checked').value;
    if (option === 'dropdown') {
        document.getElementById('toDropdown').style.display = 'block';
        document.getElementById('toManual').style.display = 'none';
    } else {
        document.getElementById('toDropdown').style.display = 'none';
        document.getElementById('toManual').style.display = 'block';
    }
}

// Functions for dynamic flight breakdown rows.
function addRow() {
    var tbody = document.getElementById('breakdownTable').getElementsByTagName('tbody')[0];
    var newRow = tbody.insertRow();
    
    // Role cell.
    var cell1 = newRow.insertCell(0);
    var selectHTML = '<select name="role[]">';
    <?php if ($_SESSION['default_role'] === 'crew'): ?>
      selectHTML += '<option value="Crew" selected>Crew</option>';
      selectHTML += '<option value="Day P1">Day P1</option>';
      selectHTML += '<option value="Day P2">Day P2</option>';
      selectHTML += '<option value="Day Pilot under training">Day Pilot under training</option>';
      selectHTML += '<option value="Night P1">Night P1</option>';
      selectHTML += '<option value="Night P2">Night P2</option>';
      selectHTML += '<option value="Night Pilot under training">Night Pilot under training</option>';
      selectHTML += '<option value="Simulator">Simulator</option>';
    <?php else: ?>
      selectHTML += '<option value="Day P1" selected>Day P1</option>';
      selectHTML += '<option value="Day P2">Day P2</option>';
      selectHTML += '<option value="Day Pilot under training">Day Pilot under training</option>';
      selectHTML += '<option value="Night P1">Night P1</option>';
      selectHTML += '<option value="Night P2">Night P2</option>';
      selectHTML += '<option value="Night Pilot under training">Night Pilot under training</option>';
      selectHTML += '<option value="Simulator">Simulator</option>';
      selectHTML += '<option value="Crew">Crew</option>';
    <?php endif; ?>
    selectHTML += '</select>';
    cell1.innerHTML = selectHTML;
    
    // Duration cell.
    var cell2 = newRow.insertCell(1);
    cell2.innerHTML = '<input type="number" name="duration[]" min="0" placeholder="Minutes" required>';
    
    // Action cell.
    var cell3 = newRow.insertCell(2);
    cell3.innerHTML = '<button type="button" onclick="removeRow(this);">Remove</button>';
}

function removeRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
}
</script>

<?php include('footer.php'); ?>
