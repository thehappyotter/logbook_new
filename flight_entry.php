<?php
// flight_entry.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch master list of aircraft.
$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

// Fetch bases from the database (ordered by base_name).
$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

// Re-query the current user's default_base and default_role from the database.
// This ensures that any changes (e.g., via My Account) are applied.
$stmtUser = $pdo->prepare("SELECT default_base, default_role FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userData = $stmtUser->fetch();
$default_base = $userData['default_base'] ?? '';
$default_role = $userData['default_role'] ?? 'pilot';

// If no default base is set and there are bases available, use the first one.
if (!$default_base && count($bases) > 0) {
    $default_base = $bases[0]['id'];
}

// Determine the default role text for the breakdown default row.
// If the user's default_role is crew, default to "Crew"; otherwise default to "Day P1".
$defaultRowRole = ($default_role === 'crew') ? "Crew" : "Day P1";

// Build the complete set of role options for the dropdown.
// The option whose value matches $defaultRowRole will have the "selected" attribute.
$roleOptions = '
<option value="Day P1"' . ($defaultRowRole == "Day P1" ? ' selected' : '') . '>Day P1</option>
<option value="Day P2"' . ($defaultRowRole == "Day P2" ? ' selected' : '') . '>Day P2</option>
<option value="Day Pilot under training"' . ($defaultRowRole == "Day Pilot under training" ? ' selected' : '') . '>Day Pilot under training</option>
<option value="Night P1"' . ($defaultRowRole == "Night P1" ? ' selected' : '') . '>Night P1</option>
<option value="Night P2"' . ($defaultRowRole == "Night P2" ? ' selected' : '') . '>Night P2</option>
<option value="Night Pilot under training"' . ($defaultRowRole == "Night Pilot under training" ? ' selected' : '') . '>Night Pilot under training</option>
<option value="Simulator"' . ($defaultRowRole == "Simulator" ? ' selected' : '') . '>Simulator</option>
<option value="Crew"' . ($defaultRowRole == "Crew" ? ' selected' : '') . '>Crew</option>';

$error = [];
$success = [];
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid request. Please try again.";
    } else {
        $flight_date = $_POST['flight_date'];

        // Aircraft field.
        if (isset($_POST['aircraft_other_checkbox'])) {
            $aircraft_id = null;
            $aircraft_type = trim($_POST['aircraft_type']);
            $aircraft_registration = trim($_POST['aircraft_registration']);
            $custom_aircraft_details = "Type: " . $aircraft_type . ", Registration: " . $aircraft_registration;
        } else {
            $selectedAircraft = $_POST['aircraft_select'];
            $aircraft_id = $selectedAircraft;
            $custom_aircraft_details = "";
        }

        // "From" field.
        $flight_from = $_POST['from_select'];
        if (isset($_POST['from_other_checkbox'])) {
            $flight_from = trim($_POST['from_other']);
        }

        // "To" field.
        $flight_to = $_POST['to_select'];
        if (isset($_POST['to_other_checkbox'])) {
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
?>
<?php include('header.php'); ?>
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
      
      <!-- Aircraft Section -->
      <div class="form-group">
          <label for="aircraft_select">Aircraft:</label>
          <select name="aircraft_select" id="aircraft_select">
            <option value="">Select Aircraft</option>
            <?php foreach ($aircraft_list as $ac): ?>
              <option value="<?php echo $ac['id']; ?>">
                <?php echo htmlspecialchars($ac['registration']); ?> - <?php echo htmlspecialchars($ac['type']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label>
            <input type="checkbox" id="aircraft_other_checkbox" name="aircraft_other_checkbox">
            Other
          </label>
      </div>
      <div class="form-group" id="aircraft_other_div" style="display:none;">
          <label for="aircraft_type">Aircraft Type:</label>
          <input type="text" name="aircraft_type" id="aircraft_type">
          <label for="aircraft_registration">Aircraft Registration:</label>
          <input type="text" name="aircraft_registration" id="aircraft_registration">
      </div>
      
      <!-- From Section -->
      <div class="form-group">
          <label for="from_select">From:</label>
          <select name="from_select" id="from_select">
            <?php foreach ($bases as $base): ?>
              <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $default_base) echo "selected"; ?>>
                <?php echo htmlspecialchars($base['base_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label>
            <input type="checkbox" id="from_other_checkbox" name="from_other_checkbox">
            Other
          </label>
      </div>
      <div class="form-group" id="from_other_div" style="display:none;">
          <input type="text" name="from_other" id="from_other" placeholder="Enter location">
      </div>
      
      <!-- To Section -->
      <div class="form-group">
          <label for="to_select">To:</label>
          <select name="to_select" id="to_select">
            <?php foreach ($bases as $base): ?>
              <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $default_base) echo "selected"; ?>>
                <?php echo htmlspecialchars($base['base_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label>
            <input type="checkbox" id="to_other_checkbox" name="to_other_checkbox">
            Other
          </label>
      </div>
      <div class="form-group" id="to_other_div" style="display:none;">
          <input type="text" name="to_other" id="to_other" placeholder="Enter location">
      </div>
      
      <!-- Other Basic Fields -->
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
          <input type="time" name="rotors_start" id="rotors_start" required oninput="recalcBreakdown();">
      </div>
      
      <div class="form-group">
          <label for="rotors_stop">Rotors Stop Time:</label>
          <input type="time" name="rotors_stop" id="rotors_stop" required oninput="recalcBreakdown();">
      </div>
    </fieldset>
    
    <fieldset>
      <legend>Flight Role Breakdown</legend>
      <p>Detail the breakdown of your flight time by role (in minutes). The first row is auto‑calculated based on your rotors times. You may change the role if needed.</p>
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
              <select name="role[]" id="defaultRoleSelect">
                <?php echo $roleOptions; ?>
              </select>
            </td>
            <td>
              <input type="number" name="duration[]" id="defaultDuration" min="0" placeholder="Minutes" readonly>
            </td>
            <td>&nbsp;</td>
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

<!-- Include jQuery from Google's CDN without integrity attribute -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
  $("#aircraft_other_checkbox").change(function() {
    $("#aircraft_other_div").toggle(this.checked);
  });
  
  $("#from_other_checkbox").change(function() {
    $("#from_other_div").toggle(this.checked);
  });
  
  $("#to_other_checkbox").change(function() {
    $("#to_other_div").toggle(this.checked);
  });
});

// Function to recalculate and update the default breakdown row.
// It calculates the total flight time in minutes based on rotors times,
// subtracts any manually entered durations in additional rows, and updates the default row.
function recalcBreakdown() {
    const rotorsStart = document.getElementById('rotors_start').value;
    const rotorsStop = document.getElementById('rotors_stop').value;
    if (!rotorsStart || !rotorsStop) return;
    
    let start = new Date("1970-01-01T" + rotorsStart + "Z");
    let stop = new Date("1970-01-01T" + rotorsStop + "Z");
    let totalMinutes = (stop - start) / 60000;
    if (totalMinutes < 0) { totalMinutes += 1440; }
    
    const $tbody = $("#breakdownTable tbody");
    let additionalMinutes = 0;
    
    // Sum the durations from all additional rows (rows with index > 0)
    $tbody.find("tr").each(function(index) {
        if (index > 0) {
            const val = parseInt($(this).find("input[name='duration[]']").val()) || 0;
            additionalMinutes += val;
        }
    });
    
    let defaultMinutes = totalMinutes - additionalMinutes;
    if (defaultMinutes < 0) defaultMinutes = 0;
    
    $("#defaultDuration").val(defaultMinutes);
}

// When an additional row's duration changes, recalc the breakdown.
$(document).on("change", "input[name='duration[]']", function() {
    if ($(this).closest("tr").index() > 0) {
        recalcBreakdown();
    }
});

// Function to add an additional breakdown row.
function addRow() {
    const tbody = document.getElementById("breakdownTable").querySelector("tbody");
    const newRow = tbody.insertRow();
    const cell1 = newRow.insertCell(0);
    const cell2 = newRow.insertCell(1);
    const cell3 = newRow.insertCell(2);
    
    let selectHTML = '<select name="role[]">';
    selectHTML += <?php echo json_encode($roleOptions); ?>;
    selectHTML += '</select>';
    cell1.innerHTML = selectHTML;
    cell2.innerHTML = '<input type="number" name="duration[]" min="0" placeholder="Minutes">';
    cell3.innerHTML = '<button type="button" onclick="removeRow(this);">Remove</button>';
    
    recalcBreakdown();
}

// Function to remove a breakdown row.
function removeRow(btn) {
    const row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    recalcBreakdown();
}
</script>
<?php include('footer.php'); ?>
