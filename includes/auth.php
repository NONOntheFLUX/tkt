<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Authentication helper.
 * Start session and provide login-state utility functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------ */
/*  Session helpers                                                     */
/* ------------------------------------------------------------------ */

/**
 * Check if user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged-in user's data from session.
 * Returns null if not logged in.
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'         => $_SESSION['user_id'],
        'first_name' => $_SESSION['user_first_name'] ?? '',
        'last_name'  => $_SESSION['user_last_name']  ?? '',
        'email'      => $_SESSION['user_email']       ?? '',
        'role'       => $_SESSION['user_role']        ?? 'user',
    ];
}

/**
 * Get the current user's role string.
 */
function currentRole(): string
{
    return $_SESSION['user_role'] ?? 'visitor';
}

/**
 * Get the current user's ID.
 */
function currentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/* ------------------------------------------------------------------ */
/*  Login / Logout                                                     */
/* ------------------------------------------------------------------ */

/**
 * Attempt login with email and password.
 * Sets session variables on success.
 *
 * @return string|true  true on success, error message string on failure.
 */
function attemptLogin(string $email, string $password): string|bool
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return 'Invalid email or password.';
    }

    if (!$user['is_active']) {
        return 'Your account has been disabled. Contact an admin.';
    }

    if (!password_verify($password, $user['password'])) {
        return 'Invalid email or password.';
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']         = $user['id'];
    $_SESSION['user_first_name'] = $user['first_name'];
    $_SESSION['user_last_name']  = $user['last_name'];
    $_SESSION['user_email']      = $user['email'];
    $_SESSION['user_role']       = $user['role'];

    return true;
}

/**
 * Register a new user.
 *
 * @return int|string  New user ID on success, error message on failure.
 */
function registerUser(string $firstName, string $lastName, string $email, string $password): int|string
{
    $db = getDB();

    // Check duplicate email
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return 'An account with this email already exists.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role)
         VALUES (:fn, :ln, :email, :pw, :role)'
    );
    $stmt->execute([
        ':fn'    => $firstName,
        ':ln'    => $lastName,
        ':email' => $email,
        ':pw'    => $hash,
        ':role'  => 'user',
    ]);

    return (int) $db->lastInsertId();
}

/**
 * Destroy session and log user out.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Require the user to be logged in; redirect to login if not.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require a specific role (or higher).
 * Role hierarchy: admin > sales_manager > sales_rep > user > visitor.
 */
function requireRole(string $minRole): void
{
    requireLogin();
    if (!hasMinRole($minRole)) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="container"><div class="alert alert-danger">Access denied. You do not have permission to view this page.</div></div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Check if current user meets the minimum role.
 */
function hasMinRole(string $minRole): bool
{
    $hierarchy = [
        'visitor'       => 0,
        'user'          => 1,
        'sales_rep'     => 2,
        'sales_manager' => 3,
        'admin'         => 4,
    ];

    $userLevel = $hierarchy[currentRole()] ?? 0;
    $minLevel  = $hierarchy[$minRole]      ?? 0;

    return $userLevel >= $minLevel;
}

/**
 * Count unread notifications for current user.
 */
function unreadNotificationCount(): int
{
    if (!isLoggedIn()) return 0;

    $db   = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => currentUserId()]);
    return (int) $stmt->fetchColumn();
}
