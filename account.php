<?php
// account.php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['password'])) {
        $new_password = $_POST['password'];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed, $_SESSION['user_id']])) {
            $success = "Password updated successfully.";
        } else {
            $error = "Update failed.";
        }
    }
}
include('header.php');
?>
<h2>My Account</h2>
<?php
if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; }
if ($success) { echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; }
?>
<form method="post" action="account.php">
  <label for="password">New Password:</label>
  <input type="password" name="password">
  <input type="submit" value="Update">
</form>
<?php include('footer.php'); ?>
