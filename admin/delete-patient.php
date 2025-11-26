<?php
include('connection.php');

if (!isset($_GET['id'])) {
    echo "Invalid request";
    exit;
}

$patient_id = intval($_GET['id']);

// Delete prescriptions + images
$pres = $conn->prepare("SELECT image_path FROM prescriptions WHERE patient_id=?");
$pres->bind_param("i", $patient_id);
$pres->execute();
$result = $pres->get_result();

while ($p = $result->fetch_assoc()) {
    if (file_exists($p['image_path'])) {
        unlink($p['image_path']);
    }
}
$pres->close();

// Delete prescriptions
$conn->query("DELETE FROM prescriptions WHERE patient_id=$patient_id");

// Delete patient
$stmt = $conn->prepare("DELETE FROM patients WHERE patient_id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->close();

echo "Patient deleted successfully";
?>
