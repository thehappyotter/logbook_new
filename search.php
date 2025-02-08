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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL");
$stmt->execute($params);
$totalResults = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT f.*, a.registration FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL ORDER BY $sort LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
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
        echo "<thead><tr><th>Date</th><th>Aircraft</th><th>From</th><th>To</th><th>Duration</th><th>Actions</th></tr></thead><tbody>";
        foreach ($flights as $flight) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($flight['flight_date']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['registration']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['flight_from']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['flight_to']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['flight_duration']) . "</td>";
            echo "<td>";
            echo "<a href='flight_edit.php?id=" . $flight['id'] . "'>Edit</a> | ";
            echo "<form method='post' action='flight_delete.php' style='display:inline;' onsubmit='return confirm(\"Are you sure?\");'>";
            echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token) . "'>";
            echo "<input type='hidden' name='id' value='" . htmlspecialchars($flight['id']) . "'>";
            echo "<input type='submit' value='Delete'>";
            echo "</form>";
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
