<?php
/**
 * Company Create / Edit form.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Company.php';

requireLogin();

$id     = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$company = null;
$errors  = [];

if ($isEdit) {
    $company = getCompanyById($id);
    if (!$company) { setFlash('danger', 'Company not found.'); redirect('companies.php'); }
    if (!canEditCompany($company)) { setFlash('danger', 'Access denied.'); redirect('companies.php'); }
} else {
    if (!canCreateCompany()) { setFlash('danger', 'Access denied.'); redirect('companies.php'); }
}

$name     = $company['name'] ?? '';
$industry = $company['industry'] ?? '';
$website  = $company['website'] ?? '';
$address  = $company['address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name     = trim($_POST['name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $website  = trim($_POST['website'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if (empty($name)) $errors[] = 'Company name is required.';

    if (empty($errors)) {
        $data = ['name' => $name, 'industry' => $industry, 'website' => $website, 'address' => $address];
        if ($isEdit) {
            updateCompany($id, $data);
            setFlash('success', 'Company updated.');
            redirect('company_detail.php?id=' . $id);
        } else {
            $data['created_by'] = currentUserId();
            $newId = createCompany($data);
            setFlash('success', 'Company created.');
            redirect('company_detail.php?id=' . $newId);
        }
    }
}

$pageTitle = $isEdit ? 'Edit Company' : 'New Company';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <a href="companies.php" class="btn btn-ghost">&larr; Back</a>
    <h1><?= $isEdit ? 'Edit Company' : 'New Company' ?></h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" required>
        </div>
        <div class="form-group">
            <label for="industry">Industry</label>
            <input type="text" id="industry" name="industry" value="<?= e($industry) ?>">
        </div>
        <div class="form-group">
            <label for="website">Website</label>
            <input type="url" id="website" name="website" value="<?= e($website) ?>" placeholder="https://...">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2"><?= e($address) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            <a href="companies.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
