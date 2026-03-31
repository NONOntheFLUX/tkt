<?php
/**
 * User model - CRUD + admin management.
 */

require_once __DIR__ . '/../config/database.php';

function getUserById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if ($user) unset($user['password']); // Never expose password hash
    return $user ?: null;
}

function getUserByEmail(string $email): ?array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    return $stmt->fetch() ?: null;
}

function listUsers(int $limit = 50, int $offset = 0): array
{
    $db = getDB();

    $total = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $stmt = $db->prepare(
        'SELECT id, first_name, last_name, email, role, is_active, created_at
         FROM users
         ORDER BY created_at DESC
         LIMIT :lim OFFSET :off'
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

function updateUserRole(int $id, string $role): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET role = :role WHERE id = :id');
    $stmt->execute([':role' => $role, ':id' => $id]);
}

function toggleUserActive(int $id, bool $active): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET is_active = :active WHERE id = :id');
    $stmt->execute([':active' => $active ? 1 : 0, ':id' => $id]);
}

function updateUserProfile(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE users SET first_name = :fn, last_name = :ln, email = :email WHERE id = :id'
    );
    $stmt->execute([
        ':fn'    => $data['first_name'],
        ':ln'    => $data['last_name'],
        ':email' => $data['email'],
        ':id'    => $id,
    ]);
}

function updateUserPassword(int $id, string $newPassword): void
{
    $db = getDB();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password = :pw WHERE id = :id');
    $stmt->execute([':pw' => $hash, ':id' => $id]);
}

function adminCreateUser(array $data): int|string
{
    $db = getDB();

    // Check duplicate
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $data['email']]);
    if ($stmt->fetch()) return 'Email already exists.';

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role, is_active)
         VALUES (:fn, :ln, :email, :pw, :role, :active)'
    );
    $stmt->execute([
        ':fn'     => $data['first_name'],
        ':ln'     => $data['last_name'],
        ':email'  => $data['email'],
        ':pw'     => $hash,
        ':role'   => $data['role'] ?? 'user',
        ':active' => $data['is_active'] ?? 1,
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Get users who can be assigned leads (sales reps, managers, admins).
 */
function getAssignableUsers(): array
{
    $db = getDB();
    return $db->query(
        "SELECT id, first_name, last_name, role
         FROM users
         WHERE role IN ('sales_rep','sales_manager','admin') AND is_active = 1
         ORDER BY first_name ASC"
    )->fetchAll();
}

/**
 * Get status history for an object.
 */
function getStatusHistory(string $objectType, int $objectId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT sh.*,
                CONCAT(u.first_name, " ", u.last_name) AS changed_by_name
         FROM status_history sh
         LEFT JOIN users u ON sh.changed_by = u.id
         WHERE sh.object_type = :ot AND sh.object_id = :oid
         ORDER BY sh.changed_at DESC'
    );
    $stmt->execute([':ot' => $objectType, ':oid' => $objectId]);
    return $stmt->fetchAll();
}
