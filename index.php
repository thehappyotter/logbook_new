<?php
// index.php
session_start();
require_once('db.php');
require_once('functions.php');

include('header.php');

if (isset($_SESSION['user_id'])) {

    // Process statistics time range selection.
    if (isset($_GET['stats_range'])) {
        $_SESSION['stats_range'] = $_GET['stats_range'];
    }
    $selected_range = $_SESSION['stats_range'] ?? 'all';

    // Build a date filter clause.
    $date_filter = "";
    switch ($selected_range) {
        case 'last7':
            $date_filter = " AND flight_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_filter = " AND MONTH(flight_date) = MONTH(CURDATE()) AND YEAR(flight_date) = YEAR(CURDATE())";
            break;
        case 'year':
            $date_filter = " AND YEAR(flight_date) = YEAR(CURDATE())";
            break;
        default:
            break;
    }

    // Pagination variables.
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // Calculate statistics.
    $stmtStats = $pdo->prepare("
        SELECT 
          COUNT(*) AS total_flights, 
          SEC_TO_TIME(SUM(TIME_TO_SEC(flight_duration))) AS total_flight_time,
          SUM(nvg_time) AS total_nvg_time,
          SUM(nvg_takeoffs) AS total_nvg_takeoffs,
          SUM(nvg_landings) AS total_nvg_landings
        FROM flights 
        WHERE user_id = ? $date_filter
    ");
    $stmtStats->execute([$_SESSION['user_id']]);
    $stats = $stmtStats->fetch();

    $totalNVGTime     = $stats['total_nvg_time']     ? $stats['total_nvg_time']     : 0;
    $totalNVGTakeoffs = $stats['total_nvg_takeoffs'] ? $stats['total_nvg_takeoffs'] : 0;
    $totalNVGLandings = $stats['total_nvg_landings'] ? $stats['total_nvg_landings'] : 0;
?>
<div class="card flight-entry-container mb-4">
  <div class="card-header">
    <h2 class="mb-0">Your Flight Statistics (<?php echo ucfirst($selected_range); ?>)</h2>
  </div>
  <div class="card-body">
    <form method="get" action="index.php" class="mb-3">
      <div class="row g-3 align-items-center">
        <div class="col-auto">
          <label for="stats_range" class="col-form-label"><strong>Show statistics for:</strong></label>
        </div>
        <div class="col-auto">
          <select name="stats_range" id="stats_range" class="form-select">
            <option value="last7" <?php echo ($selected_range=='last7' ? "selected" : ""); ?>>Last 7 days</option>
            <option value="month" <?php echo ($selected_range=='month' ? "selected" : ""); ?>>Calendar Month</option>
            <option value="year" <?php echo ($selected_range=='year' ? "selected" : ""); ?>>Year</option>
            <option value="all" <?php echo ($selected_range=='all' ? "selected" : ""); ?>>All Time</option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </div>
    </form>
    <div>
      <p><strong>Total Flights:</strong> <?php echo htmlspecialchars($stats['total_flights']); ?></p>
      <p><strong>Total Flight Time:</strong> <?php echo htmlspecialchars($stats['total_flight_time'] ?: '00:00:00'); ?></p>
      <p><strong>Total NVG Time:</strong> <?php echo htmlspecialchars($totalNVGTime); ?> minutes</p>
      <p><strong>Total NVG Takeoffs:</strong> <?php echo htmlspecialchars($totalNVGTakeoffs); ?></p>
      <p><strong>Total NVG Landings:</strong> <?php echo htmlspecialchars($totalNVGLandings); ?></p>
    </div>
  </div>
</div>
<?php
    // Retrieve flight records with pagination.
    $stmt = $pdo->prepare("SELECT * FROM flights WHERE user_id = ? ORDER BY flight_date DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $flights = $stmt->fetchAll();

    // Count total records.
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM flights WHERE user_id = ? $date_filter");
    $stmtCount->execute([$_SESSION['user_id']]);
    $totalResults = $stmtCount->fetchColumn();
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Your Flight Log</h2>
  </div>
  <div class="card-body">
    <?php if ($flights): ?>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Date</th>
            <th>Aircraft</th>
            <th>From</th>
            <th>To</th>
            <th>Duration</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($flights as $flight): ?>
            <tr>
              <td><?php echo htmlspecialchars($flight['flight_date']); ?></td>
              <?php
              if ($flight['aircraft_id'] !== null) {
                  $stmt2 = $pdo->prepare("SELECT registration FROM aircraft WHERE id = ?");
                  $stmt2->execute([$flight['aircraft_id']]);
                  $aircraft = $stmt2->fetch(PDO::FETCH_ASSOC);
                  $aircraft_reg = ($aircraft !== false && isset($aircraft['registration'])) ? $aircraft['registration'] : 'Unknown';
                  echo "<td>" . htmlspecialchars($aircraft_reg) . "</td>";
              } else {
                  echo "<td>" . htmlspecialchars($flight['custom_aircraft_details']) . "</td>";
              }
              ?>
              <?php
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
              ?>
              <td><?php echo htmlspecialchars($flight['flight_duration']); ?></td>
              <td>
                <a class="btn btn-sm btn-secondary" href="flight_view.php?id=<?php echo htmlspecialchars($flight['id']); ?>">View</a>
                <a class="btn btn-sm btn-warning" href="flight_edit.php?id=<?php echo htmlspecialchars($flight['id']); ?>">Edit</a>
                <form method="post" action="flight_delete.php" class="d-inline" onsubmit="return confirm('Are you sure?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($flight['id']); ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php
      $totalPages = ceil($totalResults / $perPage);
      ?>
      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
              <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
            <?php else: 
              $queryParams = $_GET;
              $queryParams['page'] = $i;
              $queryString = http_build_query($queryParams);
            ?>
              <li class="page-item"><a class="page-link" href="index.php?<?php echo $queryString; ?>"><?php echo $i; ?></a></li>
            <?php endif; ?>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php else: ?>
      <p>No flight records found. Start by adding a new flight.</p>
    <?php endif; ?>
  </div>
</div>
<?php
} else {
    echo "<div class='card flight-entry-container'><div class='card-body'>";
    echo "<h2>Welcome to the Flight Log</h2>";
    echo "<p>Please <a href='login.php'>login</a> or <a href='register.php'>register</a> to view your flight records and statistics.</p>";
    echo "</div></div>";
}

include('footer.php');
?>
