<?php
require_once '../config/database.php';

// Hash baru untuk password 'admin123'
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Update user admin
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$new_hash]);

echo "Password admin telah diupdate ke 'admin123'. Hash baru: " . $new_hash;
echo "<br>Silakan <a href='login.php'>coba login lagi</a>";
?>