<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Header template.
 * Included at the top of every page.
 * Expects $pageTitle to be set before including.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';

$pageTitle = $pageTitle ?? 'Simple CRM';
$notifCount = unreadNotificationCount();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Simple CRM</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">Simple CRM</a>

        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="leads.php">Leads</a>
            <?php if (isLoggedIn()): ?>
                <a href="companies.php">Companies</a>
                <a href="contacts.php">Contacts</a>
                <a href="deals.php">Deals</a>
                <a href="activities.php">Activities</a>
            <?php endif; ?>
        </nav>

        <nav class="user-nav">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="notifications.php" class="notif-link">
                    Notifications
                    <?php if ($notifCount > 0): ?>
                        <span class="badge"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if (canAccessAdmin()): ?>
                    <a href="admin/users.php">Admin</a>
                <?php endif; ?>
                <a href="profile.php"><?= htmlspecialchars($user['first_name']) ?></a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="site-main">
