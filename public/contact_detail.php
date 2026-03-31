<?php
/**
 * Contact Detail page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Contact.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('contacts.php');

$contact = getContactById($id);
if (!$contact) { setFlash('danger', 'Contact not found.'); redirect('contacts.php'); }

$pageTitle = $contact['first_name'] . ' ' . $contact['last_name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <a href="contacts.php" class="btn btn-ghost">&larr; Back to Contacts</a>
    <div class="page-header">
        <h1><?= e($contact['first_name'] . ' ' . $contact['last_name']) ?></h1>
        <div class="header-actions">
            <?php if (canEditContact($contact)): ?>
                <a href="contact_form.php?id=<?= $id ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
        </div>
    </div>
    <?= renderFlash() ?>

    <div class="card">
        <dl class="detail-list">
            <dt>Email</dt><dd><?= $contact['email'] ? '<a href="mailto:' . e($contact['email']) . '">' . e($contact['email']) . '</a>' : '—' ?></dd>
            <dt>Phone</dt><dd><?= e($contact['phone'] ?? '—') ?></dd>
            <dt>Company</dt><dd><?= $contact['company_name'] ? '<a href="company_detail.php?id=' . $contact['company_id'] . '">' . e($contact['company_name']) . '</a>' : '—' ?></dd>
            <dt>Created By</dt><dd><?= e($contact['creator_name'] ?? '—') ?> on <?= formatDateTime($contact['created_at']) ?></dd>
        </dl>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
