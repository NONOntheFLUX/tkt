<?php
/**
 * Deal Delete handler.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/Deal.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('deals.php');

$deal = getDealById($id);
if (!$deal) { setFlash('danger', 'Deal not found.'); redirect('deals.php'); }
if (!canDeleteDeal($deal)) { setFlash('danger', 'Access denied.'); redirect('deals.php'); }

deleteDeal($id);
setFlash('success', 'Deal deleted.');
redirect('deals.php');
