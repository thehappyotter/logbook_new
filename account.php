<?php
// account.php - Updated My Account Page with Default Base Setting using Base ID
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
        $error[] = "Invalid request. Please try again.";
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
            if (!filter_var($default_base, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                $error[] = "Invalid base selection.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET default_base = ? WHERE id = ?");
                if ($stmt->execute([$default_base, $_SESSION['user_id']])) {
                    $success[] = "Default base updated successfully.";
                } else {
                    $error[] = "Failed to update default base.";
                }
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
<h2>My Account</h2>
<?php 
foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
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
  <input type="submit" value="Update">
</form>
<?php include('footer.php'); ?>
