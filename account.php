<?php
// account.php - My Account Page allowing password, default base, and default role updates.
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = [];
$success = [];

// Process form submission for updating password, default base, and default role.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        // Update password if provided.
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
        
        // Update default base.
        if (isset($_POST['default_base'])) {
            $default_base = $_POST['default_base'];
            $stmt = $pdo->prepare("UPDATE users SET default_base = ? WHERE id = ?");
            if ($stmt->execute([$default_base, $_SESSION['user_id']])) {
                $success[] = "Default base updated successfully.";
            } else {
                $error[] = "Failed to update default base.";
            }
        }
        
        // Update default role.
        if (isset($_POST['default_role'])) {
            $default_role = $_POST['default_role'];
            // Validate the value.
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

// Fetch current user details.
$stmt = $pdo->prepare("SELECT username, default_role, default_base FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch bases from the database.
$stmtBases = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmtBases->fetchAll();

include('header.php');
?>
<h2>My Account</h2>
<?php 
foreach ($error as $msg) { 
    echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; 
}
foreach ($success as $msg) { 
    echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; 
}
?>
<form method="post" action="account.php">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
  
  <label for="password">New Password:</label>
  <input type="password" name="password" id="password">
  
  <label for="default_base">Default Base:</label>
  <select name="default_base" id="default_base">
    <?php foreach ($bases as $base): ?>
      <option value="<?php echo htmlspecialchars($base['id']); ?>" <?php if ($base['id'] == $user['default_base']) echo "selected"; ?>>
        <?php echo htmlspecialchars($base['base_name']); ?>
      </option>
    <?php endforeach; ?>
  </select>
  
  <label for="default_role">Default Role:</label>
  <select name="default_role" id="default_role">
    <option value="pilot" <?php if ($user['default_role'] === 'pilot') echo "selected"; ?>>Pilot</option>
    <option value="crew" <?php if ($user['default_role'] === 'crew') echo "selected"; ?>>Crew</option>
  </select>
  
  <input type="submit" value="Update">
</form>
<?php include('footer.php'); ?>
