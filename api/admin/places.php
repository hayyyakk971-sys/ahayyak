<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'POST');
requireRole('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $where  = [];
    $params = [];
    $validStatuses = ['pending','approved','rejected'];
    if (!empty($_GET['status']) && in_array($_GET['status'], $validStatuses, true)) {
        $where[]          = 'p.status = :status';
        $params[':status'] = $_GET['status'];
    }
    $validCategories = ['Restaurant','Hotel','Tourism','Entertainment','Farm','Store','Health'];
    if (!empty($_GET['category']) && in_array($_GET['category'], $validCategories, true)) {
        $where[]            = 'p.category = :category';
        $params[':category'] = $_GET['category'];
    }

    $sql = 'SELECT p.id, p.category, p.name_ar, p.name_en, p.city, p.emirate,
                   p.status, p.rejection_reason, p.created_at,
                   u.id AS owner_id, u.name_ar AS owner_name_ar, u.name_en AS owner_name_en, u.email AS owner_email
            FROM places p JOIN users u ON u.id = p.owner_id'
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY p.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonOk($stmt->fetchAll());
}

// POST — approve or reject
requireCsrf();
$body    = bodyJson();
$placeId = sanitizeInt($body['place_id'] ?? null);
$action  = $body['action'] ?? '';
if (!$placeId) jsonError('place_id required');
if (!in_array($action, ['approve','reject'], true)) jsonError('action must be approve or reject');

if ($action === 'reject') {
    $reason = trim($body['reason'] ?? '');
    if (!$reason) jsonError('rejection_reason required when rejecting');
    $db->prepare('UPDATE places SET status = "rejected", rejection_reason = :r WHERE id = :id')
       ->execute([':r' => $reason, ':id' => $placeId]);
} else {
    $db->prepare('UPDATE places SET status = "approved", rejection_reason = NULL WHERE id = :id')
       ->execute([':id' => $placeId]);
}
jsonOk(['updated' => true, 'status' => $action === 'approve' ? 'approved' : 'rejected']);
