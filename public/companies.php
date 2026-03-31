<?php
/**
 * Companies list page.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Company.php';

requireLogin();

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$filters = ['search' => $search];
$result  = listCompanies($filters, $perPage, ($page - 1) * $perPage);
$items   = $result['items'];
$pag     = paginate($result['total'], $perPage, $page);

$baseUrl = 'companies.php?' . http_build_query(array_filter(['search' => $search]));

$pageTitle = 'Companies';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Companies</h1>
        <?php if (canCreateCompany()): ?>
            <a href="company_form.php" class="btn btn-primary">+ New Company</a>
        <?php endif; ?>
    </div>

    <?= renderFlash() ?>

    <form method="GET" action="companies.php" class="filter-bar">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search companies..." class="filter-input">
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="companies.php" class="btn btn-ghost">Clear</a>
    </form>

    <?php if (empty($items)): ?>
        <div class="empty-state"><p>No companies found.</p></div>
    <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr><th>Name</th><th>Industry</th><th>Website</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $co): ?>
                <tr>
                    <td><a href="company_detail.php?id=<?= $co['id'] ?>"><?= e($co['name']) ?></a></td>
                    <td><?= e($co['industry'] ?? '—') ?></td>
                    <td><?= $co['website'] ? '<a href="' . e($co['website']) . '" target="_blank">' . e($co['website']) . '</a>' : '—' ?></td>
                    <td><?= formatDate($co['created_at']) ?></td>
                    <td>
                        <?php if (canEditCompany($co)): ?>
                            <a href="company_form.php?id=<?= $co['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
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
