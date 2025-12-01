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

$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found!'); window.location='dashboard.php';</script>";
    exit;
}

/* -----------------------------
   AUTO-UPLOAD (single or multiple)
   - If 'auto_upload' present and files are uploaded
   - Accepts:
     - single camera upload: input name = 'image' (single file)
     - multiple gallery upload: input name = 'images[]' (multiple files)
   - Only accepts jpg/jpeg/png
   - On error: prints JS alert (error) and exits
   - On success: silent exit (client reloads)
------------------------------*/
if (isset($_POST['auto_upload'])) {

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $allowed_ext   = ['jpg', 'jpeg', 'png'];

    // Normalize files: either single file 'image' or multiple 'images'
    $files_to_process = [];

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // single camera file
        $files_to_process[] = [
            'tmp_name' => $_FILES['image']['tmp_name'],
            'name'     => $_FILES['image']['name'],
            'type'     => $_FILES['image']['type'],
            'error'    => $_FILES['image']['error'],
            'size'     => $_FILES['image']['size'],
        ];
    } elseif (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        // multiple gallery files
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $files_to_process[] = [
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
        }
    } else {
        // nothing to upload
        echo "<script>alert('No files were uploaded.');</script>";
        exit;
    }

    if (empty($files_to_process)) {
        echo "<script>alert('No files were uploaded.');</script>";
        exit;
    }

    $target_dir = "uploads/prescriptions/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Process each file
    foreach ($files_to_process as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('Error uploading file \"".htmlspecialchars($f['name'])."\".');</script>";
            exit;
        }

        $file_type = $f['type'];
        $file_ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('Invalid file type for \"".htmlspecialchars($f['name'])."\". Only JPEG and PNG are allowed.');</script>";
            exit;
        }

        $image_name = time() . "_" . bin2hex(random_bytes(6)) . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $f['name']);
        $path = $target_dir . $image_name;

        if (!move_uploaded_file($f['tmp_name'], $path)) {
            echo "<script>alert('Error saving file \"".htmlspecialchars($f['name'])."\". Please try again later.');</script>";
            exit;
        }

        // Insert record
        $stmt = $conn->prepare("
            INSERT INTO prescriptions 
            (patient_id, doctor_name, date_prescribed, description, image_path, created_at, updated_at)
            VALUES (?, 'Dr. Bandodkar', CURDATE(), '', ?, NOW(), NOW())
        ");
        $stmt->bind_param("is", $patient_id, $path);
        if (!$stmt->execute()) {
            // rollback file if DB insert fails
            if (file_exists($path)) unlink($path);
            echo "<script>alert('Database error while saving \"".htmlspecialchars($f['name'])."\".');</script>";
            $stmt->close();
            exit;
        }
        $stmt->close();
    }

    // Success — silent (client will handle reload)
    exit;
}

/* -----------------------------
   BULK DELETE (POST form)
------------------------------*/
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['prescription_ids']) && is_array($_POST['prescription_ids'])) {
        foreach ($_POST['prescription_ids'] as $pid) {
            $pid = intval($pid);
            $getImg = $conn->prepare("SELECT image_path FROM prescriptions WHERE prescription_id = ?");
            $getImg->bind_param("i", $pid);
            $getImg->execute();
            $imgRes = $getImg->get_result()->fetch_assoc();
            $getImg->close();

            if ($imgRes && file_exists($imgRes['image_path'])) unlink($imgRes['image_path']);

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
<!-- GLightbox -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Floating Upload Speed-Dial */
.fab-wrapper {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1100;
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

/* main fab */
.fab-main {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
    cursor: pointer;
}

/* mini fabs */
.fab-mini {
    width:44px;
    height:44px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    color:white;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
    cursor:pointer;
    transform-origin: center;
}

/* camera mini */
.fab-camera { background:#198754; }
/* gallery mini */
.fab-gallery { background:#6f42c1; }

/* Delete button (left) */
#deleteBar {
    display:none;
    position: fixed;
    left: 20px;
    bottom: 25px;
    z-index: 1100;
}

/* Circular delete */
.delete-btn-floating {
    background: #dc3545;
    border: none;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    color: white;
    font-size: 18px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
    cursor: pointer;
}

/* Upload progress center (between delete and upload fabs) */
#uploadProgress {
    display:none;
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1100;
    width: 260px;
}

/* small progress bar inside */
#uploadProgress .progress {
    height: 10px;
    border-radius: 8px;
    overflow: hidden;
}
#uploadProgressBar {
    height: 10px;
    width: 0%;
}
</style>

<main class="main" id="top">
  <div class="container" data-layout="container">
    <?php include('includes/sidebar.php'); ?>

    <div class="content">
      <?php include('includes/navbar.php'); ?>

      <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>
            </div>
            <div>
                <!-- patient quick info (optional) -->
                <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['last_name'] ?? '')); ?></strong>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" id="bulkDeleteForm">
                <div class="row g-3">
                    <?php
                    $q = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id=? ORDER BY prescription_id DESC");
                    $q->bind_param("i", $patient_id);
                    $q->execute();
                    $res = $q->get_result();

                    if ($res->num_rows === 0) {
                        echo "<div class='col-12'><p class='text-muted'>No prescriptions uploaded.</p></div>";
                    }

                    while ($p = $res->fetch_assoc()) {
                        $img = $p['image_path'];
                        $pid = $p['prescription_id'];

                        echo "
                        <div class='col-12 col-md-6 col-lg-2 position-relative'>
                            <input type='checkbox'
                                class='form-check-input position-absolute'
                                style='top:8px; left:8px; transform:scale(1.3);'
                                name='prescription_ids[]'
                                value='$pid'
                                onchange='toggleDeleteBar()'>

                            <a href='".htmlspecialchars($img, ENT_QUOTES)."' class='glightbox'>
                                <img src='".htmlspecialchars($img, ENT_QUOTES)."' class='img-fluid rounded shadow-sm'
                                    style='height:300px; object-fit:cover; width:100%;'>
                            </a>
                        </div>";
                    }
                    ?>
                </div>

                <input type="hidden" name="delete_selected" value="1">

                <div id="deleteBar">
                    <button type="button" class="delete-btn-floating" onclick="confirmBulkDelete()">
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

<!-- Hidden file inputs -->
<!-- Camera: single capture -->
<input type="file" id="uploadCamera" accept="image/*" capture="environment" style="display:none" />

<!-- Gallery: multiple selection -->
<input type="file" id="uploadGallery" accept="image/*" multiple style="display:none" />

<!-- Speed-dial FABs -->
<div class="fab-wrapper" aria-hidden="false">
    <div id="fabMiniContainer" style="display:flex; gap:10px; align-items:center;">
        <!-- Mini buttons are initially hidden (we'll show them when main is toggled) -->
        <button id="fabGallery" class="fab-mini fab-gallery" title="Upload from Gallery" style="display:none;">
            <i class="fa fa-images"></i>
        </button>
        <button id="fabCamera" class="fab-mini fab-camera" title="Take a Photo" style="display:none;">
            <i class="fa fa-camera"></i>
        </button>
    </div>

    <div id="fabMain" class="fab-main" title="Upload">
        <i id="fabMainIcon" class="fa fa-plus"></i>
    </div>
</div>

<!-- Upload progress UI -->
<div id="uploadProgress">
    <div class="progress">
        <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar" style="width:0%">0%</div>
    </div>
</div>

<!-- GLightbox JS -->
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
/* Initialize GLightbox (no description/captions) */
document.addEventListener("DOMContentLoaded", function() {
    GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        zoomable: true,
        // Keep default UI; we are not adding delete/caption there.
    });
});

/* Toggle mini fabs (speed-dial) */
const fabMain = document.getElementById('fabMain');
const fabMainIcon = document.getElementById('fabMainIcon');
const fabGallery = document.getElementById('fabGallery');
const fabCamera = document.getElementById('fabCamera');
let fabOpen = false;

fabMain.addEventListener('click', () => {
    fabOpen = !fabOpen;
    if (fabOpen) {
        fabGallery.style.display = 'flex';
        fabCamera.style.display = 'flex';
        fabMainIcon.className = 'fa fa-times';
    } else {
        fabGallery.style.display = 'none';
        fabCamera.style.display = 'none';
        fabMainIcon.className = 'fa fa-plus';
    }
});

/* Wire mini fabs to file inputs */
const uploadCamera = document.getElementById('uploadCamera');
const uploadGallery = document.getElementById('uploadGallery');

document.getElementById('fabCamera').addEventListener('click', () => {
    // Close speed-dial for neatness
    fabOpen = false;
    fabGallery.style.display = 'none';
    fabCamera.style.display = 'none';
    fabMainIcon.className = 'fa fa-plus';
    uploadCamera.click();
});

document.getElementById('fabGallery').addEventListener('click', () => {
    fabOpen = false;
    fabGallery.style.display = 'none';
    fabCamera.style.display = 'none';
    fabMainIcon.className = 'fa fa-plus';
    uploadGallery.click();
});

/* When user selects a single camera file */
uploadCamera.addEventListener('change', function() {
    if (!this.files || !this.files.length) return;
    const file = this.files[0];
    uploadFiles([file]); // wrap as array for unified handler
});

/* When user selects multiple gallery files */
uploadGallery.addEventListener('change', function() {
    if (!this.files || !this.files.length) return;
    const filesArr = Array.from(this.files);
    uploadFiles(filesArr);
});

/* Upload files via XHR to profile.php (auto_upload=1)
   Shows center progress bar; supports multiple files. */
function uploadFiles(files) {
    // Basic client-side validation (types)
    const allowed = ['image/jpeg','image/png','image/jpg'];
    for (let f of files) {
        if (!allowed.includes(f.type)) {
            alert('Only JPEG/PNG images are allowed.');
            return;
        }
    }

    // Show progress UI
    const progressWrap = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('uploadProgressBar');
    progressWrap.style.display = 'block';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';

    const xhr = new XMLHttpRequest();
    const fd = new FormData();
    fd.append('auto_upload', '1');

    // Append files: if more than 1, name 'images[]', else name 'image'
    if (files.length === 1) {
        fd.append('image', files[0]);
    } else {
        for (let i=0;i<files.length;i++) {
            fd.append('images[]', files[i]);
        }
    }

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
        }
    });

    xhr.onload = function() {
        progressBar.style.width = '100%';
        progressBar.textContent = '100%';
        setTimeout(() => {
            progressWrap.style.display = 'none';
            // reload to fetch new images
            location.reload();
        }, 350);
    };

    xhr.onerror = function() {
        progressWrap.style.display = 'none';
        alert('Upload failed. Please check your connection and try again.');
    };

    xhr.open('POST', 'profile.php?id=<?php echo $patient_id;?>', true);
    xhr.send(fd);
}

/* Show/hide delete bar */
function toggleDeleteBar() {
    const any = document.querySelectorAll('.form-check-input:checked').length;
    document.getElementById('deleteBar').style.display = any ? 'block' : 'none';
}

/* SweetAlert confirm for bulk delete */
function confirmBulkDelete() {
    Swal.fire({
        title: "Delete selected images?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Delete"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('bulkDeleteForm').submit();
        }
    });
}
</script>
</body>
</html>
