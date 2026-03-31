<?php
/**
 * Deals list page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Deal.php';

requireLogin();

$search = trim($_GET['search'] ?? '');
$stage  = trim($_GET['stage'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$filters = ['search' => $search, 'stage' => $stage];
$result  = listDeals($filters, $perPage, ($page - 1) * $perPage);
$items   = $result['items'];
$pag     = paginate($result['total'], $perPage, $page);
$baseUrl = 'deals.php?' . http_build_query(array_filter(['search' => $search, 'stage' => $stage]));

$pageTitle = 'Deals';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Deals</h1>
        <?php if (canCreateDeal()): ?>
            <a href="deal_form.php" class="btn btn-primary">+ New Deal</a>
        <?php endif; ?>
    </div>
    <?= renderFlash() ?>

    <form method="GET" action="deals.php" class="filter-bar">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search deals..." class="filter-input">
        <select name="stage" class="filter-select">
            <option value="">All Stages</option>
            <?php foreach (['prospecting','proposal','negotiation','won','lost'] as $s): ?>
                <option value="<?= $s ?>" <?= $stage === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="deals.php" class="btn btn-ghost">Clear</a>
    </form>

    <?php if (empty($items)): ?>
        <div class="empty-state"><p>No deals found.</p></div>
    <?php else: ?>
        <table class="table table-hover">
            <thead><tr><th>Title</th><th>Lead</th><th>Amount</th><th>Stage</th><th>Updated</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $d): ?>
                <tr>
                    <td><a href="deal_detail.php?id=<?= $d['id'] ?>"><?= e($d['title'] ?: 'Deal #' . $d['id']) ?></a></td>
                    <td><?= $d['lead_title'] ? '<a href="lead_detail.php?id=' . $d['lead_id'] . '">' . e($d['lead_title']) . '</a>' : '—' ?></td>
                    <td>$<?= number_format($d['amount'], 2) ?></td>
                    <td><span class="badge <?= statusClass($d['stage']) ?>"><?= statusLabel($d['stage']) ?></span></td>
                    <td><?= formatDate($d['updated_at']) ?></td>
                    <td>
                        <?php if (canEditDeal($d)): ?>
                            <a href="deal_form.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
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
