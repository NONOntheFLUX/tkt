<?php
/**
 * Contact model - CRUD functions.
 */

require_once __DIR__ . '/../config/database.php';

function getContactById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ct.*,
                c.name AS company_name,
                CONCAT(u.first_name, " ", u.last_name) AS creator_name
         FROM contacts ct
         LEFT JOIN companies c ON ct.company_id = c.id
         LEFT JOIN users u     ON ct.created_by = u.id
         WHERE ct.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function listContacts(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $db = getDB();
    $where  = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[]           = '(ct.first_name LIKE :s1 OR ct.last_name LIKE :s2 OR ct.email LIKE :s3)';
        $params[':s1'] = '%' . $filters['search'] . '%';
        $params[':s2'] = '%' . $filters['search'] . '%';
        $params[':s3'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['company_id'])) {
        $where[]             = 'ct.company_id = :cid';
        $params[':cid']      = (int) $filters['company_id'];
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT COUNT(*) FROM contacts ct {$whereSQL}");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $sql = "SELECT ct.*,
                   c.name AS company_name,
                   CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM contacts ct
            LEFT JOIN companies c ON ct.company_id = c.id
            LEFT JOIN users u     ON ct.created_by = u.id
            {$whereSQL}
            ORDER BY ct.last_name ASC, ct.first_name ASC
            LIMIT :lim OFFSET :off";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

function createContact(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO contacts (company_id, first_name, last_name, email, phone, created_by)
         VALUES (:cid, :fn, :ln, :email, :phone, :cb)'
    );
    $stmt->execute([
        ':cid'   => $data['company_id'] ?: null,
        ':fn'    => $data['first_name'],
        ':ln'    => $data['last_name'],
        ':email' => $data['email'] ?? null,
        ':phone' => $data['phone'] ?? null,
        ':cb'    => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

function updateContact(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE contacts SET company_id = :cid, first_name = :fn, last_name = :ln, email = :email, phone = :phone WHERE id = :id'
    );
    $stmt->execute([
        ':cid'   => $data['company_id'] ?: null,
        ':fn'    => $data['first_name'],
        ':ln'    => $data['last_name'],
        ':email' => $data['email'] ?? null,
        ':phone' => $data['phone'] ?? null,
        ':id'    => $id,
    ]);
}

function deleteContact(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM contacts WHERE id = :id')->execute([':id' => $id]);
}

function getContactsByCompany(int $companyId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT id, first_name, last_name, email FROM contacts WHERE company_id = :cid ORDER BY last_name'
    );
    $stmt->execute([':cid' => $companyId]);
    return $stmt->fetchAll();
}

function getAllContacts(): array
{
    $db = getDB();
    return $db->query('SELECT id, first_name, last_name FROM contacts ORDER BY last_name ASC')->fetchAll();
}
