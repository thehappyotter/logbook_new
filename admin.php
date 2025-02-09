<?php
// admin.php - Admin Panel
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
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
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Admin Panel</h2>
  </div>
  <div class="card-body">
    <?php 
      if ($error != '') {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
      }
      if ($success != '') {
          echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>";
      }
    ?>
    <!-- Add Aircraft Section -->
    <section class="mb-4">
      <h3 class="h5">Add New Aircraft</h3>
      <form method="post" action="admin.php">
        <input type="hidden" name="add_aircraft" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        <div class="mb-3">
          <label for="registration" class="form-label">Registration:</label>
          <input type="text" name="registration" id="registration" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="type" class="form-label">Aircraft Type:</label>
          <input type="text" name="type" id="type" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="manufacturer_serial" class="form-label">Manufacturer Serial:</label>
          <input type="text" name="manufacturer_serial" id="manufacturer_serial" class="form-control">
        </div>
        <div class="mb-3">
          <label for="subtype" class="form-label">Subtype:</label>
          <input type="text" name="subtype" id="subtype" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Add Aircraft</button>
      </form>
    </section>
    
    <!-- Add User Section -->
    <section class="mb-4">
      <h3 class="h5">Add New User Account</h3>
      <form method="post" action="admin.php">
        <input type="hidden" name="add_user" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        <div class="mb-3">
          <label for="username" class="form-label">Username:</label>
          <input type="text" name="username" id="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email:</label>
          <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password:</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="mb-3 row">
          <div class="col-md-6">
            <label for="role" class="form-label">Role:</label>
            <select name="role" id="role" class="form-select">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="default_role" class="form-label">Default Role:</label>
            <select name="default_role" id="default_role" class="form-select">
              <option value="pilot">Pilot</option>
              <option value="crew">Crew</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Add User</button>
      </form>
    </section>
    
    <!-- Manage Bases Section -->
    <section class="mb-4">
      <h3 class="h5">Manage Bases</h3>
      <!-- Add Base Form -->
      <form method="post" action="admin.php" class="mb-3">
        <input type="hidden" name="add_base" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        <div class="mb-3">
          <label for="base_name" class="form-label">New Base Name:</label>
          <input type="text" name="base_name" id="base_name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Base</button>
      </form>
      
      <!-- List Existing Bases -->
      <?php
      $stmtBasesList = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
      $basesList = $stmtBasesList->fetchAll();
      if ($basesList):
      ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Base Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($basesList as $base): ?>
          <tr>
            <td><?php echo htmlspecialchars($base['id']); ?></td>
            <td><?php echo htmlspecialchars($base['base_name']); ?></td>
            <td>
              <form class="d-inline" method="post" action="admin.php">
                <input type="hidden" name="base_id" value="<?php echo htmlspecialchars($base['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                <input type="text" name="base_name" value="<?php echo htmlspecialchars($base['base_name']); ?>" class="form-control d-inline-block" style="width: auto;" required>
                <button type="submit" name="edit_base" class="btn btn-sm btn-warning">Edit</button>
              </form>
              <form class="d-inline" method="post" action="admin.php" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="base_id" value="<?php echo htmlspecialchars($base['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                <button type="submit" name="delete_base" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>No bases found.</p>
      <?php endif; ?>
    </section>
    
    <!-- Audit Trail Section -->
    <section>
      <h3 class="h5">Audit Trail</h3>
      <?php
      $stmtAudit = $pdo->query("SELECT a.*, u.username FROM audit_trail a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50");
      $auditLogs = $stmtAudit->fetchAll();
      if ($auditLogs):
      ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Flight ID</th>
            <th>Action</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($auditLogs as $log): ?>
          <tr>
            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
            <td><?php echo htmlspecialchars($log['username']); ?></td>
            <td><?php echo htmlspecialchars($log['flight_id']); ?></td>
            <td><?php echo htmlspecialchars($log['action']); ?></td>
            <td><?php echo htmlspecialchars($log['details']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>No audit logs found.</p>
      <?php endif; ?>
    </section>
    
  </div>
</div>
<?php include('footer.php'); ?>
