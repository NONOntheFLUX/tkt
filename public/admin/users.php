<?php
/**
 * Admin: User Management
 * List all users, edit roles, toggle active status, create new users.
 */

$pageTitle = 'Admin - Users';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../models/User.php';

// Only admins can access
requireRole('admin');

$perPage = 20;
$page    = max(1, (int) ($_GET['page'] ?? 1));

$result = listUsers($perPage, ($page - 1) * $perPage);
$users  = $result['items'];
$pag    = paginate($result['total'], $perPage, $page);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <?= renderFlash() ?>

    <div class="page-header">
        <h1>User Management</h1>
        <a href="user_form.php" class="btn btn-primary">+ Create User</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="7" class="text-center">No users found.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge-secondary"><?= statusLabel($u['role']) ?></span></td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Disabled</span>
                    <?php endif; ?>
                </td>
                <td><?= formatDate($u['created_at']) ?></td>
                <td class="actions">
                    <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm">Edit</a>
                    <?php if ($u['id'] !== currentUserId()): ?>
                        <form method="post" action="user_toggle.php" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if ($u['is_active']): ?>
                                <button type="submit" name="action" value="disable" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Disable this user?')">Disable</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="enable" class="btn btn-sm btn-success">Enable</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?= renderPagination($pag, 'users.php?x=1') ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
