<?php
// delete-prescriptions.php
header('Content-Type: application/json; charset=utf-8');

include('connection.php');

// check login cookie
if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Unauthenticated']);
    exit;
}

// read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid request data']);
    exit;
}

$patient_id = isset($input['patient_id']) ? intval($input['patient_id']) : 0;
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

if ($patient_id <= 0 || empty($ids)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Missing patient or ids']);
    exit;
}

// sanitize ids (integers)
$cleanIds = [];
foreach ($ids as $i) {
    $i = intval($i);
    if ($i > 0) $cleanIds[] = $i;
}
$cleanIds = array_values(array_unique($cleanIds));
if (empty($cleanIds)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No valid ids provided']);
    exit;
}

// Fetch image paths first
$placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
$sql = "SELECT prescription_id, image_path FROM prescriptions WHERE prescription_id IN ($placeholders) AND patient_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB prepare failed']);
    exit;
}

// bind dynamic params
$types = str_repeat('i', count($cleanIds)) . 'i';
$params = array_merge($cleanIds, [$patient_id]);

// use reflection trick to bind by reference
$bind_names[] = $types;
for ($i=0; $i<count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

$stmt->execute();
$res = $stmt->get_result();

$toDeletePaths = [];
$foundIds = [];
while ($r = $res->fetch_assoc()) {
    $foundIds[] = intval($r['prescription_id']);
    if (!empty($r['image_path'])) $toDeletePaths[] = $r['image_path'];
}
$stmt->close();

if (empty($foundIds)) {
    echo json_encode(['status'=>'error','message'=>'No matching prescriptions found']);
    exit;
}

// Delete files
$deletedFiles = 0;
foreach ($toDeletePaths as $p) {
    if (file_exists($p) && is_file($p)) {
        @unlink($p);
        $deletedFiles++;
    }
}

// Delete DB records using IN (...) clause with foundIds
$placeholders2 = implode(',', array_fill(0, count($foundIds), '?'));
$sql2 = "DELETE FROM prescriptions WHERE prescription_id IN ($placeholders2) AND patient_id = ?";
$stmt2 = $conn->prepare($sql2);

if ($stmt2 === false) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB prepare failed (delete)']);
    exit;
}

// bind params
$types2 = str_repeat('i', count($foundIds)) . 'i';
$params2 = array_merge($foundIds, [$patient_id]);

$bind_names2[] = $types2;
for ($i=0; $i<count($params2); $i++) {
    $bind_name = 'b2' . $i;
    $$bind_name = $params2[$i];
    $bind_names2[] = &$$bind_name;
}
call_user_func_array([$stmt2, 'bind_param'], $bind_names2);

$stmt2->execute();
$affected = $stmt2->affected_rows;
$stmt2->close();

echo json_encode([
    'status' => 'success',
    'deleted_count' => $affected,
    'deleted_files' => $deletedFiles
]);
exit;
