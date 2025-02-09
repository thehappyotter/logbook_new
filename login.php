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
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Login</h2>
  </div>
  <div class="card-body">
    <?php
      foreach ($error as $msg) {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>";
      }
    ?>
    <form method="post" action="login.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <div class="mb-3">
        <label for="username" class="form-label">Username:</label>
        <input type="text" class="form-control" name="username" id="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password:</label>
        <input type="password" class="form-control" name="password" id="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
    <p class="mt-3"><a href="forgot_password.php">Forgot Password?</a></p>
  </div>
</div>
<?php include('footer.php'); ?>
