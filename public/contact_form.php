<?php
/**
 * Contact Create / Edit form.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Company.php';

requireLogin();

$id      = (int) ($_GET['id'] ?? 0);
$isEdit  = $id > 0;
$contact = null;
$errors  = [];

if ($isEdit) {
    $contact = getContactById($id);
    if (!$contact) { setFlash('danger', 'Contact not found.'); redirect('contacts.php'); }
    if (!canEditContact($contact)) { setFlash('danger', 'Access denied.'); redirect('contacts.php'); }
} else {
    if (!canCreateContact()) { setFlash('danger', 'Access denied.'); redirect('contacts.php'); }
}

$firstName = $contact['first_name'] ?? '';
$lastName  = $contact['last_name'] ?? '';
$email     = $contact['email'] ?? '';
$phone     = $contact['phone'] ?? '';
$companyId = $contact['company_id'] ?? ($_GET['company_id'] ?? '');

$companies = getAllCompanies();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $companyId = (int) ($_POST['company_id'] ?? 0);

    if (empty($firstName)) $errors[] = 'First name is required.';
    if (empty($lastName))  $errors[] = 'Last name is required.';

    if (empty($errors)) {
        $data = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'phone' => $phone, 'company_id' => $companyId];
        if ($isEdit) {
            updateContact($id, $data);
            setFlash('success', 'Contact updated.');
            redirect('contact_detail.php?id=' . $id);
        } else {
            $data['created_by'] = currentUserId();
            $newId = createContact($data);
            setFlash('success', 'Contact created.');
            redirect('contact_detail.php?id=' . $newId);
        }
    }
}

$pageTitle = $isEdit ? 'Edit Contact' : 'New Contact';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <a href="contacts.php" class="btn btn-ghost">&larr; Back</a>
    <h1><?= $isEdit ? 'Edit Contact' : 'New Contact' ?></h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= e($firstName) ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= e($lastName) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= e($phone) ?>">
        </div>
        <div class="form-group">
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id">
                <option value="">— None —</option>
                <?php foreach ($companies as $co): ?>
                    <option value="<?= $co['id'] ?>" <?= $companyId == $co['id'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            <a href="contacts.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
