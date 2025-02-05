<?php
// register.php
session_start();
require_once('db.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $default_role = $_POST['default_role'];

    // Check if username or email already exists.
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $error = "Username or Email already taken.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, default_role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password, $default_role])) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
include('header.php');
?>
<h2>Register</h2>
<?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
<form method="post" action="register.php">
  <label for="username">Username:</label>
  <input type="text" name="username" required>
  <label for="email">Email:</label>
  <input type="email" name="email" required>
  <label for="password">Password:</label>
  <input type="password" name="password" required>
  <label for="default_role">Default Role:</label>
  <select name="default_role">
    <option value="pilot">Pilot</option>
    <option value="crew">Crew</option>
  </select>
  <input type="submit" value="Register">
</form>
<?php include('footer.php'); ?>
