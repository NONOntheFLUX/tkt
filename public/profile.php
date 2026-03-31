<?php
/**
 * User Profile page - view and edit own profile, change password.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();

$user   = getUserById(currentUserId());
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        if (empty($firstName)) $errors[] = 'First name is required.';
        if (empty($lastName))  $errors[] = 'Last name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        // Check email uniqueness
        if (empty($errors) && $email !== $user['email']) {
            $existing = getUserByEmail($email);
            if ($existing && $existing['id'] != currentUserId()) {
                $errors[] = 'This email is already in use by another account.';
            }
        }

        if (empty($errors)) {
            updateUserProfile(currentUserId(), [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
            ]);
            // Update session
            $_SESSION['user_first_name'] = $firstName;
            $_SESSION['user_last_name']  = $lastName;
            $_SESSION['user_email']      = $email;

            setFlash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current  = $_POST['current_password'] ?? '';
        $newPw    = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Verify current password
        $db = getDB();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute([':id' => currentUserId()]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($newPw) < 6) $errors[] = 'New password must be at least 6 characters.';
        if ($newPw !== $confirm) $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            updateUserPassword(currentUserId(), $newPw);
            setFlash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }

    // Re-fetch user after potential update
    $user = getUserById(currentUserId());
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <h1>My Profile</h1>
    <?= renderFlash() ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="profile.php" class="form-card">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="profile">

        <h2>Account Information</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?= e(statusLabel($user['role'])) ?>" disabled>
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>

    <form method="POST" action="profile.php" class="form-card" style="margin-top: 2rem;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="password">

        <h2>Change Password</h2>

        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password (min 6 characters)</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
