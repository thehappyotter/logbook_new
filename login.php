<?php
// login.php
session_start();
require_once('db.php');
require_once('functions.php');

$error = [];
$csrf_token = getCSRFToken();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error[] = "Invalid username or password.";
        }
    }
}

include('header.php');
?>
<div class="flight-entry-container">
  <h2>Login</h2>
  <?php
    foreach ($error as $msg) {
        echo "<p class='error'>" . htmlspecialchars($msg) . "</p>";
    }
  ?>
  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <div class="form-group">
      <label for="username">Username:</label>
      <input type="text" name="username" id="username" required>
    </div>
    <div class="form-group">
      <label for="password">Password:</label>
      <input type="password" name="password" id="password" required>
    </div>
    <div class="form-group">
      <input type="submit" value="Login">
    </div>
  </form>
  <p><a href="forgot_password.php">Forgot Password?</a></p>
</div>
<?php include('footer.php'); ?>
