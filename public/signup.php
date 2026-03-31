<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Sign Up page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors    = [];
$firstName = '';
$lastName  = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($firstName)) $errors[] = 'First name is required.';
    if (empty($lastName))  $errors[] = 'Last name is required.';
    if (empty($email))     $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $result = registerUser($firstName, $lastName, $email, $password);
        if (is_int($result)) {
            // Auto-login after registration
            attemptLogin($email, $password);
            setFlash('success', 'Welcome! Your account has been created successfully.');
            redirect('dashboard.php');
        } else {
            $errors[] = $result;
        }
    }
}

$pageTitle = 'Sign Up';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <h1>Create an Account</h1>
    <?= renderFlash() ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="signup.php" class="form-card">
        <?= csrfField() ?>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= e($firstName) ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= e($lastName) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password (min 6 characters)</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button type="submit" class="btn btn-primary">Sign Up</button>
        <p class="form-note">Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
