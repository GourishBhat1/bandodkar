<?php
include 'connection.php';

if (!isset($_POST['id'])) {
    exit("Invalid");
}

$pid = intval($_POST['id']);

$get = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id=?");
$get->bind_param("i", $pid);
$get->execute();
$res = $get->get_result()->fetch_assoc();
$get->close();

if ($res && file_exists($res['image_path'])) {
    unlink($res['image_path']);
}

$del = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id=?");
$del->bind_param("i", $pid);
$del->execute();
$del->close();

echo "OK";
?>
