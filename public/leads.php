<?php
/**
 * Leads list - catalog page with search, filter, sort, and pagination.
 * Accessible to all visitors (public).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Category.php';

// Get filter params
$search    = trim($_GET['search'] ?? '');
$status    = trim($_GET['status'] ?? '');
$sort      = trim($_GET['sort'] ?? 'newest');
$catFilter = trim($_GET['category_id'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 10;

$filters = [
    'search'      => $search,
    'status'      => $status,
    'sort'        => $sort,
    'category_id' => $catFilter,
];

$result = listLeads($filters, $perPage, ($page - 1) * $perPage);
$leads  = $result['items'];
$pag    = paginate($result['total'], $perPage, $page);

$categories = listCategories();

// Build base URL for pagination
$queryParams = http_build_query(array_filter([
    'search'      => $search,
    'status'      => $status,
    'sort'        => $sort,
    'category_id' => $catFilter,
]));
$baseUrl = 'leads.php?' . $queryParams;

$pageTitle = 'Leads';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Leads</h1>
        <?php if (canCreateLead()): ?>
            <a href="lead_form.php" class="btn btn-primary">+ New Lead</a>
        <?php endif; ?>
    </div>

    <?= renderFlash() ?>

    <!-- Search & Filter Bar -->
    <form method="GET" action="leads.php" class="filter-bar">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by title or description..." class="filter-input">

        <select name="status" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach (['new','contacted','qualified','won','lost'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="category_id" class="filter-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?> (<?= e($cat['type']) ?>)</option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="newest"   <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest"   <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="title_az" <?= $sort === 'title_az' ? 'selected' : '' ?>>Title A-Z</option>
            <option value="title_za" <?= $sort === 'title_za' ? 'selected' : '' ?>>Title Z-A</option>
            <option value="updated"  <?= $sort === 'updated' ? 'selected' : '' ?>>Recently Updated</option>
        </select>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="leads.php" class="btn btn-ghost">Clear</a>
    </form>

    <p class="result-count"><?= $pag['total'] ?> lead(s) found</p>

    <?php if (empty($leads)): ?>
        <div class="empty-state">
            <p>No leads found matching your criteria.</p>
        </div>
    <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Company</th>
                    <th>Assigned To</th>
                    <th>Category</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leads as $lead): ?>
                <tr>
                    <td><a href="lead_detail.php?id=<?= $lead['id'] ?>"><?= e($lead['title']) ?></a></td>
                    <td><span class="badge <?= statusClass($lead['status']) ?>"><?= statusLabel($lead['status']) ?></span></td>
                    <td><?= e($lead['company_name'] ?? '—') ?></td>
                    <td><?= e($lead['assignee_name'] ?? 'Unassigned') ?></td>
                    <td><?= e($lead['category_name'] ?? '—') ?></td>
                    <td><?= formatDate($lead['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?= renderPagination($pag, $baseUrl) ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
