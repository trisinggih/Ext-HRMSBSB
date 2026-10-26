<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hrms_bsb_real', 'root', '');
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE email = ?');
$stmt->execute([$hash, 'admin@example.com']);
echo "Updated: " . $stmt->rowCount() . "\n";
