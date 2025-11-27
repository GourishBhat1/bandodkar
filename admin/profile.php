<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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


/* ------------------------------------------------------------------
    AUTO UPLOAD IMAGE FROM FLOATING BUTTON
------------------------------------------------------------------ */
if (isset($_POST['auto_upload']) && isset($_FILES['image'])) {

    $target_dir = "uploads/prescriptions/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;

    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    $stmt = $conn->prepare("
        INSERT INTO prescriptions 
        (patient_id, doctor_name, date_prescribed, description, image_path, created_at, updated_at)
        VALUES (?, 'Dr. Bandodkar', CURDATE(), '', ?, NOW(), NOW())
    ");

    $stmt->bind_param("is", $patient_id, $target_file);
    $stmt->execute();
    $stmt->close();

    exit; // AJAX upload done
}


/* ------------------------------------------------------------------
    DELETE SELECTED PRESCRIPTIONS
------------------------------------------------------------------ */
if (isset($_POST['delete_selected'])) {

    if (!empty($_POST['prescription_ids'])) {

        foreach ($_POST['prescription_ids'] as $pid) {

            $pid = intval($pid);

            $getImg = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id = ?");
            $getImg->bind_param("i", $pid);
            $getImg->execute();
            $imgRes = $getImg->get_result()->fetch_assoc();
            $getImg->close();

            if ($imgRes && file_exists($imgRes['image_path'])) {
                unlink($imgRes['image_path']);
            }

            $del = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
            $del->bind_param("i", $pid);
            $del->execute();
            $del->close();
        }
    }

    echo "<script>alert('Selected prescriptions deleted successfully'); window.location='profile.php?id=$patient_id';</script>";
    exit;
}

?>

<!-- GLightbox CSS -->
<link href="vendors/glightbox/glightbox.min.css" rel="stylesheet">

<style>
/* Floating Upload Button */
.fab-upload {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 999;
    background: #198754;
    color: white;
    border-radius: 50%;
    width: 62px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
    cursor: pointer;
}
.fab-upload:hover {
    background: #157347;
}

/* Floating Delete Bar */
#deleteBar {
    display: none;
    position: fixed;
    bottom: 105px;
    right: 20px;
    z-index: 900;
}

.delete-btn-floating {
    background: #dc3545;
    border: none;
    padding: 14px 22px;
    color: white;
    border-radius: 50px;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
}
</style>

<main class="main" id="top">
  <div class="container" data-layout="container">

    <?php include('includes/sidebar.php'); ?>
    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- Prescription Gallery -->
      <div class="card mb-5">
        <div class="card-header d-flex justify-content-start align-items-center">
          
          <!-- BACK BUTTON -->
          <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>

        </div>

        <div class="card-body">
        <form method="POST">
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
                <div class="col-12 col-md-12 col-lg-2 position-relative">

                    <!-- Checkbox -->
                    <input type="checkbox" 
                        class="form-check-input position-absolute select-box"
                        style="top:8px; left:8px; transform:scale(1.4);"
                        name="prescription_ids[]" 
                        value="'.$p['prescription_id'].'"
                        onchange="toggleDeleteBar()">

                    <!-- GLightbox -->
                    <a href="'.$img.'" class="glightbox" data-gallery="prescriptions">
                        <img src="'.$img.'" class="img-fluid rounded shadow-sm" 
                        style="height:300px; object-fit:cover; width:100%;">
                    </a>
                </div>';
            }
            ?>
          </div>

          <!-- Floating Delete Button -->
          <div id="deleteBar">
            <button class="delete-btn-floating" name="delete_selected" type="submit">
              Delete Selected
            </button>
          </div>

        </form>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>
</main>

<!-- Floating Camera/Gallery Upload Button -->
<label for="uploadPrescription" class="fab-upload">
  <i class="fas fa-camera"></i>
</label>

<input type="file" id="uploadPrescription" accept="image/*" capture="environment"
       style="display:none;" onchange="autoUpload()">

<!-- GLightbox JS -->
<script src="vendors/glightbox/glightbox.min.js"></script>

<script>
let lightbox;

// Refresh GLightbox when page loads
function refreshLightbox() {
    if (lightbox) {
        try { lightbox.destroy(); } catch(e) {}
    }

    lightbox = GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        zoomable: true,
    });
}

// Auto-upload image selected from floating button
function autoUpload() {
    const fileInput = document.getElementById("uploadPrescription");
    if (!fileInput.files.length) return;

    const form = new FormData();
    form.append("auto_upload", "1");
    form.append("image", fileInput.files[0]);

    fetch("profile.php?id=<?php echo $patient_id; ?>", {
        method: "POST",
        body: form
    })
    .then(() => location.reload())
    .catch(() => alert("Upload failed"));
}

// Show delete button only if checkboxes selected
function toggleDeleteBar() {
    const checked = document.querySelectorAll('.select-box:checked').length;
    document.getElementById("deleteBar").style.display = checked ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", () => {
    setTimeout(refreshLightbox, 150);
});
</script>

</body>
</html>
