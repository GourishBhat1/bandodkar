<?php
include('includes/header.php');
include('connection.php');

if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}

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

// Update Patient
if (isset($_POST['update'])) {

    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $phone = trim($_POST['phone_number']);
    $updated_at = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        UPDATE patients 
        SET first_name=?, last_name=?, phone_number=?, updated_at=?
        WHERE patient_id=?
    ");
    $stmt->bind_param("ssssi", $first, $last, $phone, $updated_at, $patient_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Updated successfully'); window.location='profile.php?id=$patient_id';</script>";
    exit;
}
?>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<main class="main" id="top">
  <div class="container" data-layout="container">

    <?php include('includes/sidebar.php'); ?>
    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- EDIT PAGE -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Edit Patient</h4>
          <a href="dashboard.php" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="card-body">

          <form method="POST">

            <div class="mb-3">
              <label class="form-label fw-bold">First Name</label>
              <input type="text" name="first_name" class="form-control form-control-lg"
                value="<?php echo $patient['first_name']; ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Last Name</label>
              <input type="text" name="last_name" class="form-control form-control-lg"
                value="<?php echo $patient['last_name']; ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Phone Number</label>
              <input type="text" name="phone_number" class="form-control form-control-lg"
                value="<?php echo $patient['phone_number']; ?>">
            </div>

            <div class="d-flex justify-content-start mt-4">

              <button type="submit" name="update" class="btn btn-primary btn-lg px-4">
                Save Changes
              </button>

            </div>

          </form>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>

    </div>
  </div>
</main>

</body>
</html>
