<?php
header('Content-Type: application/json');
include('connection.php');

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$patient_id = intval($_GET['id']);


// ------------------------------------------
// 1. Fetch prescription images
// ------------------------------------------
$get = $conn->prepare("SELECT image_path FROM prescriptions WHERE patient_id=?");
$get->bind_param("i", $patient_id);
$get->execute();
$result = $get->get_result();

while ($row = $result->fetch_assoc()) {
    $path = $row['image_path'];
    if (!empty($path) && file_exists($path)) {
        @unlink($path); // @ suppresses warnings
    }
}
$get->close();


// ------------------------------------------
// 2. Delete prescriptions
// ------------------------------------------
$delPres = $conn->prepare("DELETE FROM prescriptions WHERE patient_id=?");
$delPres->bind_param("i", $patient_id);
$delPres->execute();
$delPres->close();


// ------------------------------------------
// 3. Delete patient
// ------------------------------------------
$delPat = $conn->prepare("DELETE FROM patients WHERE patient_id=?");
$delPat->bind_param("i", $patient_id);
$delPat->execute();
$delPat->close();

echo json_encode(["status" => "success", "message" => "Patient deleted"]);
exit;
?>
