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
    echo "<script>alert('Invalid patient!'); window.location='index.php';</script>";
    exit;
}

$patient_id = intval($_GET['patient_id']);

// ------------------------------------
// Add Prescription
// ------------------------------------
if (isset($_POST['add'])) {

    $doctor_name = "Dr. Bandodkar"; 
    $date_prescribed = $_POST['date_prescribed'];
    $description = $_POST['description_html']; // Quill output (hidden input)

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
        INSERT INTO prescriptions (patient_id, doctor_name, date_prescribed, description, image_path, created_at, updated_at)
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
        var container = document.querySelector('[data-layout]');
        container.classList.remove('container');
        container.classList.add('container-fluid');
      }
    </script>

    <?php include('includes/sidebar.php'); ?>

    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- ===============================================-->
      <!-- ADD PRESCRIPTION FORM -->
      <!-- ===============================================-->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Add Prescription</h5>
          <a href="profile.php?id=<?php echo $patient_id; ?>" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="card-body">

          <form method="POST" enctype="multipart/form-data" onsubmit="return submitDescription();">

            <div class="row mb-3">
              <div class="col-md-4">
                <label>Date Prescribed</label>
                <input type="date" name="date_prescribed" class="form-control" required>
              </div>

              <div class="col-md-8">
                <label>Prescription Image *</label>
                <input type="file" name="image" accept="image/*" required class="form-control">
              </div>
            </div>

            <div class="mb-3">
              <label>Description</label>

              <!-- Quill Editor -->
              <div id="quill-editor" style="height: 250px; background: white;"></div>

              <!-- Hidden input to store Quill HTML -->
              <input type="hidden" name="description_html" id="description_html">
            </div>

            <button type="submit" name="add" class="btn btn-success">Add Prescription</button>

          </form>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>

    </div>
  </div>
</main>


<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
  // Initialize Quill Editor
  var quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Write prescription details here...',
    modules: {
      toolbar: [
        [{ header: [1, 2, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
      ]
    }
  });

  // Transfer Quill HTML to hidden input before submit
  function submitDescription() {
    var html = quill.root.innerHTML;
    document.getElementById('description_html').value = html;
    return true;
  }
</script>

</body>
</html>