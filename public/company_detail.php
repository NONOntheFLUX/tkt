<?php
/**
 * Company Detail page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Contact.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('companies.php');

$company = getCompanyById($id);
if (!$company) { setFlash('danger', 'Company not found.'); redirect('companies.php'); }

$contacts = getContactsByCompany($id);

$pageTitle = $company['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <a href="companies.php" class="btn btn-ghost">&larr; Back to Companies</a>
    <div class="page-header">
        <h1><?= e($company['name']) ?></h1>
        <div class="header-actions">
            <?php if (canEditCompany($company)): ?>
                <a href="company_form.php?id=<?= $id ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
        </div>
    </div>
    <?= renderFlash() ?>

    <div class="card">
        <dl class="detail-list">
            <dt>Industry</dt><dd><?= e($company['industry'] ?? '—') ?></dd>
            <dt>Website</dt><dd><?= $company['website'] ? '<a href="' . e($company['website']) . '" target="_blank">' . e($company['website']) . '</a>' : '—' ?></dd>
            <dt>Address</dt><dd><?= $company['address'] ? nl2br(e($company['address'])) : '—' ?></dd>
            <dt>Created By</dt><dd><?= e($company['creator_name'] ?? '—') ?> on <?= formatDateTime($company['created_at']) ?></dd>
        </dl>
    </div>

    <div class="card">
        <div class="section-header">
            <h2>Contacts (<?= count($contacts) ?>)</h2>
            <a href="contact_form.php?company_id=<?= $id ?>" class="btn btn-sm btn-secondary">+ Add Contact</a>
        </div>
        <?php if (empty($contacts)): ?>
            <p class="text-muted">No contacts for this company.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                <?php foreach ($contacts as $ct): ?>
                    <tr>
                        <td><a href="contact_detail.php?id=<?= $ct['id'] ?>"><?= e($ct['first_name'] . ' ' . $ct['last_name']) ?></a></td>
                        <td><?= e($ct['email'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
