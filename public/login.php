<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Login page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $result = attemptLogin($email, $password);
        if ($result === true) {
            setFlash('success', 'Welcome back, ' . ($_SESSION['user_first_name'] ?? 'User') . '!');
            redirect('dashboard.php');
        } else {
            $errors[] = $result;
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <h1>Login</h1>
    <?= renderFlash() ?>

    <div class="card" style="margin-bottom: 24px;">
        <h2>Demo Accounts</h2>
        <p><strong>Visitor:</strong> no login required (public access only).</p>
        <ul style="margin-top: 12px; line-height: 1.8;">
            <li><strong>Registered User</strong> — Email: <code>user@example.com</code> | Password: <code>User123!</code></li>
            <li><strong>Sales Rep</strong> — Email: <code>rep@example.com</code> | Password: <code>Rep123!</code></li>
            <li><strong>Sales Manager</strong> — Email: <code>manager@example.com</code> | Password: <code>Manager123!</code></li>
            <li><strong>Admin</strong> — Email: <code>admin@example.com</code> | Password: <code>Admin123!</code></li>
        </ul>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="form-card">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
        <p class="form-note">Don't have an account? <a href="signup.php">Sign up here</a>.</p>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
