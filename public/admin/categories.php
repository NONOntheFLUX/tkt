<?php
/**
 * Admin: Manage Categories / Industries / Tags.
 * List, create, edit, delete reference data.
 */

$pageTitle = 'Admin - Categories';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../models/Category.php';

requireRole('admin');

$errors = [];

// Handle POST: create or edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action  = $_POST['form_action'] ?? '';
    $catName = trim($_POST['name'] ?? '');
    $catType = trim($_POST['type'] ?? 'industry');

    if ($catName === '') {
        $errors[] = 'Category name is required.';
    }

    $validTypes = ['industry', 'tag'];
    if (!in_array($catType, $validTypes)) {
        $errors[] = 'Invalid category type.';
    }

    if (empty($errors)) {
        if ($action === 'create') {
            createCategory($catName, $catType);
            setFlash('success', 'Category created.');
            redirect('categories.php');
        } elseif ($action === 'edit') {
            $catId = (int) ($_POST['category_id'] ?? 0);
            if ($catId > 0) {
                updateCategory($catId, $catName, $catType);
                setFlash('success', 'Category updated.');
                redirect('categories.php');
            }
        } elseif ($action === 'delete') {
            $catId = (int) ($_POST['category_id'] ?? 0);
            if ($catId > 0) {
                deleteCategory($catId);
                setFlash('success', 'Category deleted.');
                redirect('categories.php');
            }
        }
    }
}

// Handle delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    // Already handled above
}

$categories = listCategories();

// Check if we are editing
$editCat = null;
if (isset($_GET['edit'])) {
    $editCat = getCategoryById((int) $_GET['edit']);
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <?= renderFlash() ?>

    <div class="page-header">
        <h1>Categories &amp; Tags</h1>
        <a href="users.php" class="btn">User Management</a>
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

    <!-- Create / Edit Form -->
    <div class="form-card" style="margin-bottom: 2rem;">
        <h3><?= $editCat ? 'Edit Category' : 'Add New Category' ?></h3>
        <form method="post" class="form-inline-row">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="<?= $editCat ? 'edit' : 'create' ?>">
            <?php if ($editCat): ?>
                <input type="hidden" name="category_id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($editCat['name'] ?? '') ?>" placeholder="e.g. Technology">
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="industry" <?= ($editCat['type'] ?? '') === 'industry' ? 'selected' : '' ?>>Industry</option>
                    <option value="tag"      <?= ($editCat['type'] ?? '') === 'tag' ? 'selected' : '' ?>>Tag</option>
                </select>
            </div>

            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary"><?= $editCat ? 'Update' : 'Add' ?></button>
                <?php if ($editCat): ?>
                    <a href="categories.php" class="btn">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="text-center">No categories found.</td></tr>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= e($cat['name']) ?></td>
                <td><span class="badge badge-secondary"><?= statusLabel($cat['type']) ?></span></td>
                <td><?= formatDate($cat['created_at']) ?></td>
                <td class="actions">
                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-sm">Edit</a>
                    <form method="post" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
