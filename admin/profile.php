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

/* ========== AUTO-UPLOAD HANDLER ========== */
if (isset($_POST['auto_upload']) && isset($_FILES['image'])) {

    $target_dir = "uploads/prescriptions/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $image_name = time() . "_" . basename($_FILES["image"]["name"]);
    $path = $target_dir . $image_name;

    move_uploaded_file($_FILES["image"]["tmp_name"], $path);

    $stmt = $conn->prepare("
        INSERT INTO prescriptions 
        (patient_id, doctor_name, date_prescribed, description, image_path, created_at, updated_at)
        VALUES (?, 'Dr. Bandodkar', CURDATE(), '', ?, NOW(), NOW())
    ");

    $stmt->bind_param("is", $patient_id, $path);
    $stmt->execute();
    $stmt->close();

    exit;
}

/* ========== BULK DELETE ========== */
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

    echo "<script>window.location='profile.php?id=$patient_id';</script>";
    exit;
}
?>

<!-- Fancybox v6 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.css"/>

<style>
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
#deleteBar {
    display: none;
    position: fixed;
    bottom: 40px;
    left: 20px;
    z-index: 900;
}
.delete-btn-floating {
    background: #dc3545;
    border: none;
    padding: 14px 22px;
    color: white;
    border-radius: 50px;
    font-weight: 600;
}
</style>

<main class="main" id="top">
  <div class="container" data-layout="container">

    <?php include('includes/sidebar.php'); ?>
    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <div class="card mb-5">
        <div class="card-header">
          <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>
        </div>

        <div class="card-body">

        <form method="POST">
          <div class="row g-3">

<?php
$q = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id=? ORDER BY prescription_id DESC");
$q->bind_param("i", $patient_id);
$q->execute();
$res = $q->get_result();

while ($p = $res->fetch_assoc()) {
    $img = $p['image_path'];
    $pid = $p['prescription_id'];

    echo '
    <div class="col-12 col-md-6 col-lg-2 position-relative">

        <input type="checkbox"
               class="form-check-input position-absolute"
               style="top:8px; left:8px; transform:scale(1.4);"
               name="prescription_ids[]"
               value="'.$pid.'"
               onchange="toggleDeleteBar()">

        <a href="'.$img.'"
           data-fancybox="gallery"
           data-pid="'.$pid.'">
            <img src="'.$img.'" class="img-fluid rounded shadow-sm"
                 style="height:300px; object-fit:cover; width:100%;">
        </a>

    </div>';
}
?>

          </div>

          <div id="deleteBar">
            <button class="delete-btn-floating" name="delete_selected" type="submit">
              <i class="fa fa-trash"></i>
            </button>
          </div>

        </form>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>
</main>

<!-- Upload floating button -->
<label for="uploadPrescription" class="fab-upload">
  <i class="fas fa-camera"></i>
</label>

<input type="file" id="uploadPrescription"
       style="display:none;" onchange="autoUpload()">

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.umd.js"></script>

<script>
function autoUpload() {
    let f = document.getElementById("uploadPrescription");
    if (!f.files.length) return;

    let form = new FormData();
    form.append("auto_upload", "1");
    form.append("image", f.files[0]);

    fetch("profile.php?id=<?php echo $patient_id; ?>", {
        method: "POST",
        body: form
    }).then(() => location.reload());
}

function toggleDeleteBar() {
    let any = document.querySelectorAll('.form-check-input:checked').length;
    document.getElementById("deleteBar").style.display = any ? "block" : "none";
}

/* ============================================================
   FANCYBOX WITH DELETE BUTTON (Toolbar)
============================================================ */
Fancybox.bind("[data-fancybox='gallery']", {
    Toolbar: {
        display: {
            left: [],
            middle: ["deleteBtn"],
            right: ["zoom", "fullscreen", "download", "close"]
        },
        items: {
            deleteBtn: {
                type: "button",
                html: "<i class='fa fa-trash' style='color:#ff4d4d; font-size:20px;'></i>",
                click: (instance, slide) => {

                    let pid = slide.trigger?.dataset?.pid;

                    if (!pid) {
                        alert("Could not read image ID.");
                        return;
                    }

                    if (!confirm("Delete this image?")) return;

                    fetch("delete-one.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({ id: pid })
                    })
                    .then(() => {
                        instance.close();
                        setTimeout(() => location.reload(), 300);
                    });
                }
            }
        }
    }
});
</script>

</body>
</html>
