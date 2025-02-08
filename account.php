<?php
// account.php - My Account page
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        if (!empty(trim($_POST['password']))) {
            $new_password = trim($_POST['password']);
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $_SESSION['user_id']])) {
                $success[] = "Password updated successfully.";
            } else {
                $error[] = "Failed to update password.";
            }
        }
        if (isset($_POST['default_base'])) {
            $default_base = $_POST['default_base'];
            $stmt = $pdo->prepare("UPDATE users SET default_base = ? WHERE id = ?");
            if ($stmt->execute([$default_base, $_SESSION['user_id']])) {
                $success[] = "Default base updated successfully.";
            } else {
                $error[] = "Failed to update default base.";
            }
        }
        if (isset($_POST['default_role'])) {
            $default_role = $_POST['default_role'];
            if ($default_role === 'pilot' || $default_role === 'crew') {
                $stmt = $pdo->prepare("UPDATE users SET default_role = ? WHERE id = ?");
                if ($stmt->execute([$default_role, $_SESSION['user_id']])) {
                    $success[] = "Default role updated successfully.";
                } else {
                    $error[] = "Failed to update default role.";
                }
            } else {
                $error[] = "Invalid default role selected.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT username, default_role, default_base FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

include('header.php');
?>
<div class="flight-entry-container">
  <h2>My Account</h2>
  <?php 
  foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
  foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
  ?>
  <form method="post" action="account.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    
    <div class="form-group">
      <label for="password">New Password:</label>
      <input type="password" name="password" id="password">
    </div>
    
    <div class="form-group">
      <label for="default_base">Default Base:</label>
      <select name="default_base" id="default_base">
        <?php foreach ($bases as $base): ?>
          <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $user['default_base']) echo "selected"; ?>>
            <?php echo htmlspecialchars($base['base_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    
    <div class="form-group">
      <label for="default_role">Default Role:</label>
      <select name="default_role" id="default_role">
        <option value="pilot" <?php if ($user['default_role'] === 'pilot') echo "selected"; ?>>Pilot</option>
        <option value="crew" <?php if ($user['default_role'] === 'crew') echo "selected"; ?>>Crew</option>
      </select>
    </div>
    
    <div class="form-group">
      <input type="submit" value="Update">
    </div>
  </form>
</div>
<?php include('footer.php'); ?>
