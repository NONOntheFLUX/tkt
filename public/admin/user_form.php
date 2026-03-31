<?php
/**
 * Admin: Create / Edit a user.
 */

$pageTitle = 'Admin - User Form';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../models/User.php';

requireRole('admin');

$editId = (int) ($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$user   = $isEdit ? getUserById($editId) : null;
$errors = [];

if ($isEdit && !$user) {
    setFlash('danger', 'User not found.');
    redirect('users.php');
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $role      = $_POST['role']            ?? 'user';
    $password  = $_POST['password']        ?? '';

    // Validate
    if ($firstName === '') $errors[] = 'First name is required.';
    if ($lastName  === '') $errors[] = 'Last name is required.';
    if ($email     === '') $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    $validRoles = ['user', 'sales_rep', 'sales_manager', 'admin'];
    if (!in_array($role, $validRoles)) $errors[] = 'Invalid role selected.';

    if (!$isEdit && $password === '') $errors[] = 'Password is required for new users.';
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        if ($isEdit) {
            updateUserProfile($editId, [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
            ]);
            updateUserRole($editId, $role);
            if ($password !== '') {
                updateUserPassword($editId, $password);
            }
            setFlash('success', 'User updated successfully.');
            redirect('users.php');
        } else {
            $result = adminCreateUser([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'password'   => $password,
                'role'       => $role,
            ]);
            if (is_int($result)) {
                setFlash('success', 'User created successfully.');
                redirect('users.php');
            } else {
                $errors[] = $result;
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= $isEdit ? 'Edit User' : 'Create User' ?></h1>
        <a href="users.php" class="btn">Back to Users</a>
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

    <form method="post" class="form-card">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" required
                   value="<?= e($user['first_name'] ?? ($_POST['first_name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required
                   value="<?= e($user['last_name'] ?? ($_POST['last_name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required
                   value="<?= e($user['email'] ?? ($_POST['email'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role">
                <?php
                $roles = ['user' => 'User', 'sales_rep' => 'Sales Rep', 'sales_manager' => 'Sales Manager', 'admin' => 'Admin'];
                $currentRole = $user['role'] ?? ($_POST['role'] ?? 'user');
                foreach ($roles as $val => $label):
                ?>
                    <option value="<?= $val ?>" <?= $currentRole === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Password <?= $isEdit ? '(leave blank to keep current)' : '*' ?></label>
            <input type="password" id="password" name="password" <?= $isEdit ? '' : 'required' ?>
                   minlength="6" placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Min. 6 characters' ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update User' : 'Create User' ?></button>
            <a href="users.php" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
