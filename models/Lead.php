<?php
/**
 * Lead model - CRUD + search/filter/sort functions.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get a single lead by ID with joined data.
 */
function getLeadById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT l.*,
                CONCAT(u1.first_name, " ", u1.last_name) AS creator_name,
                CONCAT(u2.first_name, " ", u2.last_name) AS assignee_name,
                c.name AS company_name,
                CONCAT(ct.first_name, " ", ct.last_name) AS contact_name,
                cat.name AS category_name
         FROM leads l
         LEFT JOIN users u1      ON l.created_by  = u1.id
         LEFT JOIN users u2      ON l.assigned_to = u2.id
         LEFT JOIN companies c   ON l.company_id  = c.id
         LEFT JOIN contacts ct   ON l.contact_id  = ct.id
         LEFT JOIN categories cat ON l.category_id = cat.id
         WHERE l.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * List leads with search, filter, sort, and pagination.
 *
 * @param array $filters  ['search' => '', 'status' => '', 'sort' => 'newest']
 * @param int   $limit
 * @param int   $offset
 * @return array ['items' => [...], 'total' => int]
 */
function listLeads(array $filters = [], int $limit = 10, int $offset = 0): array
{
    $db = getDB();

    $where  = [];
    $params = [];

    // Search by title or description
    if (!empty($filters['search'])) {
        $where[]           = '(l.title LIKE :search OR l.description LIKE :search2)';
        $params[':search']  = '%' . $filters['search'] . '%';
        $params[':search2'] = '%' . $filters['search'] . '%';
    }

    // Filter by status
    if (!empty($filters['status'])) {
        $where[]            = 'l.status = :status';
        $params[':status']  = $filters['status'];
    }

    // Filter by assigned_to
    if (!empty($filters['assigned_to'])) {
        $where[]               = 'l.assigned_to = :assigned';
        $params[':assigned']   = (int) $filters['assigned_to'];
    }

    // Filter by category
    if (!empty($filters['category_id'])) {
        $where[]              = 'l.category_id = :catid';
        $params[':catid']     = (int) $filters['category_id'];
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Sort
    $sortMap = [
        'newest'   => 'l.created_at DESC',
        'oldest'   => 'l.created_at ASC',
        'title_az' => 'l.title ASC',
        'title_za' => 'l.title DESC',
        'updated'  => 'l.updated_at DESC',
    ];
    $sort = $sortMap[$filters['sort'] ?? 'newest'] ?? 'l.created_at DESC';

    // Count
    $countSQL = "SELECT COUNT(*) FROM leads l {$whereSQL}";
    $stmt = $db->prepare($countSQL);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Fetch
    $sql = "SELECT l.*,
                   CONCAT(u1.first_name, ' ', u1.last_name) AS creator_name,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS assignee_name,
                   c.name AS company_name,
                   cat.name AS category_name
            FROM leads l
            LEFT JOIN users u1      ON l.created_by  = u1.id
            LEFT JOIN users u2      ON l.assigned_to = u2.id
            LEFT JOIN companies c   ON l.company_id  = c.id
            LEFT JOIN categories cat ON l.category_id = cat.id
            {$whereSQL}
            ORDER BY {$sort}
            LIMIT :lim OFFSET :off";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Create a new lead. Returns the new lead ID.
 */
function createLead(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO leads (title, description, status, company_id, contact_id, assigned_to, category_id, created_by)
         VALUES (:title, :desc, :status, :cid, :ctid, :assigned, :catid, :cb)'
    );
    $stmt->execute([
        ':title'    => $data['title'],
        ':desc'     => $data['description'] ?? null,
        ':status'   => $data['status'] ?? 'new',
        ':cid'      => $data['company_id'] ?: null,
        ':ctid'     => $data['contact_id'] ?: null,
        ':assigned' => $data['assigned_to'] ?: null,
        ':catid'    => $data['category_id'] ?: null,
        ':cb'       => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Update an existing lead.
 */
function updateLead(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE leads
         SET title       = :title,
             description = :desc,
             company_id  = :cid,
             contact_id  = :ctid,
             assigned_to = :assigned,
             category_id = :catid
         WHERE id = :id'
    );
    $stmt->execute([
        ':title'    => $data['title'],
        ':desc'     => $data['description'] ?? null,
        ':cid'      => $data['company_id'] ?: null,
        ':ctid'     => $data['contact_id'] ?: null,
        ':assigned' => $data['assigned_to'] ?: null,
        ':catid'    => $data['category_id'] ?: null,
        ':id'       => $id,
    ]);
}

/**
 * Update lead status.
 */
function updateLeadStatus(int $id, string $newStatus): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE leads SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $newStatus, ':id' => $id]);
}

/**
 * Delete a lead.
 */
function deleteLead(int $id): void
{
    $db = getDB();
    // Notes for this lead
    $db->prepare('DELETE FROM notes WHERE object_type = "lead" AND object_id = :id')->execute([':id' => $id]);
    // Lead itself (deals, activities cascade)
    $db->prepare('DELETE FROM leads WHERE id = :id')->execute([':id' => $id]);
}

/**
 * Get leads assigned to a specific user.
 */
function getLeadsByAssignee(int $userId, int $limit = 10): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT l.*, c.name AS company_name
         FROM leads l
         LEFT JOIN companies c ON l.company_id = c.id
         WHERE l.assigned_to = :uid
         ORDER BY l.updated_at DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get leads created by a specific user.
 */
function getLeadsByCreator(int $userId, int $limit = 10): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT l.*, c.name AS company_name
         FROM leads l
         LEFT JOIN companies c ON l.company_id = c.id
         WHERE l.created_by = :uid
         ORDER BY l.updated_at DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Count leads by status for reports.
 */
function countLeadsByStatus(): array
{
    $db = getDB();
    $stmt = $db->query('SELECT status, COUNT(*) AS cnt FROM leads GROUP BY status');
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['status']] = (int) $row['cnt'];
    }
    return $result;
}
