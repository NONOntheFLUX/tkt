<?php
/**
 * Note model - CRUD functions.
 * Notes are polymorphic: object_type + object_id reference any business object.
 */

require_once __DIR__ . '/../config/database.php';

function getNoteById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT n.*,
                CONCAT(u.first_name, " ", u.last_name) AS author_name
         FROM notes n
         LEFT JOIN users u ON n.created_by = u.id
         WHERE n.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all notes for a given object.
 */
function getNotesByObject(string $objectType, int $objectId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT n.*,
                CONCAT(u.first_name, " ", u.last_name) AS author_name
         FROM notes n
         LEFT JOIN users u ON n.created_by = u.id
         WHERE n.object_type = :ot AND n.object_id = :oid
         ORDER BY n.created_at DESC'
    );
    $stmt->execute([':ot' => $objectType, ':oid' => $objectId]);
    return $stmt->fetchAll();
}

/**
 * Create a new note.
 */
function createNote(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO notes (object_type, object_id, content, created_by)
         VALUES (:ot, :oid, :content, :cb)'
    );
    $stmt->execute([
        ':ot'      => $data['object_type'],
        ':oid'     => $data['object_id'],
        ':content' => $data['content'],
        ':cb'      => $data['created_by'],
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Delete a note.
 */
function deleteNote(int $id): void
{
    $db = getDB();
    $db->prepare('DELETE FROM notes WHERE id = :id')->execute([':id' => $id]);
}
