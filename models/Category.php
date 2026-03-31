<?php
/**
 * Category model - simple reference data management.
 */

require_once __DIR__ . '/../config/database.php';

function getCategoryById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function listCategories(string $type = ''): array
{
    $db = getDB();
    if ($type) {
        $stmt = $db->prepare('SELECT * FROM categories WHERE type = :type ORDER BY name ASC');
        $stmt->execute([':type' => $type]);
    } else {
        $stmt = $db->query('SELECT * FROM categories ORDER BY type ASC, name ASC');
    }
    return $stmt->fetchAll();
}

function createCategory(string $name, string $type = 'industry'): int
{
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO categories (name, type) VALUES (:name, :type)');
    $stmt->execute([':name' => $name, ':type' => $type]);
    return (int) $db->lastInsertId();
}

function updateCategory(int $id, string $name, string $type): void
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE categories SET name = :name, type = :type WHERE id = :id');
    $stmt->execute([':name' => $name, ':type' => $type, ':id' => $id]);
}

function deleteCategory(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
}

function getCategoriesByType(string $type): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id, name FROM categories WHERE type = :type ORDER BY name ASC');
    $stmt->execute([':type' => $type]);
    return $stmt->fetchAll();
}
