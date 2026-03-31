<?php
/**
 * Activities list page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Activity.php';

requireLogin();

$type   = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$filters = ['type' => $type, 'status' => $status];
$result  = listActivities($filters, $perPage, ($page - 1) * $perPage);
$items   = $result['items'];
$pag     = paginate($result['total'], $perPage, $page);
$baseUrl = 'activities.php?' . http_build_query(array_filter(['type' => $type, 'status' => $status]));

$pageTitle = 'Activities';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Activities</h1>
        <?php if (canCreateActivity()): ?>
            <a href="activity_form.php" class="btn btn-primary">+ New Activity</a>
        <?php endif; ?>
    </div>
    <?= renderFlash() ?>

    <form method="GET" action="activities.php" class="filter-bar">
        <select name="type" class="filter-select">
            <option value="">All Types</option>
            <?php foreach (['call','email','meeting','task','other'] as $t): ?>
                <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= statusLabel($t) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach (['pending','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="activities.php" class="btn btn-ghost">Clear</a>
    </form>

    <?php if (empty($items)): ?>
        <div class="empty-state"><p>No activities found.</p></div>
    <?php else: ?>
        <table class="table table-hover">
            <thead><tr><th>Type</th><th>Lead</th><th>Description</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $a): ?>
                <tr>
                    <td><?= statusLabel($a['type']) ?></td>
                    <td><?= $a['lead_title'] ? '<a href="lead_detail.php?id=' . $a['lead_id'] . '">' . e($a['lead_title']) . '</a>' : '—' ?></td>
                    <td><?= e(strlen($a['description'] ?? '') > 60 ? substr($a['description'], 0, 60) . '...' : ($a['description'] ?? '')) ?></td>
                    <td><?= formatDate($a['due_date']) ?></td>
                    <td><span class="badge <?= statusClass($a['status']) ?>"><?= statusLabel($a['status']) ?></span></td>
                    <td>
                        <?php if (canEditActivity($a)): ?>
                            <a href="activity_form.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
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