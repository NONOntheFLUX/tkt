<?php
/**
 * CSRF Token helper.
 * Generates and verifies tokens for POST forms.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store in session.
 * Returns the token string.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field with the CSRF token.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Verify a submitted CSRF token.
 * Call this at the top of every POST handler.
 *
 * @return bool
 */
function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF or die with 403.
 */
function requireCsrf(): void
{
    if (!verifyCsrf()) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}
