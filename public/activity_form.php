<?php
/**
 * Activity Create / Edit form.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Deal.php'; // for getAllLeadsForDropdown

requireLogin();

$id      = (int) ($_GET['id'] ?? 0);
$isEdit  = $id > 0;
$activity = null;
$errors  = [];

if ($isEdit) {
    $activity = getActivityById($id);
    if (!$activity) { setFlash('danger', 'Activity not found.'); redirect('activities.php'); }
    if (!canEditActivity($activity)) { setFlash('danger', 'Access denied.'); redirect('activities.php'); }
} else {
    if (!canCreateActivity()) { setFlash('danger', 'Access denied.'); redirect('activities.php'); }
}

$leadId      = $activity['lead_id'] ?? ($_GET['lead_id'] ?? '');
$type        = $activity['type'] ?? 'task';
$description = $activity['description'] ?? '';
$dueDate     = $activity['due_date'] ?? '';
$actStatus   = $activity['status'] ?? 'pending';

$leads = getAllLeadsForDropdown();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $leadId      = (int) ($_POST['lead_id'] ?? 0);
    $type        = $_POST['type'] ?? 'task';
    $description = trim($_POST['description'] ?? '');
    $dueDate     = $_POST['due_date'] ?? '';
    $actStatus   = $_POST['status'] ?? 'pending';

    if (empty($description)) $errors[] = 'Description is required.';

    if (empty($errors)) {
        $data = [
            'lead_id'     => $leadId,
            'type'        => $type,
            'description' => $description,
            'due_date'    => $dueDate,
            'status'      => $actStatus,
        ];
        if ($isEdit) {
            updateActivity($id, $data);
            setFlash('success', 'Activity updated.');
            redirect('activities.php');
        } else {
            $data['created_by'] = currentUserId();
            createActivity($data);
            setFlash('success', 'Activity created.');
            redirect('activities.php');
        }
    }
}

$pageTitle = $isEdit ? 'Edit Activity' : 'New Activity';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <a href="activities.php" class="btn btn-ghost">&larr; Back</a>
    <h1><?= $isEdit ? 'Edit Activity' : 'New Activity' ?></h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <?php foreach (['call','email','meeting','task','other'] as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= statusLabel($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="lead_id">Related Lead</label>
                <select id="lead_id" name="lead_id">
                    <option value="">— None —</option>
                    <?php foreach ($leads as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $leadId == $l['id'] ? 'selected' : '' ?>><?= e($l['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description" name="description" rows="3" required><?= e($description) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="<?= e($dueDate) ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['pending','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $actStatus === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            <a href="activities.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
