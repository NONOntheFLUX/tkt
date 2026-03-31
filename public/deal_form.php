<?php
/**
 * Deal Create / Edit form.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Deal.php';

requireLogin();

$id     = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$deal   = null;
$errors = [];

if ($isEdit) {
    $deal = getDealById($id);
    if (!$deal) { setFlash('danger', 'Deal not found.'); redirect('deals.php'); }
    if (!canEditDeal($deal)) { setFlash('danger', 'Access denied.'); redirect('deals.php'); }
} else {
    if (!canCreateDeal()) { setFlash('danger', 'Access denied.'); redirect('deals.php'); }
}

$title  = $deal['title'] ?? '';
$leadId = $deal['lead_id'] ?? ($_GET['lead_id'] ?? '');
$amount = $deal['amount'] ?? '';
$leads  = getAllLeadsForDropdown();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $title  = trim($_POST['title'] ?? '');
    $leadId = (int) ($_POST['lead_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);

    if (empty($title)) $errors[] = 'Deal title is required.';
    if ($amount < 0) $errors[] = 'Amount cannot be negative.';

    if (empty($errors)) {
        $data = ['title' => $title, 'lead_id' => $leadId, 'amount' => $amount];
        if ($isEdit) {
            updateDeal($id, $data);
            setFlash('success', 'Deal updated.');
            redirect('deal_detail.php?id=' . $id);
        } else {
            $data['created_by'] = currentUserId();
            $newId = createDeal($data);
            logStatusChange('deal', $newId, null, 'prospecting', currentUserId());
            setFlash('success', 'Deal created.');
            redirect('deal_detail.php?id=' . $newId);
        }
    }
}

$pageTitle = $isEdit ? 'Edit Deal' : 'New Deal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container container-sm">
    <a href="deals.php" class="btn btn-ghost">&larr; Back</a>
    <h1><?= $isEdit ? 'Edit Deal' : 'New Deal' ?></h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="title">Deal Title <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="lead_id">Related Lead</label>
                <select id="lead_id" name="lead_id">
                    <option value="">— None —</option>
                    <?php foreach ($leads as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $leadId == $l['id'] ? 'selected' : '' ?>><?= e($l['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount ($)</label>
                <input type="number" id="amount" name="amount" value="<?= e($amount) ?>" min="0" step="0.01">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            <a href="deals.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
