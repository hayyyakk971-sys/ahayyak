<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'POST');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Public listing — only approved places
    $where  = ['p.status = "approved"'];
    $params = [];

    $validCats = ['Restaurant','Hotel','Tourism','Entertainment','Farm','Store','Health'];
    if (!empty($_GET['category']) && in_array($_GET['category'], $validCats, true)) {
        $where[]              = 'p.category = :cat';
        $params[':cat']       = $_GET['category'];
    }
    if (!empty($_GET['city'])) {
        $where[]              = 'p.city LIKE :city';
        $params[':city']      = '%' . $_GET['city'] . '%';
    }
    if (!empty($_GET['emirate'])) {
        $where[]              = 'p.emirate LIKE :emirate';
        $params[':emirate']   = '%' . $_GET['emirate'] . '%';
    }
    if (!empty($_GET['q'])) {
        $like                 = '%' . $_GET['q'] . '%';
        $where[]              = '(p.name_ar LIKE :q OR p.name_en LIKE :q OR p.description_ar LIKE :q OR p.description_en LIKE :q)';
        $params[':q']         = $like;
    }

    $sql  = 'SELECT p.id, p.category, p.name_ar, p.name_en, p.city, p.emirate,
                    p.latitude, p.longitude, p.opening_hours, p.price_range,
                    p.phobia_triggers, p.medical_triggers, p.interest_tags,
                    u.name_ar AS owner_name_ar, u.name_en AS owner_name_en
             FROM places p
             JOIN users u ON u.id = p.owner_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.created_at DESC
             LIMIT 200';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        foreach (['phobia_triggers','medical_triggers','interest_tags'] as $col) {
            $r[$col] = $r[$col] ? json_decode($r[$col], true) : [];
        }
    }
    jsonOk($rows);
}

// POST — owner creates new place
$user = requireRole('place_owner');
requireCsrf();

$body = bodyJson();
$validCats = ['Restaurant','Hotel','Tourism','Entertainment','Farm','Store','Health'];
$cat = $body['category'] ?? '';
if (!in_array($cat, $validCats, true)) jsonError('Invalid category');

$nameAr = trim($body['name_ar'] ?? '');
$nameEn = trim($body['name_en'] ?? '');
if (!$nameAr && !$nameEn) jsonError('Place name required');

$stmt = $db->prepare(
    'INSERT INTO places
     (owner_id, category, name_ar, name_en, city, emirate, address_ar, address_en,
      latitude, longitude, description_ar, description_en, opening_hours, price_range,
      phobia_triggers, medical_triggers, interest_tags, status)
     VALUES
     (:owner, :cat, :nar, :nen, :city, :emirate, :aar, :aen,
      :lat, :lon, :dar, :den, :hours, :price,
      :phobia, :medical, :interest, "pending")'
);
$stmt->execute([
    ':owner'   => $user['id'],
    ':cat'     => $cat,
    ':nar'     => $nameAr ?: null,
    ':nen'     => $nameEn ?: null,
    ':city'    => $body['city'] ?? null,
    ':emirate' => $body['emirate'] ?? null,
    ':aar'     => $body['address_ar'] ?? null,
    ':aen'     => $body['address_en'] ?? null,
    ':lat'     => isset($body['latitude']) ? (float)$body['latitude'] : null,
    ':lon'     => isset($body['longitude']) ? (float)$body['longitude'] : null,
    ':dar'     => $body['description_ar'] ?? null,
    ':den'     => $body['description_en'] ?? null,
    ':hours'   => $body['opening_hours'] ?? null,
    ':price'   => $body['price_range'] ?? null,
    ':phobia'  => isset($body['phobia_triggers']) ? json_encode($body['phobia_triggers']) : null,
    ':medical' => isset($body['medical_triggers']) ? json_encode($body['medical_triggers']) : null,
    ':interest'=> isset($body['interest_tags']) ? json_encode($body['interest_tags']) : null,
]);

jsonOk(['id' => (int)$db->lastInsertId(), 'status' => 'pending']);
