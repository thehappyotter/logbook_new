<?php
// register.php
session_start();
require_once('db.php');
require_once('functions.php');

$error = [];
$csrf_token = getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $default_role = $_POST['default_role'];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error[] = "Invalid email format.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error[] = "Username or Email already taken.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, default_role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $default_role])) {
                    header("Location: login.php");
                    exit;
                } else {
                    $error[] = "Registration failed. Please try again.";
                }
            }
        }
    }
}

include('header.php');
?>
<div class="card flight-entry-container">
  <div class="card-header">
    <h2 class="mb-0">Register</h2>
  </div>
  <div class="card-body">
    <?php
      foreach ($error as $msg) {
          echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>";
      }
    ?>
    <form method="post" action="register.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <div class="mb-3">
        <label for="username" class="form-label">Username:</label>
        <input type="text" class="form-control" name="username" id="username" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input type="email" class="form-control" name="email" id="email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password:</label>
        <input type="password" class="form-control" name="password" id="password" required>
      </div>
      <div class="mb-3">
        <label for="default_role" class="form-label">Default Role:</label>
        <select class="form-select" name="default_role" id="default_role">
          <option value="pilot">Pilot</option>
          <option value="crew">Crew</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Register</button>
    </form>
  </div>
</div>
<?php include('footer.php'); ?>
