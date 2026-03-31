<?php
/**
 * Logout - destroy session and redirect to home.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

logout();
setFlash('success', 'You have been logged out.');
redirect('index.php');
