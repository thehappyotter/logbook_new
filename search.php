<?php
// search.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$searchQuery = "";
$start_date  = "";
$end_date    = "";
$sort = "flight_date DESC";  // Default sort order
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
$stmt = $pdo->prepare("SELECT COUNT(*) FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL");
$stmt->execute($params);
$totalResults = $stmt->fetchColumn();

// Retrieve the flight records with limit and offset.
$stmt = $pdo->prepare("SELECT f.*, a.registration FROM flights f JOIN aircraft a ON f.aircraft_id = a.id WHERE $whereSQL ORDER BY $sort LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$flights = $stmt->fetchAll();

include('header.php');
?>
<h2>Search Flight Records</h2>
<form method="get" action="search.php">
  <label for="search">Search (Flight From, Flight To, or Aircraft Registration):</label>
  <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
  
  <label for="start_date">Start Date:</label>
  <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
  
  <label for="end_date">End Date:</label>
  <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
  
  <input type="submit" value="Search">
</form>
<?php
if ($flights) {
    echo "<table>";
    echo "<tr><th>Date</th><th>Aircraft</th><th>From</th><th>To</th><th>Duration</th><th>Actions</th></tr>";
    foreach ($flights as $flight) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($flight['flight_date']) . "</td>";
        echo "<td>" . htmlspecialchars($flight['registration']) . "</td>";
        echo "<td>" . htmlspecialchars($flight['flight_from']) . "</td>";
        echo "<td>" . htmlspecialchars($flight['flight_to']) . "</td>";
        echo "<td>" . htmlspecialchars($flight['flight_duration']) . "</td>";
        echo "<td>";
        echo "<a href='flight_edit.php?id=" . $flight['id'] . "'>Edit</a> | ";
        echo "<a href='flight_delete.php?id=" . $flight['id'] . "' onclick='return confirm(\"Are you sure?\");'>Delete</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Pagination links.
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
include('footer.php');
?>
