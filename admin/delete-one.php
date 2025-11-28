<?php
include('connection.php');

$id = intval($_GET['id'] ?? 0);
$patientid = intval($_GET['patientid'] ?? 0);

if ($id && $patientid) {

    $stmt = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($img && file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }

    $del = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id=?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();
}

header("Location: profile.php?id=" . $patientid);
exit;
