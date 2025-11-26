<?php
include('includes/header.php');
include('connection.php');

if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}

// Validate patient ID
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid patient!'); window.location='dashboard.php';</script>";
    exit;
}

$patient_id = intval($_GET['id']);

// Fetch patient
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found!'); window.location='dashboard.php';</script>";
    exit;
}

// Delete prescription
if (isset($_POST['delete_prescription'])) {

    $prescription_id = intval($_POST['delete_prescription_id']);

    $getImg = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id = ?");
    $getImg->bind_param("i", $prescription_id);
    $getImg->execute();
    $imgRes = $getImg->get_result()->fetch_assoc();
    $getImg->close();

    if ($imgRes && file_exists($imgRes['image_path'])) {
        unlink($imgRes['image_path']);
    }

    $del = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
    $del->bind_param("i", $prescription_id);
    $del->execute();
    $del->close();

    echo "<script>alert('Prescription deleted successfully'); window.location='profile.php?id=$patient_id';</script>";
    exit;
}
?>

<!-- GLightbox CSS -->
<link href="vendors/glightbox/glightbox.min.css" rel="stylesheet">

<main class="main" id="top">
  <div class="container" data-layout="container">

    <?php include('includes/sidebar.php'); ?>
    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- Prescription Gallery -->
      <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Prescription Images</h5>

          <div>
            <a href="dashboard.php" class="btn btn-secondary btn-sm me-2">Back</a>
            <a href="add-prescription.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success btn-sm">
              + Add
            </a>
          </div>
        </div>

        <div class="card-body">
          <div class="row g-3">

            <?php
            $q = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id=? ORDER BY prescription_id DESC");
            $q->bind_param("i", $patient_id);
            $q->execute();
            $result = $q->get_result();

            if ($result->num_rows == 0) {
                echo "<p class='text-muted'>No prescriptions uploaded.</p>";
            }

            while ($p = $result->fetch_assoc()) {
                $img = $p['image_path'];
                echo '
                <div class="col-6 col-md-3 col-lg-2">

                    <!-- GLightbox Trigger -->
                    <a href="'.$img.'" class="glightbox" data-gallery="prescriptions">
                        <img src="'.$img.'" class="img-fluid rounded shadow-sm" 
                        style="height:150px; object-fit:cover; width:100%;">
                    </a>

                    <form method="POST" class="mt-1"
                        onsubmit="return confirm(\'Delete this prescription?\');">
                        <input type="hidden" name="delete_prescription_id" value="'.$p['prescription_id'].'">
                        <button class="btn btn-danger btn-sm w-100">Delete</button>
                    </form>

                </div>';
            }
            ?>
          </div>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>
</main>

<!-- GLightbox JS -->
<script src="vendors/glightbox/glightbox.min.js"></script>

<script>
  // Initialize GLightbox
  const lightbox = GLightbox({
      selector: '.glightbox',
      touchNavigation: true,
      loop: true,
      zoomable: true,
  });
</script>

</body>
</html>
