<?php
// admin_bases.php - Manage Bases page for admins
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid CSRF token.";
    } else {
        if (isset($_POST['add_base'])) {
            $base_name = trim($_POST['base_name']);
            $base_code = trim($_POST['base_code'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (empty($base_name)) {
                $error[] = "Base name is required.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO bases (base_name, base_code, description) VALUES (?, ?, ?)");
                if ($stmt->execute([$base_name, $base_code, $description])) {
                    $success[] = "Base added successfully.";
                } else {
                    $error[] = "Failed to add base.";
                }
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM bases ORDER BY base_name ASC");
$bases = $stmt->fetchAll();

include('header.php');
?>
<div class="flight-entry-container">
  <h2>Manage Bases</h2>
  <?php 
  foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
  foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
  ?>
  <h3>Add New Base</h3>
  <form method="post" action="admin_bases.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    <input type="hidden" name="add_base" value="1">
    <div class="form-group">
      <label for="base_name">Base Name:</label>
      <input type="text" name="base_name" id="base_name" required>
    </div>
    <div class="form-group">
      <label for="base_code">Base Code:</label>
      <input type="text" name="base_code" id="base_code">
    </div>
    <div class="form-group">
      <label for="description">Description:</label>
      <textarea name="description" id="description"></textarea>
    </div>
    <div class="form-group">
      <input type="submit" value="Add Base">
    </div>
  </form>
  
  <h3>Existing Bases</h3>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Base Name</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($bases as $base): ?>
      <tr>
        <td><?php echo htmlspecialchars($base['id']); ?></td>
        <td><?php echo htmlspecialchars($base['base_name']); ?></td>
        <td>
          <form style="display:inline;" method="post" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <input type="hidden" name="base_id" value="<?php echo htmlspecialchars($base['id']); ?>">
            <input type="text" name="base_name" value="<?php echo htmlspecialchars($base['base_name']); ?>" required>
            <input type="submit" name="edit_base" value="Edit">
          </form>
          <form style="display:inline;" method="post" action="admin.php" onsubmit="return confirm('Are you sure?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <input type="hidden" name="base_id" value="<?php echo htmlspecialchars($base['id']); ?>">
            <input type="submit" name="delete_base" value="Delete">
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include('footer.php'); ?>
