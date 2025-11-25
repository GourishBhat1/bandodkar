<?php
// get-patients.php
header('Content-Type: application/json; charset=utf-8');

include('connection.php');

// simple auth: require login cookie for API access
if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

// permitted sort values mapping to SQL
$allowed_sorts = [
    'id_desc' => 'patient_id DESC',
    'id_asc'  => 'patient_id ASC',
    'name_asc'  => 'first_name ASC, last_name ASC',
    'name_desc' => 'first_name DESC, last_name DESC'
];

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) && isset($allowed_sorts[$_GET['sort']]) ? $_GET['sort'] : 'id_desc';
$orderBy = $allowed_sorts[$sort];

// basic query
// search by id (exact), phone (partial), name (partial)
$params = [];
$types = '';
$whereSql = '';

if ($q !== '') {
    // if numeric, allow id exact match
    if (ctype_digit($q)) {
        $whereSql = " WHERE (patient_id = ? OR phone_number LIKE CONCAT('%',?,'%') OR first_name LIKE CONCAT('%',?,'%') OR last_name LIKE CONCAT('%',?,'%')) ";
        $types = 'isss';
        $params = [intval($q), $q, $q, $q];
    } else {
        $whereSql = " WHERE (phone_number LIKE CONCAT('%',?,'%') OR first_name LIKE CONCAT('%',?,'%') OR last_name LIKE CONCAT('%',?,'%')) ";
        $types = 'sss';
        $params = [$q, $q, $q];
    }
}

// limit optional (for paging later)
$limit = 100;

// build sql
$sql = "SELECT patient_id, first_name, last_name, phone_number, created_at FROM patients {$whereSql} ORDER BY {$orderBy} LIMIT ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

// bind params dynamically
if ($whereSql !== '') {
    // add limit param
    $types_with_limit = $types . 'i';
    $params[] = $limit;
    $stmt->bind_param($types_with_limit, ...$params);
} else {
    $stmt->bind_param('i', $limit);
}

$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    // normalize
    $items[] = [
        'patient_id' => (int)$row['patient_id'],
        'first_name' => $row['first_name'],
        'last_name'  => $row['last_name'],
        'phone_number' => $row['phone_number'],
        'created_at' => $row['created_at']
    ];
}

$stmt->close();

echo json_encode($items, JSON_UNESCAPED_UNICODE);
exit;
