<?php
$host = "localhost";
$dbname = "CEE_IT_CONNECTS";
$user = "postgres"; 
$pass = "12345"; 

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
