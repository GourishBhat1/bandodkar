<?php
include('includes/header.php');
include('connection.php');

// ------------------------------------
// CREATE PATIENT
// ------------------------------------
if (isset($_POST['create'])) {

    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $dob = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $phone = $_POST['phone_number'];

    // Logged-in user
    $created_by = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : 0;

    // Insert with user_id
    $stmt = $conn->prepare("
        INSERT INTO patients (user_id, first_name, last_name, birth_date, gender, address, phone_number, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("issssss", $created_by, $first, $last, $dob, $gender, $address, $phone);

    if (!$stmt->execute()) {
        die("Execute Failed: " . $stmt->error);  // Debug
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    echo "<script>alert('Patient created successfully!'); window.location='profile.php?id=$newId';</script>";
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
      <!-- CREATE PATIENT FORM -->
      <!-- ===============================================-->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Create New Patient</h5>
          <a href="index.php" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="card-body">

          <form method="POST">

            <div class="row">
              <div class="col-md-6 mb-3">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" required>
              </div>

              <div class="col-md-6 mb-3">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control">
              </div>
            </div>

            <div class="row">

              <div class="col-md-4 mb-3">
                <label>Date of Birth</label>
                <input type="date" name="birth_date" class="form-control">
              </div>

              <div class="col-md-4 mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                  <option value="">-- Select --</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control">
              </div>

            </div>

            <div class="mb-3">
              <label>Address</label>
              <textarea name="address" class="form-control"></textarea>
            </div>

            <div class="mt-3">
              <button type="submit" name="create" class="btn btn-primary">Create Patient</button>
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