<?php
// forgot_password.php
session_start();
require_once('db.php');
require_once('functions.php');
$message = '';
$error = '';
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires_at]);
            
            // In production, send an email. For demo, display the reset link.
            $reset_link = "http://yourdomain.com/reset_password.php?token=" . urlencode($token);
            $message = "A password reset link has been generated. For demo purposes, click <a href='" . htmlspecialchars($reset_link) . "'>here</a> to reset your password.";
        } else {
            $error = "Email address not found.";
        }
    }
}
include('header.php');
?>
<h2>Forgot Password</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<?php if ($message) { echo "<p class='success'>$message</p>"; } ?>
<form method="post" action="forgot_password.php">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
  <label for="email">Enter your registered email address:</label>
  <input type="email" name="email" required>
  <input type="submit" value="Reset Password">
</form>
<?php include('footer.php'); ?>
