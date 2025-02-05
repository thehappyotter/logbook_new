<?php
// db.php
$host = 'localhost';
$db   = 'flightlog';
$user = 'flightlog';      // Change to your database username
$pass = 'flightlog';      // Change to your database password
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
