<?php
/**
 * Contacts list page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Contact.php';

requireLogin();

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$result = listContacts(['search' => $search], $perPage, ($page - 1) * $perPage);
$items  = $result['items'];
$pag    = paginate($result['total'], $perPage, $page);
$baseUrl = 'contacts.php?' . http_build_query(array_filter(['search' => $search]));

$pageTitle = 'Contacts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Contacts</h1>
        <?php if (canCreateContact()): ?>
            <a href="contact_form.php" class="btn btn-primary">+ New Contact</a>
        <?php endif; ?>
    </div>
    <?= renderFlash() ?>

    <form method="GET" action="contacts.php" class="filter-bar">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search contacts..." class="filter-input">
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="contacts.php" class="btn btn-ghost">Clear</a>
    </form>

    <?php if (empty($items)): ?>
        <div class="empty-state"><p>No contacts found.</p></div>
    <?php else: ?>
        <table class="table table-hover">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $ct): ?>
                <tr>
                    <td><a href="contact_detail.php?id=<?= $ct['id'] ?>"><?= e($ct['first_name'] . ' ' . $ct['last_name']) ?></a></td>
                    <td><?= e($ct['email'] ?? '—') ?></td>
                    <td><?= e($ct['phone'] ?? '—') ?></td>
                    <td><?= e($ct['company_name'] ?? '—') ?></td>
                    <td>
                        <?php if (canEditContact($ct)): ?>
                            <a href="contact_form.php?id=<?= $ct['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?= renderPagination($pag, $baseUrl) ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
