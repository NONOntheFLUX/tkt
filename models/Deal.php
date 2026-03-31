<?php
/**
 * Deal model - CRUD functions.
 */

require_once __DIR__ . '/../config/database.php';

function getDealById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT d.*,
                l.title AS lead_title,
                CONCAT(u.first_name, " ", u.last_name) AS creator_name
         FROM deals d
         LEFT JOIN leads l ON d.lead_id = l.id
         LEFT JOIN users u ON d.created_by = u.id
         WHERE d.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function listDeals(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $db = getDB();
    $where  = [];
    $params = [];

    if (!empty($filters['stage'])) {
        $where[]           = 'd.stage = :stage';
        $params[':stage']  = $filters['stage'];
    }
    if (!empty($filters['lead_id'])) {
        $where[]           = 'd.lead_id = :lid';
        $params[':lid']    = (int) $filters['lead_id'];
    }
    if (!empty($filters['search'])) {
        $where[]           = '(d.title LIKE :s OR l.title LIKE :s2)';
        $params[':s']      = '%' . $filters['search'] . '%';
        $params[':s2']     = '%' . $filters['search'] . '%';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT COUNT(*) FROM deals d LEFT JOIN leads l ON d.lead_id = l.id {$whereSQL}");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $sql = "SELECT d.*,
                   l.title AS lead_title,
                   CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM deals d
            LEFT JOIN leads l ON d.lead_id = l.id
            LEFT JOIN users u ON d.created_by = u.id
            {$whereSQL}
            ORDER BY d.updated_at DESC
            LIMIT :lim OFFSET :off";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

function createDeal(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO deals (lead_id, title, amount, stage, created_by)
         VALUES (:lid, :title, :amt, :stage, :cb)'
    );
    $stmt->execute([
        ':lid'   => $data['lead_id'] ?: null,
        ':title' => $data['title'],
        ':amt'   => $data['amount'] ?? 0,
        ':stage' => $data['stage'] ?? 'prospecting',
        ':cb'    => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

function updateDeal(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE deals SET lead_id = :lid, title = :title, amount = :amt WHERE id = :id'
    );
    $stmt->execute([
        ':lid'   => $data['lead_id'] ?: null,
        ':title' => $data['title'],
        ':amt'   => $data['amount'] ?? 0,
        ':id'    => $id,
    ]);
}

function updateDealStage(int $id, string $newStage): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE deals SET stage = :s WHERE id = :id');
    $stmt->execute([':s' => $newStage, ':id' => $id]);
}

function deleteDeal(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM deals WHERE id = :id')->execute([':id' => $id]);
}

function getDealsByLead(int $leadId): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM deals WHERE lead_id = :lid ORDER BY updated_at DESC');
    $stmt->execute([':lid' => $leadId]);
    return $stmt->fetchAll();
}

function getAllLeadsForDropdown(): array
{
    $db = getDB();
    return $db->query('SELECT id, title FROM leads ORDER BY title ASC')->fetchAll();
}

function totalDealValue(): float
{
    $db = getDB();
    return (float) $db->query("SELECT COALESCE(SUM(amount),0) FROM deals WHERE stage NOT IN ('lost')")->fetchColumn();
}

function countDealsByStage(): array
{
    $db = getDB();
    $stmt = $db->query('SELECT stage, COUNT(*) AS cnt, SUM(amount) AS total_amount FROM deals GROUP BY stage');
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['stage']] = ['count' => (int)$row['cnt'], 'amount' => (float)$row['total_amount']];
    }
    return $result;
}
