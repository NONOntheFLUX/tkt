<?php
/**
 * Lead Detail - view full lead with notes, activities, deals, status history.
 * Also handles adding notes and changing status.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/User.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { redirect('leads.php'); }

$lead = getLeadById($id);
if (!$lead) {
    setFlash('danger', 'Lead not found.');
    redirect('leads.php');
}

$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    // Add Note
    if ($action === 'add_note') {
        $content = trim($_POST['note_content'] ?? '');
        if (empty($content)) {
            $errors[] = 'Note content cannot be empty.';
        } elseif (!canAddNote()) {
            $errors[] = 'You do not have permission to add notes.';
        } else {
            createNote([
                'object_type' => 'lead',
                'object_id'   => $id,
                'content'     => $content,
                'created_by'  => currentUserId(),
            ]);
            // Notify lead owner and assignee
            $notifyUsers = array_unique(array_filter([$lead['created_by'], $lead['assigned_to']]));
            foreach ($notifyUsers as $notifyUid) {
                if ($notifyUid != currentUserId()) {
                    $author = currentUser();
                    createNotification(
                        $notifyUid,
                        ($author['first_name'] ?? $author['name'] ?? 'A user') . ' added a note on lead: ' . $lead['title'],
                        'lead_detail.php?id=' . $id
                    );
                }
            }
            setFlash('success', 'Note added successfully.');
            redirect('lead_detail.php?id=' . $id);
        }
    }

    // Change Status
    if ($action === 'change_status') {
        $newStatus = $_POST['new_status'] ?? '';
        if (!canChangeLeadStatus($lead)) {
            $errors[] = 'You do not have permission to change this lead\'s status.';
        } elseif (!isValidLeadTransition($lead['status'], $newStatus)) {
            $errors[] = 'Invalid status transition from "' . statusLabel($lead['status']) . '" to "' . statusLabel($newStatus) . '".';
        } else {
            $oldStatus = $lead['status'];
            updateLeadStatus($id, $newStatus);
            logStatusChange('lead', $id, $oldStatus, $newStatus, currentUserId());

            // Notify relevant users
            $notifyUsers = array_unique(array_filter([$lead['created_by'], $lead['assigned_to']]));
            foreach ($notifyUsers as $notifyUid) {
                if ($notifyUid != currentUserId()) {
                    createNotification(
                        $notifyUid,
                        'Lead "' . $lead['title'] . '" status changed from ' . statusLabel($oldStatus) . ' to ' . statusLabel($newStatus),
                        'lead_detail.php?id=' . $id
                    );
                }
            }

            setFlash('success', 'Status changed to "' . statusLabel($newStatus) . '".');
            redirect('lead_detail.php?id=' . $id);
        }
    }

    // Refresh lead data after changes
    $lead = getLeadById($id);
}

// Fetch related data
$notes         = getNotesByObject('lead', $id);
$deals         = getDealsByLead($id);
$activities    = getActivitiesByLead($id);
$statusHistory = getStatusHistory('lead', $id);
$transitions   = isLoggedIn() ? allowedLeadTransitions($lead['status']) : [];

$pageTitle = $lead['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <a href="leads.php" class="btn btn-ghost">&larr; Back to Leads</a>
            <h1><?= e($lead['title']) ?></h1>
        </div>
        <div class="header-actions">
            <?php if (isLoggedIn() && canEditLead($lead)): ?>
                <a href="lead_form.php?id=<?= $id ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
            <?php if (isLoggedIn() && canDeleteLead($lead)): ?>
                <a href="lead_delete.php?id=<?= $id ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');">Delete</a>
            <?php endif; ?>
        </div>
    </div>

    <?= renderFlash() ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="detail-grid">
        <div class="detail-main">
            <div class="card">
                <h2>Lead Details</h2>
                <dl class="detail-list">
                    <dt>Status</dt>
                    <dd><span class="badge <?= statusClass($lead['status']) ?>"><?= statusLabel($lead['status']) ?></span></dd>

                    <dt>Description</dt>
                    <dd><?= $lead['description'] ? nl2br(e($lead['description'])) : '<em>No description</em>' ?></dd>

                    <dt>Company</dt>
                    <dd><?= $lead['company_name'] ? '<a href="company_detail.php?id=' . $lead['company_id'] . '">' . e($lead['company_name']) . '</a>' : '—' ?></dd>

                    <dt>Contact</dt>
                    <dd><?= $lead['contact_name'] ? '<a href="contact_detail.php?id=' . $lead['contact_id'] . '">' . e($lead['contact_name']) . '</a>' : '—' ?></dd>

                    <dt>Assigned To</dt>
                    <dd><?= e($lead['assignee_name'] ?? 'Unassigned') ?></dd>

                    <dt>Category</dt>
                    <dd><?= e($lead['category_name'] ?? '—') ?></dd>

                    <dt>Created By</dt>
                    <dd><?= e($lead['creator_name'] ?? '—') ?> on <?= formatDateTime($lead['created_at']) ?></dd>

                    <dt>Last Updated</dt>
                    <dd><?= formatDateTime($lead['updated_at']) ?></dd>
                </dl>
            </div>

            <?php if (isLoggedIn() && canChangeLeadStatus($lead) && !empty($transitions)): ?>
            <div class="card">
                <h2>Change Status</h2>
                <form method="POST" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_status">
                    <select name="new_status" class="filter-select" required>
                        <option value="">Select new status...</option>
                        <?php foreach ($transitions as $t): ?>
                            <option value="<?= $t ?>"><?= statusLabel($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2>Notes &amp; Comments (<?= count($notes) ?>)</h2>

                <?php if (isLoggedIn() && canAddNote()): ?>
                <form method="POST" class="note-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_note">
                    <div class="form-group">
                        <textarea name="note_content" rows="3" placeholder="Add a note or comment..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
                </form>
                <?php endif; ?>

                <?php if (empty($notes)): ?>
                    <p class="text-muted">No notes yet.</p>
                <?php else: ?>
                    <div class="notes-list">
                        <?php foreach ($notes as $note): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <strong><?= e($note['author_name']) ?></strong>
                                    <span class="text-muted"><?= formatDateTime($note['created_at']) ?></span>
                                </div>
                                <div class="note-body"><?= nl2br(e($note['content'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-sidebar">
            <div class="card">
                <div class="section-header">
                    <h3>Deals (<?= count($deals) ?>)</h3>
                    <?php if (isLoggedIn() && canCreateDeal()): ?>
                        <a href="deal_form.php?lead_id=<?= $id ?>" class="btn btn-sm btn-secondary">+ Add</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($deals)): ?>
                    <p class="text-muted">No deals yet.</p>
                <?php else: ?>
                    <?php foreach ($deals as $deal): ?>
                        <div class="mini-card">
                            <a href="deal_detail.php?id=<?= $deal['id'] ?>"><?= e($deal['title'] ?: 'Deal #' . $deal['id']) ?></a>
                            <span class="badge <?= statusClass($deal['stage']) ?>"><?= statusLabel($deal['stage']) ?></span>
                            <span>$<?= number_format($deal['amount'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="section-header">
                    <h3>Activities (<?= count($activities) ?>)</h3>
                    <?php if (isLoggedIn() && canCreateActivity()): ?>
                        <a href="activity_form.php?lead_id=<?= $id ?>" class="btn btn-sm btn-secondary">+ Add</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($activities)): ?>
                    <p class="text-muted">No activities yet.</p>
                <?php else: ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="mini-card">
                            <strong><?= statusLabel($act['type']) ?></strong>
                            <span class="badge <?= statusClass($act['status']) ?>"><?= statusLabel($act['status']) ?></span>
                            <span class="text-muted"><?= formatDate($act['due_date']) ?></span>
                            <p><?= e(strlen($act['description'] ?? '') > 80 ? substr($act['description'], 0, 80) . '...' : ($act['description'] ?? '')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Status History</h3>
                <?php if (empty($statusHistory)): ?>
                    <p class="text-muted">No status changes recorded.</p>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($statusHistory as $h): ?>
                            <div class="history-item">
                                <span class="badge <?= statusClass($h['old_status'] ?? 'new') ?>"><?= statusLabel($h['old_status'] ?? '—') ?></span>
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
