<?php
// index.php
include('header.php');
require_once('db.php');
require_once('functions.php');

if (isset($_SESSION['user_id'])) {

    if (isset($_GET['stats_range'])) {
        $_SESSION['stats_range'] = $_GET['stats_range'];
    }
    $selected_range = isset($_SESSION['stats_range']) ? $_SESSION['stats_range'] : 'all';

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
        case 'all':
        default:
            break;
    }

    $stmtStats = $pdo->prepare("
        SELECT 
          COUNT(*) AS total_flights, 
          SEC_TO_TIME(SUM(TIME_TO_SEC(flight_duration))) AS total_flight_time, 
          SUM(night_vision_duration) AS total_nvg_minutes 
        FROM flights 
        WHERE user_id = ? $date_filter
    ");
    $stmtStats->execute([$_SESSION['user_id']]);
    $stats = $stmtStats->fetch();

    $nvg_minutes = $stats['total_nvg_minutes'] ? $stats['total_nvg_minutes'] : 0;
    $nvg_hours = floor($nvg_minutes / 60);
    $nvg_remaining_minutes = $nvg_minutes % 60;

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
    echo "<div class='form-group'>";
    echo "<input type='submit' value='Update'>";
    echo "</div>";
    echo "</form>";

    echo "<div id='statsContainer' style='margin-bottom: 20px;'>";
    echo "<div id='statsHeader' style='cursor: pointer; background: #ccc; padding: 5px;'>";
    echo "<h3 style='display: inline-block; margin: 0;'>Your Flight Statistics (" . ucfirst($selected_range) . ")</h3> ";
    echo "<span id='toggleIcon' style='float: right;'>[-]</span>";
    echo "</div>";
    echo "<div id='statsContent' style='border: 1px solid #ccc; padding: 15px; background: #eef;'>";
    echo "<p><strong>Total Flights:</strong> " . htmlspecialchars($stats['total_flights']) . "</p>";
    echo "<p><strong>Total Flight Time:</strong> " . htmlspecialchars($stats['total_flight_time'] ?: '00:00:00') . "</p>";
    echo "<p><strong>Total NVG Time:</strong> " . $nvg_hours . " hours " . $nvg_remaining_minutes . " minutes</p>";
    echo "</div>";
    echo "</div>";

    $stmt = $pdo->prepare("SELECT * FROM flights WHERE user_id = ? ORDER BY flight_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $flights = $stmt->fetchAll();

    $csrf_token = getCSRFToken();

    echo "<h2>Your Flight Log</h2>";
    if ($flights) {
        echo "<table>";
        echo "<thead><tr>
                <th>Date</th>
                <th>Aircraft</th>
                <th>From</th>
                <th>To</th>
                <th>Duration</th>
                <th>Notes</th>
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
            echo "<td>" . htmlspecialchars($flight['flight_from']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['flight_to']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['flight_duration']) . "</td>";
            echo "<td>" . htmlspecialchars($flight['notes']) . "</td>";
            echo "<td>";
            if ($flight['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                echo "<a href='flight_edit.php?id=" . $flight['id'] . "'>Edit</a> | ";
                echo "<form method='post' action='flight_delete.php' style='display:inline;' onsubmit='return confirm(\"Are you sure?\");'>";
                echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token) . "'>";
                echo "<input type='hidden' name='id' value='" . htmlspecialchars($flight['id']) . "'>";
                echo "<input type='submit' value='Delete'>";
                echo "</form>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No flight records found. Start by adding a new flight.</p>";
    }
    echo "</div>";
} else {
    echo "<div class='flight-entry-container'>";
    echo "<h2>Welcome to the Flight Log</h2>";
    echo "<p>Please <a href='login.php'>login</a> or <a href='register.php'>register</a> to view your flight records and statistics.</p>";
    echo "</div>";
}
include('footer.php');
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var statsContent = document.getElementById('statsContent');
    var toggleIcon = document.getElementById('toggleIcon');
    if (statsContent && toggleIcon) {
        var collapsed = localStorage.getItem('statsCollapsed');
        if (collapsed === 'true') {
            statsContent.style.display = 'none';
            toggleIcon.textContent = '[+]';
        }
        document.getElementById('statsHeader').addEventListener('click', function() {
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
