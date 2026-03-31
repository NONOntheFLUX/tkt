<?php
/**
 * Company model - CRUD functions.
 */

require_once __DIR__ . '/../config/database.php';

function getCompanyById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT co.*,
                CONCAT(u.first_name, " ", u.last_name) AS creator_name
         FROM companies co
         LEFT JOIN users u ON co.created_by = u.id
         WHERE co.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function listCompanies(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $db = getDB();
    $where  = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[]          = '(co.name LIKE :search OR co.industry LIKE :search2)';
        $params[':search']  = '%' . $filters['search'] . '%';
        $params[':search2'] = '%' . $filters['search'] . '%';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count
    $stmt = $db->prepare("SELECT COUNT(*) FROM companies co {$whereSQL}");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Fetch
    $sql = "SELECT co.*,
                   CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM companies co
            LEFT JOIN users u ON co.created_by = u.id
            {$whereSQL}
            ORDER BY co.name ASC
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

function createCompany(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO companies (name, industry, website, address, created_by)
         VALUES (:name, :ind, :web, :addr, :cb)'
    );
    $stmt->execute([
        ':name' => $data['name'],
        ':ind'  => $data['industry'] ?? null,
        ':web'  => $data['website'] ?? null,
        ':addr' => $data['address'] ?? null,
        ':cb'   => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

function updateCompany(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE companies SET name = :name, industry = :ind, website = :web, address = :addr WHERE id = :id'
    );
    $stmt->execute([
        ':name' => $data['name'],
        ':ind'  => $data['industry'] ?? null,
        ':web'  => $data['website'] ?? null,
        ':addr' => $data['address'] ?? null,
        ':id'   => $id,
    ]);
}

function deleteCompany(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM companies WHERE id = :id')->execute([':id' => $id]);
}

/**
 * Get all companies as a simple list for dropdowns.
 */
function getAllCompanies(): array
{
    $db = getDB();
    return $db->query('SELECT id, name FROM companies ORDER BY name ASC')->fetchAll();
}
