<?php
// flight_entry.php
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

$error = [];
$success = [];
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid request. Please try again.";
    } else {
        $flight_date = $_POST['flight_date'];

        // Aircraft field.
        $selectedAircraft = $_POST['aircraft_select'];
        if ($selectedAircraft !== "other") {
            $aircraft_id = $selectedAircraft;
            $custom_aircraft_details = "";
        } else {
            $aircraft_id = null;
            $aircraft_type = trim($_POST['aircraft_type']);
            $aircraft_registration = trim($_POST['aircraft_registration']);
            $custom_aircraft_details = "Type: " . $aircraft_type . ", Registration: " . $aircraft_registration;
        }

        // "From" field.
        $selectedFrom = $_POST['from_select'];
        if ($selectedFrom !== "other") {
            $flight_from = $selectedFrom;
        } else {
            $flight_from = trim($_POST['from_other']);
        }

        // "To" field.
        $selectedTo = $_POST['to_select'];
        if ($selectedTo !== "other") {
            $flight_to = $selectedTo;
        } else {
            $flight_to = trim($_POST['to_other']);
        }

        // Other fields.
        $capacity = $_POST['capacity'];
        $pilot_type = $_POST['pilot_type'];
        $crew_names = trim($_POST['crew_names']);
        $rotors_start = $_POST['rotors_start'];
        $rotors_stop  = $_POST['rotors_stop'];

        try {
            $start = new DateTime($rotors_start);
            $stop  = new DateTime($rotors_stop);
            $interval = $start->diff($stop);
            $flight_duration = $interval->format('%H:%I:%S');
        } catch (Exception $e) {
            $error[] = "Invalid time format for rotors start/stop.";
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO flights (user_id, flight_date, aircraft_id, custom_aircraft_details, flight_from, flight_to, capacity, pilot_type, crew_names, rotors_start, rotors_stop, flight_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $flight_date,
                $aircraft_id,
                $custom_aircraft_details,
                $flight_from,
                $flight_to,
                $capacity,
                $pilot_type,
                $crew_names,
                $rotors_start,
                $rotors_stop,
                $flight_duration
            ]);
            if ($result) {
                $flight_id = $pdo->lastInsertId();
                logAudit($pdo, $_SESSION['user_id'], $flight_id, 'create', 'Flight record created with extended details.');
                if (isset($_POST['role']) && isset($_POST['duration'])) {
                    $roles = $_POST['role'];
                    $durations = $_POST['duration'];
                    $count = count($roles);
                    for ($i = 0; $i < $count; $i++) {
                        $role = trim($roles[$i]);
                        $duration = intval($durations[$i]);
                        if ($role !== "" && $duration > 0) {
                            $stmtBreakdown = $pdo->prepare("INSERT INTO flight_breakdown (flight_id, role, duration_minutes) VALUES (?, ?, ?)");
                            $stmtBreakdown->execute([$flight_id, $role, $duration]);
                        }
                    }
                }
                $success[] = "Flight record added successfully.";
            } else {
                $error[] = "Failed to add flight record.";
            }
        }
    }
}

// Fetch master list of aircraft.
$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

// Fetch bases from the database (order by base_name).
$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

// Fetch current user's default base.
$stmtUser = $pdo->prepare("SELECT default_base FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user = $stmtUser->fetch();
$default_base = $user['default_base'] ?? '';
if (!$default_base && count($bases) > 0) {
    $default_base = $bases[0]['id'];
}

include('header.php');
?>
<div class="flight-entry-container">
    <h2>Enter New Flight Record</h2>
    <?php 
    foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
    foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
    ?>
    <form method="post" action="flight_entry.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      
      <fieldset>
        <legend>Basic Flight Details</legend>
        
        <div class="form-group">
            <label for="flight_date">Date:</label>
            <input type="date" name="flight_date" id="flight_date" required>
        </div>
        
        <div class="form-group">
            <label for="aircraft_select">Aircraft:</label>
            <select name="aircraft_select" id="aircraft_select" onchange="toggleField(this, 'aircraftManualDiv');">
              <option value="">Select Aircraft</option>
              <?php foreach ($aircraft_list as $ac): ?>
                <option value="<?php echo $ac['id']; ?>">
                  <?php echo htmlspecialchars($ac['registration']); ?> - <?php echo htmlspecialchars($ac['type']); ?>
                </option>
              <?php endforeach; ?>
              <option value="other">Other (Enter manually)</option>
            </select>
        </div>
        <div class="form-group" id="aircraftManualDiv" style="display:none;">
            <label for="aircraft_type">Aircraft Type:</label>
            <input type="text" name="aircraft_type" id="aircraft_type">
            <label for="aircraft_registration">Aircraft Registration:</label>
            <input type="text" name="aircraft_registration" id="aircraft_registration">
        </div>
        
        <div class="form-group">
            <label for="from_select">From:</label>
            <select name="from_select" id="from_select" onchange="toggleField(this, 'fromManualDiv');">
              <?php foreach ($bases as $base): ?>
                <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $default_base) echo "selected"; ?>>
                  <?php echo htmlspecialchars($base['base_name']); ?>
                </option>
              <?php endforeach; ?>
              <option value="other">Other (Enter location)</option>
            </select>
        </div>
        <div class="form-group" id="fromManualDiv" style="display:none;">
            <input type="text" name="from_other" placeholder="Enter location">
        </div>
        
        <div class="form-group">
            <label for="to_select">To:</label>
            <select name="to_select" id="to_select" onchange="toggleField(this, 'toManualDiv');">
              <?php foreach ($bases as $base): ?>
                <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $default_base) echo "selected"; ?>>
                  <?php echo htmlspecialchars($base['base_name']); ?>
                </option>
              <?php endforeach; ?>
              <option value="other">Other (Enter location)</option>
            </select>
        </div>
        <div class="form-group" id="toManualDiv" style="display:none;">
            <input type="text" name="to_other" placeholder="Enter location">
        </div>
        
        <div class="form-group">
            <label for="capacity">Capacity:</label>
            <select name="capacity" id="capacity">
              <option value="pilot">Pilot</option>
              <option value="crew">Crew</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="pilot_type">Pilot Type:</label>
            <select name="pilot_type" id="pilot_type">
              <option value="single">Single Pilot</option>
              <option value="multi">Multi Pilot</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="crew_names">Crew Names (comma separated):</label>
            <input type="text" name="crew_names" id="crew_names" placeholder="Enter names">
        </div>
        
        <div class="form-group">
            <label for="rotors_start">Rotors Start Time:</label>
            <input type="time" name="rotors_start" id="rotors_start" required oninput="updateDefaultDuration();">
        </div>
        
        <div class="form-group">
            <label for="rotors_stop">Rotors Stop Time:</label>
            <input type="time" name="rotors_stop" id="rotors_stop" required oninput="updateDefaultDuration();">
        </div>
      </fieldset>
      
      <fieldset>
        <legend>Flight Role Breakdown</legend>
        <p>Detail the breakdown of your flight time by role (in minutes):</p>
        <table id="breakdownTable" class="breakdown-table">
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
                <input type="number" name="duration[]" id="defaultDuration" min="0" placeholder="Minutes" required>
              </td>
              <td>
                <button type="button" onclick="removeRow(this);">Remove</button>
              </td>
            </tr>
          </tbody>
        </table>
        <button type="button" onclick="addRow();">Add Role</button>
      </fieldset>
      
      <div class="form-group">
          <input type="submit" value="Add Flight Record">
      </div>
    </form>
</div>

<script>
// Toggle manual input fields.
function toggleField(selectElem, divId) {
    if (selectElem.value === "other") {
        document.getElementById(divId).style.display = "block";
    } else {
        document.getElementById(divId).style.display = "none";
    }
}

// Auto-update the default duration.
function updateDefaultDuration() {
    var startTime = document.getElementById('rotors_start').value;
    var stopTime = document.getElementById('rotors_stop').value;
    if (startTime && stopTime) {
        var start = new Date("1970-01-01T" + startTime + "Z");
        var stop = new Date("1970-01-01T" + stopTime + "Z");
        var diff = (stop - start) / 60000;
        if (diff < 0) { diff += 1440; }
        document.getElementById('defaultDuration').value = diff;
    }
}

// Dynamic flight breakdown row functions.
function addRow() {
    var tbody = document.getElementById("breakdownTable").getElementsByTagName("tbody")[0];
    var newRow = tbody.insertRow();
    var cell1 = newRow.insertCell(0);
    var cell2 = newRow.insertCell(1);
    var cell3 = newRow.insertCell(2);
    
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
    cell2.innerHTML = '<input type="number" name="duration[]" min="0" placeholder="Minutes" required>';
    cell3.innerHTML = '<button type="button" onclick="removeRow(this);">Remove</button>';
}

function removeRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
}
</script>

<?php include('footer.php'); ?>
