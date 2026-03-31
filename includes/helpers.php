<?php
/**
 * General helper functions used across the application.
 */

/**
 * Sanitize and escape output for HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message in session.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message.
 */
function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render flash message HTML if one exists.
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';
    $type = e($flash['type']);
    $msg  = e($flash['message']);
    return "<div class=\"alert alert-{$type}\">{$msg}</div>";
}

/**
 * Create an in-app notification for a user.
 */
function createNotification(int $userId, string $message, string $link = ''): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO notifications (user_id, message, link) VALUES (:uid, :msg, :link)'
    );
    $stmt->execute([
        ':uid'  => $userId,
        ':msg'  => $message,
        ':link' => $link,
    ]);
}

/**
 * Log a status change in the status_history table.
 */
function logStatusChange(string $objectType, int $objectId, ?string $oldStatus, string $newStatus, int $changedBy): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO status_history (object_type, object_id, old_status, new_status, changed_by)
         VALUES (:ot, :oid, :os, :ns, :cb)'
    );
    $stmt->execute([
        ':ot'  => $objectType,
        ':oid' => $objectId,
        ':os'  => $oldStatus,
        ':ns'  => $newStatus,
        ':cb'  => $changedBy,
    ]);
}

/**
 * Format a date string for display.
 */
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) return '—';
    return date($format, strtotime($date));
}

/**
 * Format a datetime string for display.
 */
function formatDateTime(?string $dt): string
{
    if (!$dt) return '—';
    return date('M j, Y g:i A', strtotime($dt));
}

/**
 * Return a human-readable label for a status/stage.
 */
function statusLabel(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}

/**
 * Return a CSS class for a status badge.
 */
function statusClass(string $status): string
{
    $map = [
        'new'          => 'badge-info',
        'contacted'    => 'badge-primary',
        'qualified'    => 'badge-warning',
        'won'          => 'badge-success',
        'lost'         => 'badge-danger',
        'prospecting'  => 'badge-info',
        'proposal'     => 'badge-primary',
        'negotiation'  => 'badge-warning',
        'pending'      => 'badge-warning',
        'completed'    => 'badge-success',
        'cancelled'    => 'badge-danger',
    ];
    return $map[$status] ?? 'badge-secondary';
}

/**
 * Build a pagination array.
 */
function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
    ];
}

/**
 * Render pagination links.
 */
function renderPagination(array $pag, string $baseUrl): string
{
    if ($pag['total_pages'] <= 1) return '';

    $html = '<div class="pagination">';
    // Previous
    if ($pag['current'] > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($pag['current'] - 1) . '">&laquo; Prev</a>';
    }
    // Page numbers
    for ($i = 1; $i <= $pag['total_pages']; $i++) {
        if ($i == $pag['current']) {
            $html .= '<span class="page-current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a>';
        }
    }
    // Next
    if ($pag['current'] < $pag['total_pages']) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($pag['current'] + 1) . '">Next &raquo;</a>';
    }
    $html .= '</div>';
    return $html;
}
