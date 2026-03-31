<?php
/**
 * Deal Detail page - with stage change.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Note.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('deals.php');

$deal = getDealById($id);
if (!$deal) { setFlash('danger', 'Deal not found.'); redirect('deals.php'); }

$errors = [];

// Handle stage change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_stage') {
        $newStage = $_POST['new_stage'] ?? '';
        if (!canEditDeal($deal)) {
            $errors[] = 'Permission denied.';
        } elseif (!isValidDealTransition($deal['stage'], $newStage)) {
            $errors[] = 'Invalid stage transition from "' . statusLabel($deal['stage']) . '" to "' . statusLabel($newStage) . '".';
        } else {
            $oldStage = $deal['stage'];
            updateDealStage($id, $newStage);
            logStatusChange('deal', $id, $oldStage, $newStage, currentUserId());
            setFlash('success', 'Deal stage changed to "' . statusLabel($newStage) . '".');
            redirect('deal_detail.php?id=' . $id);
        }
    }

    $deal = getDealById($id);
}

$transitions   = allowedDealTransitions($deal['stage']);
$notes         = getNotesByObject('deal', $id);
$statusHistory = getStatusHistory('deal', $id);

$pageTitle = $deal['title'] ?: 'Deal #' . $deal['id'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <a href="deals.php" class="btn btn-ghost">&larr; Back to Deals</a>
    <div class="page-header">
        <h1><?= e($deal['title'] ?: 'Deal #' . $deal['id']) ?></h1>
        <div class="header-actions">
            <?php if (canEditDeal($deal)): ?>
                <a href="deal_form.php?id=<?= $id ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
            <?php if (canDeleteDeal($deal)): ?>
                <a href="deal_delete.php?id=<?= $id ?>" class="btn btn-danger"
                   onclick="return confirm('Delete this deal?');">Delete</a>
            <?php endif; ?>
        </div>
    </div>
    <?= renderFlash() ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="detail-grid">
        <div class="detail-main">
            <div class="card">
                <h2>Deal Details</h2>
                <dl class="detail-list">
                    <dt>Stage</dt>
                    <dd><span class="badge <?= statusClass($deal['stage']) ?>"><?= statusLabel($deal['stage']) ?></span></dd>
                    <dt>Amount</dt><dd>$<?= number_format($deal['amount'], 2) ?></dd>
                    <dt>Related Lead</dt>
                    <dd><?= $deal['lead_title'] ? '<a href="lead_detail.php?id=' . $deal['lead_id'] . '">' . e($deal['lead_title']) . '</a>' : '—' ?></dd>
                    <dt>Created By</dt><dd><?= e($deal['creator_name'] ?? '—') ?> on <?= formatDateTime($deal['created_at']) ?></dd>
                    <dt>Last Updated</dt><dd><?= formatDateTime($deal['updated_at']) ?></dd>
                </dl>
            </div>

            <?php if (canEditDeal($deal) && !empty($transitions)): ?>
            <div class="card">
                <h2>Change Stage</h2>
                <form method="POST" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_stage">
                    <select name="new_stage" class="filter-select" required>
                        <option value="">Select new stage...</option>
                        <?php foreach ($transitions as $t): ?>
                            <option value="<?= $t ?>"><?= statusLabel($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Update Stage</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-sidebar">
            <div class="card">
                <h3>Stage History</h3>
                <?php if (empty($statusHistory)): ?>
                    <p class="text-muted">No changes recorded.</p>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($statusHistory as $h): ?>
                            <div class="history-item">
                                <span class="badge <?= statusClass($h['old_status'] ?? 'prospecting') ?>"><?= statusLabel($h['old_status'] ?? '—') ?></span>
                                &rarr;
                                <span class="badge <?= statusClass($h['new_status']) ?>"><?= statusLabel($h['new_status']) ?></span>
                                <br>
                                <small class="text-muted">by <?= e($h['changed_by_name'] ?? 'System') ?> on <?= formatDateTime($h['changed_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
