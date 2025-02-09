<?php
// forgot_password.php
session_start();
require_once('db.php');
require_once('functions.php');

$message = '';
$error = [];
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate a token and store it.
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            if ($stmt->execute([$user['id'], $token, $expires_at])) {
                $reset_link = "http://yourdomain.com/reset_password.php?token=" . urlencode($token);
                $message = "A password reset link has been generated. For demo purposes, click <a href='" . htmlspecialchars($reset_link) . "'>here</a> to reset your password.";
            } else {
                $error[] = "Failed to generate reset token.";
            }
        } else {
            $error[] = "Email address not found.";
        }
    }
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Forgot Password</h2>
  </div>
  <div class="card-body">
    <?php
      foreach ($error as $msg) {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>";
      }
      if ($message) {
          echo "<div class='alert alert-success'>" . $message . "</div>";
      }
    ?>
    <form method="post" action="forgot_password.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <div class="mb-3">
        <label for="email" class="form-label">Enter your registered email address:</label>
        <input type="email" class="form-control" name="email" id="email" required>
      </div>
      <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
  </div>
</div>
<?php include('footer.php'); ?>
