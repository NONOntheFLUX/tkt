<?php
/**
 * Demo Setup Script
 * Run this ONCE after importing schema.sql to set up working demo passwords.
 * Access: http://localhost/crm/public/setup_demo.php
 * DELETE THIS FILE after running it in production.
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Simple CRM - Demo Password Setup</h2>";

$accounts = [
    ['admin@example.com',   'Admin123!'],
    ['rep@example.com',     'Rep123!'],
    ['user@example.com',    'User123!'],
    ['manager@example.com', 'Manager123!'],
];

$db = getDB();
$updated = 0;

foreach ($accounts as [$email, $password]) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password = :pw WHERE email = :email');
    $stmt->execute([':pw' => $hash, ':email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "<p>Updated: <strong>{$email}</strong> / {$password}</p>";
        $updated++;
    } else {
        echo "<p style='color:orange;'>Skipped (not found): {$email}</p>";
    }
}

echo "<hr>";
echo "<p><strong>{$updated} account(s) updated.</strong></p>";
echo "<p>You can now log in with the demo accounts above.</p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
echo "<p style='color:red;'><strong>IMPORTANT:</strong> Delete this file (setup_demo.php) after use!</p>";
