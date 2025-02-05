<?php
// reset_password.php
session_start();
require_once('db.php');
$error = '';
$message = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$token) {
    die("Invalid token.");
}

// Verify the token.
$stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$tokenData = $stmt->fetch();

if (!$tokenData) {
    die("Invalid or expired token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the user's password.
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed, $tokenData['user_id']])) {
        // Mark token as used.
        $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
        $stmt->execute([$tokenData['id']]);
        $message = "Password has been reset successfully. <a href='login.php'>Login here</a>.";
    } else {
        $error = "Failed to reset password.";
    }
}

include('header.php');
?>
<h2>Reset Password</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<?php if ($message) { echo "<p class='success'>$message</p>"; } else { ?>
<form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
  <label for="password">New Password:</label>
  <input type="password" name="password" required>
  <input type="submit" value="Reset Password">
</form>
<?php } ?>
<?php include('footer.php'); ?>
