<?php
require 'db.php';

$stmt = $pdo->query("SELECT id, password FROM admins");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {

    $plainPassword = $user['password'];

    // Skip only if already hashed
    if (strpos($plainPassword, '$2y$') === 0) {
        continue;
    }

    $hashed = password_hash($plainPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $update->execute([$hashed, $user['id']]);
}

echo "Passwords updated!";