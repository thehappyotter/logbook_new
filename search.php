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
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Search Flight Records</h2>
  </div>
  <div class="card-body">
    <form method="get" action="search.php" class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="search" class="form-label">Search (From, To, Aircraft):</label>
          <input type="text" class="form-control" name="search" id="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        <div class="col-md-4">
          <label for="start_date" class="form-label">Start Date:</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="col-md-4">
          <label for="end_date" class="form-label">End Date:</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Search</button>
      </div>
    </form>
    <?php
    if ($flights) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr>
                <th>Date</th>
                <th>Aircraft</th>
                <th>From</th>
                <th>To</th>
                <th>Duration</th>
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
            echo "<td>";
            echo "<a class='btn btn-sm btn-secondary' href='flight_view.php?id=" . htmlspecialchars($flight['id']) . "'>View</a> ";
            echo "<a class='btn btn-sm btn-warning' href='flight_edit.php?id=" . htmlspecialchars($flight['id']) . "'>Edit</a> ";
            echo "<form method='post' action='flight_delete.php' class='d-inline' onsubmit='return confirm(\"Are you sure?\");'>";
            echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token) . "'>";
            echo "<input type='hidden' name='id' value='" . htmlspecialchars($flight['id']) . "'>";
            echo "<button type='submit' class='btn btn-sm btn-danger'>Delete</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
      
        $totalPages = ceil($totalResults / $perPage);
        echo "<nav><ul class='pagination'>";
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $page) {
                echo "<li class='page-item active'><span class='page-link'>$i</span></li>";
            } else {
                $queryParams = $_GET;
                $queryParams['page'] = $i;
                $queryString = http_build_query($queryParams);
                echo "<li class='page-item'><a class='page-link' href='search.php?$queryString'>$i</a></li>";
            }
        }
        echo "</ul></nav>";
    } else {
        echo "<p>No flight records found.</p>";
    }
    ?>
  </div>
</div>
<?php include('footer.php'); ?>
