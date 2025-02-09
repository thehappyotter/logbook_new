<?php
// flight_entry.php (Updated with Bootstrap UI enhancements)
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Set default date.
$default_date = date('Y-m-d');

// Retrieve the user's most recent flight to set the default aircraft.
$stmtLast = $pdo->prepare("SELECT aircraft_id FROM flights WHERE user_id = ? ORDER BY flight_date DESC, id DESC LIMIT 1");
$stmtLast->execute([$_SESSION['user_id']]);
$lastFlight = $stmtLast->fetch();
$lastAircraftId = $lastFlight ? $lastFlight['aircraft_id'] : "";

// Fetch master list of aircraft.
$stmt = $pdo->query("SELECT * FROM aircraft ORDER BY registration ASC");
$aircraft_list = $stmt->fetchAll();

// Fetch bases.
$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

// Re-query user's default_base and default_role.
$stmtUser = $pdo->prepare("SELECT default_base, default_role FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userData = $stmtUser->fetch();
$default_base = $userData['default_base'] ?? '';
$default_role = $userData['default_role'] ?? 'pilot';
if (!$default_base && count($bases) > 0) {
    $default_base = $bases[0]['id'];
}

// Determine default role for Flight Role Breakdown.
$defaultRowRole = ($default_role === 'crew') ? "Crew" : "Day P1";

// Build role options.
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
        // (Your flight processing logic goes here.)
        // If the submission is successful, you might add a success message.
        // Otherwise, collect error messages.
    }
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Enter New Flight Record</h2>
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
    <form method="post" action="flight_entry.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      
      <!-- Flight Details Section -->
      <fieldset class="mb-4">
        <legend class="h5">Flight Details</legend>
        <div class="mb-3 row">
          <label for="flight_date" class="col-sm-2 col-form-label">Date:</label>
          <div class="col-sm-4">
            <input type="date" class="form-control" name="flight_date" id="flight_date" value="<?php echo $default_date; ?>" required>
          </div>
        </div>
      </fieldset>
      
      <!-- Aircraft Section -->
      <fieldset class="mb-4">
        <legend class="h5">Aircraft</legend>
        <div class="mb-3 row">
          <label for="aircraft_select" class="col-sm-2 col-form-label">Select Aircraft:</label>
          <div class="col-sm-4">
            <select class="form-select" name="aircraft_select" id="aircraft_select">
              <option value="">Select Aircraft</option>
              <?php foreach ($aircraft_list as $ac): ?>
                <option value="<?php echo $ac['id']; ?>" <?php echo ($ac['id'] == $lastAircraftId) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ac['registration']); ?> - <?php echo htmlspecialchars($ac['type']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="aircraft_other_checkbox" name="aircraft_other_checkbox">
              <label class="form-check-label" for="aircraft_other_checkbox">Other</label>
            </div>
          </div>
        </div>
        <div class="mb-3" id="aircraft_other_div" style="display:none;">
          <div class="row">
            <div class="col-sm-6">
              <label for="aircraft_type" class="form-label">Aircraft Type:</label>
              <input type="text" class="form-control" name="aircraft_type" id="aircraft_type">
            </div>
            <div class="col-sm-6">
              <label for="aircraft_registration" class="form-label">Aircraft Registration:</label>
              <input type="text" class="form-control" name="aircraft_registration" id="aircraft_registration">
            </div>
          </div>
        </div>
      </fieldset>
      
      <!-- Flight Route Section -->
      <fieldset class="mb-4">
        <legend class="h5">Flight Route</legend>
        <div class="mb-3 row">
          <label for="from_select" class="col-sm-2 col-form-label">From:</label>
          <div class="col-sm-4">
            <select class="form-select" name="from_select" id="from_select">
              <?php foreach ($bases as $base): ?>
                <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if($base['id'] == $default_base) echo "selected"; ?>>
                  <?php echo htmlspecialchars($base['base_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="from_other_checkbox" name="from_other_checkbox">
              <label class="form-check-label" for="from_other_checkbox">Other</label>
            </div>
          </div>
        </div>
        <div class="mb-3" id="from_other_div" style="display:none;">
          <input type="text" class="form-control" name="from_other" id="from_other" placeholder="Enter location">
        </div>
        
        <div class="mb-3 row">
          <label for="to_select" class="col-sm-2 col-form-label">To:</label>
          <div class="col-sm-4">
            <select class="form-select" name="to_select" id="to_select">
              <?php foreach ($bases as $base): ?>
                <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if($base['id'] == $default_base) echo "selected"; ?>>
                  <?php echo htmlspecialchars($base['base_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="to_other_checkbox" name="to_other_checkbox">
              <label class="form-check-label" for="to_other_checkbox">Other</label>
            </div>
          </div>
        </div>
        <div class="mb-3" id="to_other_div" style="display:none;">
          <input type="text" class="form-control" name="to_other" id="to_other" placeholder="Enter location">
        </div>
      </fieldset>
      
      <!-- Additional Flight Details Section -->
      <fieldset class="mb-4">
        <legend class="h5">Additional Flight Details</legend>
        <div class="mb-3 row">
          <label for="capacity" class="col-sm-2 col-form-label">Capacity:</label>
          <div class="col-sm-4">
            <select class="form-select" name="capacity" id="capacity">
              <option value="pilot" <?php echo ($default_role==='pilot') ? 'selected' : ''; ?>>Pilot</option>
              <option value="crew" <?php echo ($default_role==='crew') ? 'selected' : ''; ?>>Crew</option>
            </select>
          </div>
          <label for="pilot_type" class="col-sm-2 col-form-label">Pilot Type:</label>
          <div class="col-sm-4">
            <select class="form-select" name="pilot_type" id="pilot_type">
              <option value="single">Single Pilot</option>
              <option value="multi">Multi Pilot</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label for="crew_names" class="form-label">Crew Names:</label>
          <input type="text" class="form-control" name="crew_names" id="crew_names" placeholder="Enter crew names (comma separated)">
        </div>
      </fieldset>
      
      <!-- Flight Timing Section -->
      <fieldset class="mb-4">
        <legend class="h5">Flight Timing</legend>
        <div class="mb-3 row">
          <label for="rotors_start" class="col-sm-2 col-form-label">Rotors Start:</label>
          <div class="col-sm-4">
            <input type="time" class="form-control" name="rotors_start" id="rotors_start" required oninput="recalcBreakdown();">
          </div>
          <label for="rotors_stop" class="col-sm-2 col-form-label">Rotors Stop:</label>
          <div class="col-sm-4">
            <input type="time" class="form-control" name="rotors_stop" id="rotors_stop" required oninput="recalcBreakdown();">
          </div>
        </div>
      </fieldset>
      
      <!-- Flight Role Breakdown Section -->
      <fieldset class="mb-4">
        <legend class="h5">Flight Role Breakdown</legend>
        <p class="small text-muted">Detail the breakdown of your flight time by role (in minutes). The first row is auto‑calculated based on your rotors times.</p>
        <table class="table table-bordered" id="breakdownTable">
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
                <select class="form-select" name="role[]" id="defaultRoleSelect">
                  <?php echo $roleOptions; ?>
                </select>
              </td>
              <td>
                <input type="number" class="form-control" name="duration[]" id="defaultDuration" min="0" placeholder="Minutes" readonly>
              </td>
              <td></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="addRow();">Add Role</button>
      </fieldset>
      
      <!-- NVG Section (Optional) -->
      <fieldset class="mb-4">
        <legend class="h5">NVG (Night Vision Goggles) <small class="text-muted">(Optional)</small></legend>
        <div class="mb-3 row">
          <label for="nvg_time" class="col-sm-2 col-form-label">NVG Time:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="nvg_time" id="nvg_time" min="0" value="0">
          </div>
          <label for="nvg_takeoffs" class="col-sm-2 col-form-label">NVG Takeoffs:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="nvg_takeoffs" id="nvg_takeoffs" min="0" value="0">
          </div>
        </div>
        <div class="mb-3 row">
          <label for="nvg_landings" class="col-sm-2 col-form-label">NVG Landings:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="nvg_landings" id="nvg_landings" min="0" value="0">
          </div>
        </div>
      </fieldset>
      
      <!-- Instrument Flight Section (Optional) -->
      <fieldset class="mb-4">
        <legend class="h5">Instrument Flight <small class="text-muted">(Optional)</small></legend>
        <div class="mb-3 row">
          <label for="sim_if" class="col-sm-2 col-form-label">Sim IF:</label>
          <div class="col-sm-4">
            <input type="time" class="form-control" name="sim_if" id="sim_if">
          </div>
          <label for="act_if" class="col-sm-2 col-form-label">Act IF:</label>
          <div class="col-sm-4">
            <input type="time" class="form-control" name="act_if" id="act_if">
          </div>
        </div>
        <div class="mb-3 row">
          <label for="ils_approaches" class="col-sm-2 col-form-label">ILS Approaches:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="ils_approaches" id="ils_approaches" min="0" value="0">
          </div>
          <label for="rnp" class="col-sm-2 col-form-label">RNP:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="rnp" id="rnp" min="0" value="0">
          </div>
        </div>
        <div class="mb-3 row">
          <label for="npa" class="col-sm-2 col-form-label">NPA:</label>
          <div class="col-sm-4">
            <input type="number" class="form-control" name="npa" id="npa" min="0" value="0">
          </div>
        </div>
      </fieldset>
      
      <div class="mb-3">
        <button type="submit" class="btn btn-primary">Add Flight Record</button>
      </div>
    </form>
  </div>
</div>

<!-- jQuery (for dynamic behavior) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
// Toggle visibility for "Other" options.
$(document).ready(function(){
  $("#aircraft_other_checkbox").change(function(){
    $("#aircraft_other_div").toggle(this.checked);
  });
  $("#from_other_checkbox").change(function(){
    $("#from_other_div").toggle(this.checked);
  });
  $("#to_other_checkbox").change(function(){
    $("#to_other_div").toggle(this.checked);
  });
});

// Function to recalc the default flight role breakdown row.
function recalcBreakdown(){
    const rotorsStart = document.getElementById('rotors_start').value;
    const rotorsStop = document.getElementById('rotors_stop').value;
    if(!rotorsStart || !rotorsStop) return;
    
    let start = new Date("1970-01-01T" + rotorsStart + "Z");
    let stop = new Date("1970-01-01T" + rotorsStop + "Z");
    let totalMinutes = (stop - start) / 60000;
    if(totalMinutes < 0){ totalMinutes += 1440; }
    
    const $tbody = $("#breakdownTable tbody");
    let additionalMinutes = 0;
    
    $tbody.find("tr").each(function(index){
        if(index > 0){
            const val = parseInt($(this).find("input[name='duration[]']").val()) || 0;
            additionalMinutes += val;
        }
    });
    
    let defaultMinutes = totalMinutes - additionalMinutes;
    if(defaultMinutes < 0) defaultMinutes = 0;
    
    $("#defaultDuration").val(defaultMinutes);
}

// Recalculate when any additional breakdown row changes.
$(document).on("change", "input[name='duration[]']", function(){
    if($(this).closest("tr").index() > 0){
        recalcBreakdown();
    }
});

// Add a new breakdown row.
function addRow(){
    const tbody = $("#breakdownTable tbody");
    let newRow = `<tr>
            <td>
              <select class="form-select" name="role[]">
                <?php echo json_encode($roleOptions); ?>
              </select>
            </td>
            <td>
              <input type="number" class="form-control" name="duration[]" min="0" placeholder="Minutes">
            </td>
            <td>
              <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this);">Remove</button>
            </td>
          </tr>`;
    tbody.append(newRow);
    recalcBreakdown();
}

// Remove a breakdown row.
function removeRow(btn){
    $(btn).closest('tr').remove();
    recalcBreakdown();
}
</script>
<?php include('footer.php'); ?>
