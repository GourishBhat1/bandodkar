<?php
include('includes/header.php');
include('connection.php');

// -------------------------------------------------
// CHECK LOGIN VIA COOKIE
// -------------------------------------------------
if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}

// -------------------------------------------------
// CREATE PATIENT
// -------------------------------------------------
if (isset($_POST['create'])) {

    // Sanitize inputs to prevent SQL injection and XSS
    $first = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last  = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));

    // Logged-in user ID
    $userId = intval($_COOKIE['user_id']);

    // PHP timestamps (requested)
    $createdAt = date("Y-m-d H:i:s");
    $updatedAt = date("Y-m-d H:i:s");

    // Insert new patient
    $stmt = $conn->prepare("
        INSERT INTO patients (user_id, first_name, last_name, phone_number, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("isssss", $userId, $first, $last, $phone, $createdAt, $updatedAt);

    if (!$stmt->execute()) {
        // Improve error handling
        die("Execute Error: " . $stmt->error);
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
        document.querySelector('[data-layout]').classList.remove('container');
        document.querySelector('[data-layout]').classList.add('container-fluid');
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

              <div class="col-md-6 mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control">
              </div>

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
