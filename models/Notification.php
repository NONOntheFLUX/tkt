<?php
/**
 * Notification model - list, mark read/unread.
 */

require_once __DIR__ . '/../config/database.php';

function getNotificationsForUser(int $userId, int $limit = 50, int $offset = 0): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM notifications
         WHERE user_id = :uid
         ORDER BY created_at DESC
         LIMIT :lim OFFSET :off'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countNotificationsForUser(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

function markNotificationRead(int $id, int $userId): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $userId]);
}

function markNotificationUnread(int $id, int $userId): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 0 WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $userId]);
}

function markAllNotificationsRead(int $userId): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
}

function deleteNotification(int $id, int $userId): void
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $userId]);
}
