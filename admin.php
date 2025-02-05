<?php
// admin.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_aircraft'])) {
        $registration = trim($_POST['registration']);
        $type = trim($_POST['type']);
        $manufacturer_serial = trim($_POST['manufacturer_serial']);
        $subtype = trim($_POST['subtype']);
        
        $stmt = $pdo->prepare("INSERT INTO aircraft (registration, type, manufacturer_serial, subtype) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$registration, $type, $manufacturer_serial, $subtype])) {
            $success = "Aircraft added successfully.";
        } else {
            $error = "Failed to add aircraft.";
        }
    } elseif (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $default_role = $_POST['default_role'];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, default_role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed, $role, $default_role])) {
                $success = "User account created.";
            } else {
                $error = "Failed to create user account.";
            }
        }
    }
}
include('header.php');
?>
<h2>Admin Panel</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<?php if ($success) { echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; } ?>
<section>
  <h3>Add New Aircraft</h3>
  <form method="post" action="admin.php">
    <input type="hidden" name="add_aircraft" value="1">
    <label for="registration">Registration:</label>
    <input type="text" name="registration" required>
    <label for="type">Aircraft Type:</label>
    <input type="text" name="type" required>
    <label for="manufacturer_serial">Manufacturer Serial Number:</label>
    <input type="text" name="manufacturer_serial">
    <label for="subtype">Sub Type:</label>
    <input type="text" name="subtype">
    <input type="submit" value="Add Aircraft">
  </form>
</section>
<section>
  <h3>Add New User Account</h3>
  <form method="post" action="admin.php">
    <input type="hidden" name="add_user" value="1">
    <label for="username">Username:</label>
    <input type="text" name="username" required>
    <label for="email">Email:</label>
    <input type="email" name="email" required>
    <label for="password">Password:</label>
    <input type="password" name="password" required>
    <label for="role">Role:</label>
    <select name="role">
      <option value="user">User</option>
      <option value="admin">Admin</option>
    </select>
    <label for="default_role">Default Role:</label>
    <select name="default_role">
      <option value="pilot">Pilot</option>
      <option value="crew">Crew</option>
    </select>
    <input type="submit" value="Add User">
  </form>
</section>
<section>
  <h3>Audit Trail</h3>
  <?php
  $stmt = $pdo->query("SELECT a.*, u.username FROM audit_trail a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50");
  $auditLogs = $stmt->fetchAll();
  if ($auditLogs) {
      echo "<table>";
      echo "<tr><th>Timestamp</th><th>User</th><th>Flight ID</th><th>Action</th><th>Details</th></tr>";
      foreach ($auditLogs as $log) {
          echo "<tr>";
          echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
          echo "<td>" . htmlspecialchars($log['username']) . "</td>";
          echo "<td>" . htmlspecialchars($log['flight_id']) . "</td>";
          echo "<td>" . htmlspecialchars($log['action']) . "</td>";
          echo "<td>" . htmlspecialchars($log['details']) . "</td>";
          echo "</tr>";
      }
      echo "</table>";
  } else {
      echo "<p>No audit logs found.</p>";
  }
  ?>
</section>
<?php include('footer.php'); ?>
