<?php
/**
 * User Dashboard - shows summary of user's leads, activities, and stats.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Activity.php';

requireLogin();

$user = currentUser();
$uid  = currentUserId();

// Fetch user-specific data
$myLeads       = getLeadsByCreator($uid, 5);
$assignedLeads = getLeadsByAssignee($uid, 5);
$upcoming      = getUpcomingActivities($uid, 5);

// Stats for managers/admins
$showStats     = hasMinRole('sales_manager');
$leadCounts    = $showStats ? countLeadsByStatus() : [];
$dealStats     = $showStats ? countDealsByStage() : [];
$totalPipeline = $showStats ? totalDealValue() : 0;

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Dashboard</h1>
    <?= renderFlash() ?>

    <p class="welcome-text">Welcome back, <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
       (<?= e(statusLabel($user['role'])) ?>)</p>

    <?php if ($showStats): ?>
    <section class="stats-grid">
        <div class="stat-card">
            <h3>Total Pipeline</h3>
            <p class="stat-number">$<?= number_format($totalPipeline, 2) ?></p>
        </div>
        <div class="stat-card">
            <h3>New Leads</h3>
            <p class="stat-number"><?= $leadCounts['new'] ?? 0 ?></p>
        </div>
        <div class="stat-card">
            <h3>Qualified Leads</h3>
            <p class="stat-number"><?= $leadCounts['qualified'] ?? 0 ?></p>
        </div>
        <div class="stat-card">
            <h3>Won Deals</h3>
            <p class="stat-number"><?= ($dealStats['won']['count'] ?? 0) ?></p>
        </div>
    </section>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- My Leads -->
        <section class="dash-section">
            <div class="section-header">
                <h2>My Leads</h2>
                <a href="leads.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <?php if (empty($myLeads)): ?>
                <p class="text-muted">No leads created yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Title</th><th>Status</th><th>Company</th></tr></thead>
                    <tbody>
                    <?php foreach ($myLeads as $lead): ?>
                        <tr>
                            <td><a href="lead_detail.php?id=<?= $lead['id'] ?>"><?= e($lead['title']) ?></a></td>
                            <td><span class="badge <?= statusClass($lead['status']) ?>"><?= statusLabel($lead['status']) ?></span></td>
                            <td><?= e($lead['company_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Assigned Leads -->
        <section class="dash-section">
            <div class="section-header">
                <h2>Assigned to Me</h2>
            </div>
            <?php if (empty($assignedLeads)): ?>
                <p class="text-muted">No leads assigned to you.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Title</th><th>Status</th><th>Company</th></tr></thead>
                    <tbody>
                    <?php foreach ($assignedLeads as $lead): ?>
                        <tr>
                            <td><a href="lead_detail.php?id=<?= $lead['id'] ?>"><?= e($lead['title']) ?></a></td>
                            <td><span class="badge <?= statusClass($lead['status']) ?>"><?= statusLabel($lead['status']) ?></span></td>
                            <td><?= e($lead['company_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Upcoming Activities -->
        <section class="dash-section">
            <div class="section-header">
                <h2>Upcoming Activities</h2>
                <a href="activities.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <?php if (empty($upcoming)): ?>
                <p class="text-muted">No upcoming activities.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Type</th><th>Lead</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $act): ?>
                        <tr>
                            <td><?= statusLabel($act['type']) ?></td>
                            <td><a href="lead_detail.php?id=<?= $act['lead_id'] ?>"><?= e($act['lead_title'] ?? '—') ?></a></td>
                            <td><?= formatDate($act['due_date']) ?></td>
                            <td><span class="badge <?= statusClass($act['status']) ?>"><?= statusLabel($act['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
