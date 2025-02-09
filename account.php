<?php
// account.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch current user details.
$stmt = $pdo->prepare("SELECT username, default_role, default_base FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch list of bases.
$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

$updateSuccess = "";
$updateError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update default role if submitted.
    if (isset($_POST['default_role'])) {
        $default_role = $_POST['default_role'];
        $stmtUpdateRole = $pdo->prepare("UPDATE users SET default_role = ? WHERE id = ?");
        if ($stmtUpdateRole->execute([$default_role, $_SESSION['user_id']])) {
            $_SESSION['default_role'] = $default_role;
            $updateSuccess .= "Default role updated successfully. ";
        } else {
            $updateError .= "Failed to update default role. ";
        }
    }
    // Update default base if submitted.
    if (isset($_POST['default_base'])) {
        $default_base = $_POST['default_base'];
        $stmtUpdateBase = $pdo->prepare("UPDATE users SET default_base = ? WHERE id = ?");
        if ($stmtUpdateBase->execute([$default_base, $_SESSION['user_id']])) {
            $updateSuccess .= "Default base updated successfully. ";
        } else {
            $updateError .= "Failed to update default base. ";
        }
        // Refresh $user default_base for display.
        $stmt = $pdo->prepare("SELECT default_base FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = array_merge($user, $stmt->fetch());
    }
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">My Account</h2>
  </div>
  <div class="card-body">
    <?php 
      if (!empty($updateError)) {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($updateError) . "</div>";
      }
      if (!empty($updateSuccess)) {
          echo "<div class='alert alert-success'>" . htmlspecialchars($updateSuccess) . "</div>";
      }
    ?>
    <form method="post" action="account.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
      <div class="mb-3">
        <label for="default_role" class="form-label">Default Role:</label>
        <select name="default_role" id="default_role" class="form-select">
          <option value="pilot" <?php echo ($user['default_role'] === 'pilot') ? 'selected' : ''; ?>>Pilot</option>
          <option value="crew" <?php echo ($user['default_role'] === 'crew') ? 'selected' : ''; ?>>Crew</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="default_base" class="form-label">Default Base:</label>
        <select name="default_base" id="default_base" class="form-select">
          <?php foreach ($bases as $base): ?>
            <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php echo ($base['id'] == $user['default_base']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($base['base_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Update Account</button>
    </form>
  </div>
</div>
<?php include('footer.php'); ?>
