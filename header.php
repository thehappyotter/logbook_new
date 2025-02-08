<?php
// header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Flight Log</title>
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  <header>
    <div class="main-menu">
      <a href="index.php">Home</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="flight_entry.php">New Flight</a>
        <a href="search.php">Search Flights</a>
        <a href="export.php">Export CSV</a>
        <a href="account.php">My Account</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="admin.php">Admin Panel</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="user-menu">
      <?php if (isset($_SESSION['user_id'])): ?>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </header>
  <!-- Start the main wrapper -->
  <div class="wrapper">
    <main>
