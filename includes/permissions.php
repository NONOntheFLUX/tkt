<?php
/**
 * Permissions helper.
 * Centralizes all role-based access checks for business objects.
 */

require_once __DIR__ . '/auth.php';

/* ------------------------------------------------------------------ */
/*  Lead permissions                                                   */
/* ------------------------------------------------------------------ */

/**
 * Can the current user create a lead?
 */
function canCreateLead(): bool
{
    return isLoggedIn() && hasMinRole('user');
}

/**
 * Can the current user edit a given lead?
 * Owner, assigned rep, sales_manager, or admin.
 */
function canEditLead(array $lead): bool
{
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    if (hasMinRole('sales_manager')) return true;

    $uid = currentUserId();
    // Owner or assigned user
    return ($lead['created_by'] == $uid || $lead['assigned_to'] == $uid);
}

/**
 * Can the current user delete a given lead?
 * Only owner or admin.
 */
function canDeleteLead(array $lead): bool
{
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($lead['created_by'] == currentUserId());
}

/**
 * Can the current user change a lead's status?
 */
function canChangeLeadStatus(array $lead): bool
{
    return canEditLead($lead);
}

/**
 * Can the current user assign leads?
 * Only sales_manager or admin.
 */
function canAssignLead(): bool
{
    return isLoggedIn() && hasMinRole('sales_manager');
}

/* ------------------------------------------------------------------ */
/*  Deal permissions                                                   */
/* ------------------------------------------------------------------ */

function canCreateDeal(): bool
{
    return isLoggedIn() && hasMinRole('user');
}

function canEditDeal(array $deal): bool
{
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    if (hasMinRole('sales_manager')) return true;
    return ($deal['created_by'] == currentUserId());
}

function canDeleteDeal(array $deal): bool
{
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($deal['created_by'] == currentUserId());
}

/**
 * Can the user move a deal stage backward?
 * Only sales_manager or admin.
 */
function canMoveDealBackward(): bool
{
    return isLoggedIn() && hasMinRole('sales_manager');
}

/* ------------------------------------------------------------------ */
/*  Company / Contact / Activity permissions                           */
/* ------------------------------------------------------------------ */

function canCreateCompany(): bool  { return isLoggedIn() && hasMinRole('user'); }
function canEditCompany(array $c): bool {
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($c['created_by'] == currentUserId());
}
function canDeleteCompany(array $c): bool { return canEditCompany($c); }

function canCreateContact(): bool  { return isLoggedIn() && hasMinRole('user'); }
function canEditContact(array $c): bool {
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($c['created_by'] == currentUserId());
}
function canDeleteContact(array $c): bool { return canEditContact($c); }

function canCreateActivity(): bool { return isLoggedIn() && hasMinRole('user'); }
function canEditActivity(array $a): bool {
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($a['created_by'] == currentUserId());
}
function canDeleteActivity(array $a): bool { return canEditActivity($a); }

/* ------------------------------------------------------------------ */
/*  Notes (any logged-in user can add; only owner or admin can delete) */
/* ------------------------------------------------------------------ */

function canAddNote(): bool
{
    return isLoggedIn();
}

function canDeleteNote(array $note): bool
{
    if (!isLoggedIn()) return false;
    if (hasMinRole('admin')) return true;
    return ($note['created_by'] == currentUserId());
}

/* ------------------------------------------------------------------ */
/*  Admin area                                                         */
/* ------------------------------------------------------------------ */

function canAccessAdmin(): bool
{
    return isLoggedIn() && hasMinRole('admin');
}

/* ------------------------------------------------------------------ */
/*  Workflow: allowed status transitions                               */
/* ------------------------------------------------------------------ */

/**
 * Get allowed next statuses for a lead based on current status.
 */
function allowedLeadTransitions(string $currentStatus): array
{
    $map = [
        'new'       => ['contacted'],
        'contacted' => ['qualified', 'lost'],
        'qualified' => ['won', 'lost'],
        'won'       => [],
        'lost'      => [],
    ];
    return $map[$currentStatus] ?? [];
}

/**
 * Get allowed next stages for a deal based on current stage.
 * If the user is sales_manager or admin, backward moves are allowed.
 */
function allowedDealTransitions(string $currentStage): array
{
    $forward = [
        'prospecting' => ['proposal'],
        'proposal'    => ['negotiation'],
        'negotiation' => ['won', 'lost'],
        'won'         => [],
        'lost'        => [],
    ];

    $allowed = $forward[$currentStage] ?? [];

    // Backward transitions for manager/admin
    if (canMoveDealBackward()) {
        $backward = [
            'proposal'    => ['prospecting'],
            'negotiation' => ['proposal'],
            'won'         => ['negotiation'],
            'lost'        => ['negotiation'],
        ];
        $allowed = array_merge($allowed, $backward[$currentStage] ?? []);
        $allowed = array_unique($allowed);
    }

    return $allowed;
}

/**
 * Validate a lead status transition.
 */
function isValidLeadTransition(string $from, string $to): bool
{
    return in_array($to, allowedLeadTransitions($from), true);
}

/**
 * Validate a deal stage transition.
 */
function isValidDealTransition(string $from, string $to): bool
{
    return in_array($to, allowedDealTransitions($from), true);
}
