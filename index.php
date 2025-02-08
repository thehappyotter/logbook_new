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

    echo "<div class='flight-entry-container'>";
    echo "<form method='get' action='index.php' style='margin-bottom:20px;'>";
    echo "<div class='form-group'>";
    echo "<label for='stats_range'><strong>Show statistics for:</strong></label> ";
    echo "<select name='stats_range' id='stats_range'>";
    echo "<option value='last7'" . ($selected_range=='last7' ? " selected" : "") . ">Last 7 days</option>";
    echo "<option value='month'" . ($selected_range=='month' ? " selected" : "") . ">Calendar Month</option>";
    echo "<option value='year'" . ($selected_range=='year' ? " selected" : "") . ">Year</option>";
    echo "<option value='all'" . ($selected_range=='all' ? " selected" : "") . ">All Time</option>";
    echo "</select>";
    echo "</div>";
    echo "<div class='form-group'><input type='submit' value='Update'></div>";
    echo "</form>";

    echo "<div id='statsContainer' style='margin-bottom:20px;'>";
    echo "<div id='statsHeader' style='cursor:pointer; background:#ccc; padding:5px;'>";
    echo "<h3 style='display:inline-block; margin:0;'>Your Flight Statistics (" . ucfirst($selected_range) . ")</h3> ";
    echo "<span id='toggleIcon' style='float:right;'>[-]</span>";
    echo "</div>";
    echo "<div id='statsContent' style='border:1px solid #ccc; padding:15px; background:#eef;'>";
    echo "<p><strong>Total Flights:</strong> " . htmlspecialchars($stats['total_flights']) . "</p>";
    echo "<p><strong>Total Flight Time:</strong> " . htmlspecialchars($stats['total_flight_time'] ?: '00:00:00') . "</p>";
    echo "<p><strong>Total NVG Time:</strong> " . htmlspecialchars($totalNVGTime) . " minutes</p>";
    echo "<p><strong>Total NVG Takeoffs:</strong> " . htmlspecialchars($totalNVGTakeoffs) . "</p>";
    echo "<p><strong>Total NVG Landings:</strong> " . htmlspecialchars($totalNVGLandings) . "</p>";
    echo "</div>";
    echo "</div>";

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

    echo "<h2>Your Flight Log</h2>";
    if ($flights) {
        echo "<table>";
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
                echo "<a href='index.php?$queryString'>$i</a> ";
            }
        }
    } else {
        echo "<p>No flight records found. Start by adding a new flight.</p>";
    }
    
    echo "</main></div>"; // End wrapper and main.
} else {
    echo "<div class='flight-entry-container'>";
    echo "<h2>Welcome to the Flight Log</h2>";
    echo "<p>Please <a href='login.php'>login</a> or <a href='register.php'>register</a> to view your flight records and statistics.</p>";
    echo "</div>";
}

include('footer.php');
?>
<script>
document.addEventListener("DOMContentLoaded", function(){
    var statsContent = document.getElementById('statsContent');
    var toggleIcon = document.getElementById('toggleIcon');
    if (statsContent && toggleIcon) {
        var collapsed = localStorage.getItem('statsCollapsed');
        if (collapsed === 'true') {
            statsContent.style.display = 'none';
            toggleIcon.textContent = '[+]';
        }
        document.getElementById('statsHeader').addEventListener('click', function(){
            if (statsContent.style.display === 'none') {
                statsContent.style.display = 'block';
                toggleIcon.textContent = '[-]';
                localStorage.setItem('statsCollapsed', 'false');
            } else {
                statsContent.style.display = 'none';
                toggleIcon.textContent = '[+]';
                localStorage.setItem('statsCollapsed', 'true');
            }
        });
    }
});
</script>
