<?php
/**
 * Lead Create / Edit form.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();

$id     = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$lead   = null;
$errors = [];

if ($isEdit) {
    $lead = getLeadById($id);
    if (!$lead) {
        setFlash('danger', 'Lead not found.');
        redirect('leads.php');
    }
    if (!canEditLead($lead)) {
        setFlash('danger', 'You do not have permission to edit this lead.');
        redirect('leads.php');
    }
} else {
    if (!canCreateLead()) {
        setFlash('danger', 'You do not have permission to create leads.');
        redirect('leads.php');
    }
}

// Form defaults
$title       = $lead['title'] ?? '';
$description = $lead['description'] ?? '';
$companyId   = $lead['company_id'] ?? '';
$contactId   = $lead['contact_id'] ?? '';
$assignedTo  = $lead['assigned_to'] ?? '';
$categoryId  = $lead['category_id'] ?? '';

// Dropdown data
$companies  = getAllCompanies();
$contacts   = getAllContacts();
$categories = listCategories();
$assignable = getAssignableUsers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $companyId   = (int) ($_POST['company_id'] ?? 0);
    $contactId   = (int) ($_POST['contact_id'] ?? 0);
    $assignedTo  = (int) ($_POST['assigned_to'] ?? 0);
    $categoryId  = (int) ($_POST['category_id'] ?? 0);

    // Validation
    if (empty($title)) $errors[] = 'Title is required.';
    if (strlen($title) > 200) $errors[] = 'Title must be 200 characters or less.';

    // Only managers/admins can assign
    if ($assignedTo && !canAssignLead()) {
        // Keep current assignment or clear
        $assignedTo = $lead['assigned_to'] ?? 0;
    }

    if (empty($errors)) {
        $data = [
            'title'       => $title,
            'description' => $description,
            'company_id'  => $companyId,
            'contact_id'  => $contactId,
            'assigned_to' => $assignedTo,
            'category_id' => $categoryId,
        ];

        if ($isEdit) {
            $oldAssigned = $lead['assigned_to'];
            updateLead($id, $data);

            // Notify if assignment changed
            if ($assignedTo && $assignedTo != $oldAssigned) {
                createNotification(
                    $assignedTo,
                    'You have been assigned to lead: ' . $title,
                    'lead_detail.php?id=' . $id
                );
            }

            setFlash('success', 'Lead updated successfully.');
            redirect('lead_detail.php?id=' . $id);
        } else {
            $data['created_by'] = currentUserId();
            $newId = createLead($data);

            // Log initial status
            logStatusChange('lead', $newId, null, 'new', currentUserId());

            // Notify assignee
            if ($assignedTo) {
                createNotification(
                    $assignedTo,
                    'You have been assigned to lead: ' . $title,
                    'lead_detail.php?id=' . $newId
                );
            }

            setFlash('success', 'Lead created successfully.');
            redirect('lead_detail.php?id=' . $newId);
        }
    }
}

$pageTitle = $isEdit ? 'Edit Lead' : 'New Lead';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <a href="leads.php" class="btn btn-ghost">&larr; Back to Leads</a>
    <h1><?= $isEdit ? 'Edit Lead' : 'Create New Lead' ?></h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="title">Title <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required maxlength="200">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= e($description) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_id">Company</label>
                <select id="company_id" name="company_id">
                    <option value="">— None —</option>
                    <?php foreach ($companies as $co): ?>
                        <option value="<?= $co['id'] ?>" <?= $companyId == $co['id'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="contact_id">Contact</label>
                <select id="contact_id" name="contact_id">
                    <option value="">— None —</option>
                    <?php foreach ($contacts as $ct): ?>
                        <option value="<?= $ct['id'] ?>" <?= $contactId == $ct['id'] ? 'selected' : '' ?>><?= e($ct['first_name'] . ' ' . $ct['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category / Tag</label>
                <select id="category_id" name="category_id">
                    <option value="">— None —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?> (<?= e($cat['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (canAssignLead()): ?>
            <div class="form-group">
                <label for="assigned_to">Assign To</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($assignable as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $assignedTo == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['first_name'] . ' ' . $u['last_name']) ?> (<?= e($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Lead' : 'Create Lead' ?></button>
            <a href="leads.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
