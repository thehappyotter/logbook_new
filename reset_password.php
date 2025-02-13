<?php
// reset_password.php
session_start();
require_once('db.php');
require_once('functions.php');

$error = [];
$message = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$token) {
    die("Invalid token.");
}

$stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$tokenData = $stmt->fetch();

if (!$tokenData) {
    die("Invalid or expired token.");
}

$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        $new_password = $_POST['password'];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed, $tokenData['user_id']])) {
            $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
            $stmt->execute([$tokenData['id']]);
            $message = "Password has been reset successfully. <a href='login.php'>Login here</a>.";
        } else {
            $error[] = "Failed to reset password.";
        }
    }
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Reset Password</h2>
  </div>
  <div class="card-body">
    <?php
      foreach ($error as $msg) {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>";
      }
      if ($message) {
          echo "<div class='alert alert-success'>" . $message . "</div>";
      } else {
    ?>
    <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <div class="mb-3">
        <label for="password" class="form-label">New Password:</label>
        <input type="password" class="form-control" name="password" id="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
    <?php } ?>
  </div>
</div>
<?php include('footer.php'); ?>
