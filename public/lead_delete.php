<?php
/**
 * Lead Delete - confirms and deletes a lead.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Lead.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('leads.php');

$lead = getLeadById($id);
if (!$lead) {
    setFlash('danger', 'Lead not found.');
    redirect('leads.php');
}

if (!canDeleteLead($lead)) {
    setFlash('danger', 'You do not have permission to delete this lead.');
    redirect('leads.php');
}

deleteLead($id);
setFlash('success', 'Lead "' . $lead['title'] . '" has been deleted.');
redirect('leads.php');
