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
  <!-- Bootstrap CSS via CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Google Font: Roboto -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <header>
    <!-- Bootstrap Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Flight Log</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php if (isset($_SESSION['user_id'])): ?>
              <li class="nav-item"><a class="nav-link" href="flight_entry.php">New Flight</a></li>
              <li class="nav-item"><a class="nav-link" href="search.php">Search Flights</a></li>
              <li class="nav-item"><a class="nav-link" href="export.php">Export CSV</a></li>
              <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="admin.php">Admin Panel</a></li>
              <?php endif; ?>
            <?php endif; ?>
          </ul>
          <ul class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
              <li class="nav-item">
                <span class="navbar-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
              </li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
              <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>
  <!-- Begin main container -->
  <div class="container my-4">
    <main>
