<?php
// search.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$searchQuery = "";
$start_date  = "";
$end_date    = "";
$sort = "flight_date DESC";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClauses = ["f.user_id = ?"];
$params = [$_SESSION['user_id']];

if (isset($_GET['search']) && trim($_GET['search']) !== "") {
    $searchQuery = trim($_GET['search']);
    $whereClauses[] = "(f.flight_from LIKE ? OR f.flight_to LIKE ? OR a.registration LIKE ?)";
    $likeQuery = "%$searchQuery%";
    $params[] = $likeQuery;
    $params[] = $likeQuery;
    $params[] = $likeQuery;
}

if (isset($_GET['start_date']) && trim($_GET['start_date']) !== "") {
    $start_date = $_GET['start_date'];
    $whereClauses[] = "f.flight_date >= ?";
    $params[] = $start_date;
}

if (isset($_GET['end_date']) && trim($_GET['end_date']) !== "") {
    $end_date = $_GET['end_date'];
    $whereClauses[] = "f.flight_date <= ?";
    $params[] = $end_date;
}

$whereSQL = implode(" AND ", $whereClauses);

// Count total results.
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL");
$stmtCount->execute($params);
$totalResults = $stmtCount->fetchColumn();

// Retrieve flight records with pagination.
$query = "SELECT f.*, a.registration FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL ORDER BY $sort LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($query);

$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex, $param);
    $paramIndex++;
}

$stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
$paramIndex++;
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);

$stmt->execute();
$flights = $stmt->fetchAll();

$csrf_token = getCSRFToken();

include('header.php');
?>
<div class="flight-entry-container">
  <h2>Search Flight Records</h2>
  <form method="get" action="search.php">
    <div class="form-group">
      <label for="search">Search (Flight From, Flight To, or Aircraft Registration):</label>
      <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
    </div>
    <div class="form-group">
      <label for="start_date">Start Date:</label>
      <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
    </div>
    <div class="form-group">
      <label for="end_date">End Date:</label>
      <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
    </div>
    <div class="form-group">
      <input type="submit" value="Search">
    </div>
  </form>
  <?php
  if ($flights) {
      echo "<table>";
      echo "<thead><tr>
              <th>Date</th>
              <th>Aircraft</th>
              <th>From</th>
              <th>To</th>
              <th>Duration</th>
              <th>NVG Time</th>
              <th>NVG Takeoffs</th>
              <th>NVG Landings</th>
              <th>Sim IF</th>
              <th>Act IF</th>
              <th>ILS Approaches</th>
              <th>RNP</th>
              <th>NPA</th>
              <th>Actions</th>
            </tr></thead><tbody>";
      foreach ($flights as $flight) {
          echo "<tr>";
          echo "<td>" . htmlspecialchars($flight['flight_date']) . "</td>";
          if ($flight['aircraft_id'] !== null) {
              $stmt2 = $pdo->prepare("SELECT registration FROM aircraft WHERE id = ?");
              $stmt2->execute([$flight['aircraft_id']]);
              $aircraft = $stmt2->fetch(PDO::FETCH_ASSOC);
              $aircraft_reg = ($aircraft !== false && isset($aircraft['registration'])) ? $aircraft['registration'] : 'Unknown';
              echo "<td>" . htmlspecialchars($aircraft_reg) . "</td>";
          } else {
              echo "<td>" . htmlspecialchars($flight['custom_aircraft_details']) . "</td>";
          }
          $from = $flight['flight_from'];
          if (is_numeric($from)) {
              $stmtFrom = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
              $stmtFrom->execute([$from]);
              $baseData = $stmtFrom->fetch();
              if ($baseData) {
                  $from = $baseData['base_name'];
              }
          }
          echo "<td>" . htmlspecialchars($from) . "</td>";
          $to = $flight['flight_to'];
          if (is_numeric($to)) {
              $stmtTo = $pdo->prepare("SELECT base_name FROM bases WHERE id = ?");
              $stmtTo->execute([$to]);
              $baseData = $stmtTo->fetch();
              if ($baseData) {
                  $to = $baseData['base_name'];
              }
          }
          echo "<td>" . htmlspecialchars($to) . "</td>";
          echo "<td>" . htmlspecialchars($flight['flight_duration']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['nvg_time']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['nvg_takeoffs']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['nvg_landings']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['sim_if']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['act_if']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['ils_approaches']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['rnp']) . "</td>";
          echo "<td>" . htmlspecialchars($flight['npa']) . "</td>";
          echo "<td>";
          echo "<a href='flight_view.php?id=" . $flight['id'] . "'>View Full Flight Data</a> | ";
          echo "<a href='flight_edit.php?id=" . $flight['id'] . "'>Edit</a> | ";
          echo "<a href='flight_delete.php?id=" . $flight['id'] . "' onclick='return confirm(\"Are you sure?\");'>Delete</a>";
          echo "</td>";
          echo "</tr>";
      }
      echo "</tbody></table>";
      
      $totalPages = ceil($totalResults / $perPage);
      for ($i = 1; $i <= $totalPages; $i++) {
          if ($i == $page) {
              echo "<strong>$i</strong> ";
          } else {
              $queryParams = $_GET;
              $queryParams['page'] = $i;
              $queryString = http_build_query($queryParams);
              echo "<a href='search.php?$queryString'>$i</a> ";
          }
      }
  } else {
      echo "<p>No flight records found.</p>";
  }
  ?>
</div>
<?php include('footer.php'); ?>
