<?php
// base_edit.php
session_start();
require_once('db.php');
require_once('functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Base ID not specified.");
}

$base_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM bases WHERE id = ?");
$stmt->execute([$base_id]);
$base = $stmt->fetch();

if (!$base) {
    die("Base not found.");
}

$error = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error[] = "Invalid request. Please try again.";
    } else {
        $base_name = trim($_POST['base_name']);
        $base_code = trim($_POST['base_code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $stmt = $pdo->prepare("UPDATE bases SET base_name = ?, base_code = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$base_name, $base_code, $description, $base_id])) {
            $success[] = "Base updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM bases WHERE id = ?");
            $stmt->execute([$base_id]);
            $base = $stmt->fetch();
        } else {
            $error[] = "Failed to update base.";
        }
    }
}

include('header.php');
?>
<h2>Edit Base</h2>
<?php
foreach ($error as $msg) { echo "<p class='error'>" . htmlspecialchars($msg) . "</p>"; }
foreach ($success as $msg) { echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; }
?>
<form method="post" action="base_edit.php?id=<?php echo $base_id; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    <label for="base_name">Base Name:</label>
    <input type="text" name="base_name" id="base_name" value="<?php echo htmlspecialchars($base['base_name']); ?>" required>
    
    <label for="base_code">Base Code:</label>
    <input type="text" name="base_code" id="base_code" value="<?php echo htmlspecialchars($base['base_code']); ?>">
    
    <label for="description">Description:</label>
    <textarea name="description" id="description"><?php echo htmlspecialchars($base['description']); ?></textarea>
    
    <input type="submit" value="Update Base">
</form>
<?php include('footer.php'); ?>
