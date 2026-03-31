<?php
/**
 * Notifications Center.
 * List all notifications for the current user with mark read/unread + mark all read.
 */

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Notification.php';

requireLogin();

$userId = currentUserId();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = $_POST['action'] ?? '';
    $notifId = (int) ($_POST['notification_id'] ?? 0);

    switch ($action) {
        case 'mark_read':
            if ($notifId > 0) markNotificationRead($notifId, $userId);
            break;
        case 'mark_unread':
            if ($notifId > 0) markNotificationUnread($notifId, $userId);
            break;
        case 'mark_all_read':
            markAllNotificationsRead($userId);
            setFlash('success', 'All notifications marked as read.');
            break;
        case 'delete':
            if ($notifId > 0) deleteNotification($notifId, $userId);
            break;
    }

    redirect('notifications.php');
}

$perPage = 20;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$total   = countNotificationsForUser($userId);
$pag     = paginate($total, $perPage, $page);
$notifications = getNotificationsForUser($userId, $perPage, $pag['offset']);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <?= renderFlash() ?>

    <div class="page-header">
        <h1>Notifications</h1>
        <?php if (!empty($notifications)): ?>
            <form method="post" style="display:inline">
                <?= csrfField() ?>
                <button type="submit" name="action" value="mark_all_read" class="btn btn-primary">Mark All as Read</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <p>You have no notifications.</p>
        </div>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                    <div class="notif-content">
                        <?php if (!$notif['is_read']): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                        <div class="notif-body">
                            <?php if ($notif['link']): ?>
                                <a href="<?= e($notif['link']) ?>"><?= e($notif['message']) ?></a>
                            <?php else: ?>
                                <span><?= e($notif['message']) ?></span>
                            <?php endif; ?>
                            <small class="notif-time"><?= formatDateTime($notif['created_at']) ?></small>
                        </div>
                    </div>
                    <div class="notif-actions">
                        <form method="post" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                            <?php if ($notif['is_read']): ?>
                                <button type="submit" name="action" value="mark_unread" class="btn btn-sm">Unread</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="mark_read" class="btn btn-sm">Read</button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Delete this notification?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= renderPagination($pag, 'notifications.php?x=1') ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
