<?php
// login.php
session_start();
require_once('db.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
include('header.php');
?>
<h2>Login</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<form method="post" action="login.php">
  <label for="username">Username:</label>
  <input type="text" name="username" required>
  <label for="password">Password:</label>
  <input type="password" name="password" required>
  <input type="submit" value="Login">
</form>
<p><a href="forgot_password.php">Forgot Password?</a></p>
<?php include('footer.php'); ?>
