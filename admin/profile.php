<?php
include('includes/header.php');
include('connection.php');

// ------------------------------------
// VALIDATE PATIENT
// ------------------------------------
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid patient!'); window.location='index.php';</script>";
    exit;
}

$patient_id = intval($_GET['id']);

// ------------------------------------
// FETCH PATIENT DETAILS
// ------------------------------------
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found!'); window.location='index.php';</script>";
    exit;
}

// ------------------------------------
// UPDATE PATIENT
// ------------------------------------
if (isset($_POST['update'])) {

    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $dob = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $phone = $_POST['phone_number'];

    $stmt = $conn->prepare("
        UPDATE patients 
        SET first_name=?, last_name=?, birth_date=?, gender=?, address=?, phone_number=?, updated_at=NOW()
        WHERE patient_id=?
    ");
    $stmt->bind_param("ssssssi", $first, $last, $dob, $gender, $address, $phone, $patient_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Patient updated successfully!'); window.location='profile.php?id=$patient_id';</script>";
    exit;
}

// ------------------------------------
// DELETE PATIENT
// ------------------------------------
if (isset($_POST['delete'])) {

    $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id=?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Patient deleted successfully!'); window.location='index.php';</script>";
    exit;
}

// ------------------------------------
// DELETE PRESCRIPTION
// ------------------------------------
if (isset($_POST['delete_prescription'])) {

    $prescription_id = intval($_POST['delete_prescription_id']);

    // Fetch image to delete
    $getImg = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id = ?");
    $getImg->bind_param("i", $prescription_id);
    $getImg->execute();
    $imgRes = $getImg->get_result()->fetch_assoc();
    $getImg->close();

    if ($imgRes) {
        $file = $imgRes['image_path'];
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Delete from DB
    $del = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
    $del->bind_param("i", $prescription_id);
    $del->execute();
    $del->close();

    echo "<script>alert('Prescription deleted successfully'); window.location='profile.php?id=$patient_id';</script>";
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
        var container = document.querySelector('[data-layout]');
        container.classList.remove('container');
        container.classList.add('container-fluid');
      }
    </script>

    <?php include('includes/sidebar.php'); ?>

    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- ===============================================-->
      <!-- PATIENT DETAILS CARD -->
      <!-- ===============================================-->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Patient Profile</h5>
          <a href="index.php" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="card-body">

          <form method="POST">

            <div class="row">
              <div class="col-md-6 mb-3">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo $patient['first_name']; ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo $patient['last_name']; ?>">
              </div>
            </div>

            <div class="row">

              <div class="col-md-4 mb-3">
                <label>Date of Birth</label>
                <input type="date" name="birth_date" class="form-control" value="<?php echo $patient['birth_date']; ?>">
              </div>

              <div class="col-md-4 mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                  <option value="">-- Select --</option>
                  <option value="Male" <?php if ($patient['gender']=="Male") echo "selected"; ?>>Male</option>
                  <option value="Female" <?php if ($patient['gender']=="Female") echo "selected"; ?>>Female</option>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?php echo $patient['phone_number']; ?>">
              </div>

            </div>

            <div class="mb-3">
              <label>Address</label>
              <textarea name="address" class="form-control"><?php echo $patient['address']; ?></textarea>
            </div>

            <div class="d-flex justify-content-between mt-3">
              <button type="submit" name="update" class="btn btn-primary">Update</button>

              <button type="submit" name="delete" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to delete this patient? This cannot be undone.');">
                Delete Patient
              </button>
            </div>

          </form>

        </div>
      </div>



      <!-- ===============================================-->
      <!-- PRESCRIPTIONS TABLE -->
      <!-- ===============================================-->
      <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Prescriptions</h5>

          <a href="add-prescription.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success btn-sm">
            + Add Prescription
          </a>
        </div>

        <div class="card-body">

          <div class="table-responsive">
            <table id="prescriptionsTable" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Image</th>
                </tr>
              </thead>

              <tbody>
                <?php
                $q = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id=? ORDER BY prescription_id DESC");
                $q->bind_param("i", $patient_id);
                $q->execute();
                $pres = $q->get_result();

                while ($p = $pres->fetch_assoc()) {

    echo '
    <tr>
        <td>'.$p['prescription_id'].'</td>
        <td>'.$p['date_prescribed'].'</td>
        <td>'.$p['description'].'</td>
        <td>

            <form method="POST" onsubmit="return confirm(\'Are you sure you want to delete this prescription?\');" style="display:inline;">
                <input type="hidden" name="delete_prescription_id" value="'.$p['prescription_id'].'">
                <button type="submit" name="delete_prescription" class="btn btn-danger btn-sm">Delete</button>
            </form>

            <a href="'.$p['image_path'].'" target="_blank" class="btn btn-info btn-sm ms-2">
                View
            </a>

        </td>
    </tr>';
}
                $q->close();
                ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>

    </div>
  </div>
</main>

<!-- DATATABLES -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(document).ready(function () {
      $('#prescriptionsTable').DataTable();
  });
</script>

</body>
</html>