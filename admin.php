<?php
// admin.php - Admin Panel (updated Manage Bases section)
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Aircraft
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
    }
    // Add User
    elseif (isset($_POST['add_user'])) {
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
    // Manage Bases: Add new base
    elseif (isset($_POST['add_base'])) {
        $base_name = trim($_POST['base_name']);
        if (empty($base_name)) {
            $error = "Base name cannot be empty.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO bases (base_name) VALUES (?)");
            if ($stmt->execute([$base_name])) {
                $success = "Base added successfully.";
            } else {
                $error = "Failed to add base.";
            }
        }
    }
    // Manage Bases: Edit existing base
    elseif (isset($_POST['edit_base'])) {
        $base_id = $_POST['base_id'];
        $base_name = trim($_POST['base_name']);
        if (empty($base_name)) {
            $error = "Base name cannot be empty.";
        } else {
            $stmt = $pdo->prepare("UPDATE bases SET base_name = ? WHERE id = ?");
            if ($stmt->execute([$base_name, $base_id])) {
                $success = "Base updated successfully.";
            } else {
                $error = "Failed to update base.";
            }
        }
    }
    // Manage Bases: Delete base
    elseif (isset($_POST['delete_base'])) {
        $base_id = $_POST['base_id'];
        $stmt = $pdo->prepare("DELETE FROM bases WHERE id = ?");
        if ($stmt->execute([$base_id])) {
            $success = "Base deleted successfully.";
        } else {
            $error = "Failed to delete base.";
        }
    }
}

include('header.php');
?>
<div class="flight-entry-container">
  <h2>Admin Panel</h2>
  <?php 
    if ($error != '') {
        echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
    }
    if ($success != '') {
        echo "<p class='success'>" . htmlspecialchars($success) . "</p>";
    }
  ?>
  
  <section>
    <h3>Add New Aircraft</h3>
    <form method="post" action="admin.php">
      <input type="hidden" name="add_aircraft" value="1">
      <div class="form-group">
        <label for="registration">Registration:</label>
        <input type="text" name="registration" required>
      </div>
      <div class="form-group">
        <label for="type">Aircraft Type:</label>
        <input type="text" name="type" required>
      </div>
      <div class="form-group">
        <label for="manufacturer_serial">Manufacturer Serial:</label>
        <input type="text" name="manufacturer_serial">
      </div>
      <div class="form-group">
        <label for="subtype">Subtype:</label>
        <input type="text" name="subtype">
      </div>
      <div class="form-group">
        <input type="submit" value="Add Aircraft">
      </div>
    </form>
  </section>
  
  <section>
    <h3>Add New User Account</h3>
    <form method="post" action="admin.php">
      <input type="hidden" name="add_user" value="1">
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label for="role">Role:</label>
        <select name="role">
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="default_role">Default Role:</label>
        <select name="default_role">
          <option value="pilot">Pilot</option>
          <option value="crew">Crew</option>
        </select>
      </div>
      <div class="form-group">
        <input type="submit" value="Add User">
      </div>
    </form>
  </section>
  
  <section>
    <h3>Manage Bases</h3>
    <!-- Form to add a new base -->
    <form method="post" action="admin.php" style="margin-bottom: 20px;">
      <input type="hidden" name="add_base" value="1">
      <div class="form-group">
        <label for="base_name">New Base Name:</label>
        <input type="text" name="base_name" id="base_name" required>
      </div>
      <div class="form-group">
        <input type="submit" value="Add Base">
      </div>
    </form>
    
    <!-- List existing bases with options to edit or delete -->
    <?php
    $stmtBasesList = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
    $basesList = $stmtBasesList->fetchAll();
    if ($basesList) {
        echo "<table>";
        echo "<thead><tr><th>ID</th><th>Base Name</th><th>Actions</th></tr></thead><tbody>";
        foreach ($basesList as $base) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($base['id']) . "</td>";
            echo "<td>" . htmlspecialchars($base['base_name']) . "</td>";
            echo "<td>";
            // Edit form for each base.
            echo "<form style='display:inline;' method='post' action='admin.php'>";
            echo "<input type='hidden' name='base_id' value='" . htmlspecialchars($base['id']) . "'>";
            echo "<input type='text' name='base_name' value='" . htmlspecialchars($base['base_name']) . "' required>";
            echo "<input type='submit' name='edit_base' value='Edit'>";
            echo "</form> ";
            // Delete form.
            echo "<form style='display:inline;' method='post' action='admin.php' onsubmit='return confirm(\"Are you sure?\");'>";
            echo "<input type='hidden' name='base_id' value='" . htmlspecialchars($base['id']) . "'>";
            echo "<input type='submit' name='delete_base' value='Delete'>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No bases found.</p>";
    }
    ?>
  </section>
  
  <section>
    <h3>Audit Trail</h3>
    <?php
    $stmtAudit = $pdo->query("SELECT a.*, u.username FROM audit_trail a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50");
    $auditLogs = $stmtAudit->fetchAll();
    if ($auditLogs) {
        echo "<table>";
        echo "<thead><tr><th>Timestamp</th><th>User</th><th>Flight ID</th><th>Action</th><th>Details</th></tr></thead><tbody>";
        foreach ($auditLogs as $log) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($log['username']) . "</td>";
            echo "<td>" . htmlspecialchars($log['flight_id']) . "</td>";
            echo "<td>" . htmlspecialchars($log['action']) . "</td>";
            echo "<td>" . htmlspecialchars($log['details']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No audit logs found.</p>";
    }
    ?>
  </section>
</div>
<?php include('footer.php'); ?>
