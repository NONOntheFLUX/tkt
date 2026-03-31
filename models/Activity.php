<?php
/**
 * Activity model - CRUD functions.
 */

require_once __DIR__ . '/../config/database.php';

function getActivityById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*,
                l.title AS lead_title,
                CONCAT(u.first_name, " ", u.last_name) AS creator_name
         FROM activities a
         LEFT JOIN leads l ON a.lead_id = l.id
         LEFT JOIN users u ON a.created_by = u.id
         WHERE a.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function listActivities(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $db = getDB();
    $where  = [];
    $params = [];

    if (!empty($filters['lead_id'])) {
        $where[]          = 'a.lead_id = :lid';
        $params[':lid']   = (int) $filters['lead_id'];
    }
    if (!empty($filters['type'])) {
        $where[]          = 'a.type = :type';
        $params[':type']  = $filters['type'];
    }
    if (!empty($filters['status'])) {
        $where[]            = 'a.status = :status';
        $params[':status']  = $filters['status'];
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT COUNT(*) FROM activities a {$whereSQL}");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $sql = "SELECT a.*,
                   l.title AS lead_title,
                   CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM activities a
            LEFT JOIN leads l ON a.lead_id = l.id
            LEFT JOIN users u ON a.created_by = u.id
            {$whereSQL}
            ORDER BY a.due_date ASC, a.created_at DESC
            LIMIT :lim OFFSET :off";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

function createActivity(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO activities (lead_id, type, description, due_date, status, created_by)
         VALUES (:lid, :type, :desc, :due, :status, :cb)'
    );
    $stmt->execute([
        ':lid'    => $data['lead_id'] ?: null,
        ':type'   => $data['type'] ?? 'task',
        ':desc'   => $data['description'] ?? null,
        ':due'    => $data['due_date'] ?: null,
        ':status' => $data['status'] ?? 'pending',
        ':cb'     => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

function updateActivity(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE activities SET lead_id = :lid, type = :type, description = :desc, due_date = :due, status = :status WHERE id = :id'
    );
    $stmt->execute([
        ':lid'    => $data['lead_id'] ?: null,
        ':type'   => $data['type'] ?? 'task',
        ':desc'   => $data['description'] ?? null,
        ':due'    => $data['due_date'] ?: null,
        ':status' => $data['status'] ?? 'pending',
        ':id'     => $id,
    ]);
}

function deleteActivity(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM activities WHERE id = :id')->execute([':id' => $id]);
}

function getActivitiesByLead(int $leadId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS creator_name
         FROM activities a
         LEFT JOIN users u ON a.created_by = u.id
         WHERE a.lead_id = :lid
         ORDER BY a.due_date ASC'
    );
    $stmt->execute([':lid' => $leadId]);
    return $stmt->fetchAll();
}

function getUpcomingActivities(int $userId, int $limit = 5): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, l.title AS lead_title
         FROM activities a
         LEFT JOIN leads l ON a.lead_id = l.id
         WHERE a.created_by = :uid AND a.status = "pending"
         ORDER BY a.due_date ASC
         LIMIT :lim'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
