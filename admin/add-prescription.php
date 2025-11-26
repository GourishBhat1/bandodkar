<?php
include('includes/header.php');
include('connection.php');

if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}

// ------------------------------------
// Validate patient_id
// ------------------------------------
if (!isset($_GET['patient_id'])) {
    echo "<script>alert('Invalid patient!'); window.location='dashboard.php';</script>";
    exit;
}

$patient_id = intval($_GET['patient_id']);

// ------------------------------------
// Add Prescription
// ------------------------------------
if (isset($_POST['add'])) {

    $doctor_name = "Dr. Bandodkar"; 
    $date_prescribed = date("Y-m-d"); // AUTO DATE
    $description = ""; // EMPTY — removed feature

    // ------------------------------
    // Handle image upload
    // ------------------------------
    $target_dir = "uploads/prescriptions/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        die("Image upload failed.");
    }

    // ------------------------------
    // Insert into DB
    // ------------------------------
    $stmt = $conn->prepare("
        INSERT INTO prescriptions 
        (patient_id, doctor_name, date_prescribed, description, image_path, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->bind_param("issss", $patient_id, $doctor_name, $date_prescribed, $description, $target_file);

    if (!$stmt->execute()) {
        die("Execute Error: " . $stmt->error);
    }

    $stmt->close();

    echo "<script>alert('Prescription added!'); window.location='profile.php?id=$patient_id';</script>";
    exit;
}

?>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<main class="main" id="top">
  <div class="container" data-layout="container">

    <script>
      var isFluid = JSON.parse(localStorage.getItem('isFluid'));
      if (isFluid) {
        document.querySelector('[data-layout]').classList.remove('container');
        document.querySelector('[data-layout]').classList.add('container-fluid');
      }
    </script>

    <?php include('includes/sidebar.php'); ?>

    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Add Prescription</h5>
          <a href="profile.php?id=<?php echo $patient_id; ?>" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="card-body">

          <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
  <label>Prescription Image *</label>
  <input 
      type="file" 
      name="image" 
      accept="image/*" 
      capture="environment" 
      required 
      class="form-control">
</div>

            <button type="submit" name="add" class="btn btn-success">Upload Prescription</button>

          </form>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>

    </div>
  </div>
</main>

</body>
</html>
