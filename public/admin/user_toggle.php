<?php
/**
 * Admin: Enable / Disable a user (POST only).
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../models/User.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}

requireCsrf();

$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($userId <= 0) {
    setFlash('danger', 'Invalid user.');
    redirect('users.php');
}

// Prevent admin from disabling themselves
if ($userId === currentUserId()) {
    setFlash('danger', 'You cannot disable your own account.');
    redirect('users.php');
}

if ($action === 'disable') {
    toggleUserActive($userId, false);
    setFlash('success', 'User has been disabled.');
} elseif ($action === 'enable') {
    toggleUserActive($userId, true);
    setFlash('success', 'User has been enabled.');
} else {
    setFlash('danger', 'Invalid action.');
}

redirect('users.php');
