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
?>
<!-- GLightbox CSS (Falcon uses glightbox) -->
<link href="vendors/glightbox/glightbox.min.css" rel="stylesheet">

<main class="main" id="top">
  <div class="container" data-layout="container">

    <?php include('includes/sidebar.php'); ?>
    <div class="content">

      <?php include('includes/navbar.php'); ?>

      <!-- Prescription Gallery Card -->
      <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
          <!-- replace title text with Back button as requested -->
          <div>
            <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>
          </div>

          <!-- Helper / info -->
          <div class="text-muted small">Select images to delete (use the checkbox)</div>
        </div>

        <div class="card-body">
          <form id="galleryForm" method="post">
            <div class="row g-3" id="prescriptionGrid">

              <?php
              $q = $conn->prepare("SELECT prescription_id, image_path FROM prescriptions WHERE patient_id=? ORDER BY prescription_id DESC");
              $q->bind_param("i", $patient_id);
              $q->execute();
              $result = $q->get_result();

              if ($result->num_rows == 0) {
                  echo "<p class='text-muted'>No prescriptions uploaded.</p>";
              }

              while ($p = $result->fetch_assoc()) {
                  $img = htmlspecialchars($p['image_path'], ENT_QUOTES);
                  $pid = intval($p['prescription_id']);

                  echo '
                  <div class="col-6 col-md-3 col-lg-2 prescription-item" data-prescription-id="'.$pid.'">
                    <div class="position-relative rounded shadow-sm overflow-hidden">
                      <!-- GLightbox Trigger -->
                      <a href="'.$img.'" class="glightbox" data-gallery="prescriptions" data-title="">
                        <img src="'.$img.'" class="img-fluid" style="height:150px; object-fit:cover; width:100%;" alt="Prescription">
                      </a>

                      <!-- checkbox overlay -->
                      <div class="checkbox-overlay">
                        <input type="checkbox" class="form-check-input pres-checkbox" value="'.$pid.'" />
                      </div>
                    </div>
                  </div>';
              }
              $q->close();
              ?>
            </div>
          </form>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>
</main>

<!-- Floating Add Button -->
<style>
/* Floating Add Button */
.fab-add {
    position: fixed;
    bottom: 92px; /* leave room for delete btn */
    right: 25px;
    z-index: 999;
    background: #198754;
    color: white;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    text-decoration: none;
}
.fab-add:hover{ background:#157347; color:#fff; }

/* Floating Delete Selected Button (hidden by default) */
.fab-delete {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 999;
    background: #dc3545;
    color: white;
    border-radius: 28px;
    min-width: 56px;
    height: 56px;
    display: none; /* hidden until selection */
    align-items: center;
    justify-content: center;
    gap:10px;
    padding: 0 14px;
    font-size: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    border: none;
}
.fab-delete.show { display:flex; }

/* checkbox overlay position */
.prescription-item { position: relative; }
.checkbox-overlay {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 30;
    background: rgba(255,255,255,0.9);
    padding: 3px;
    border-radius: 6px;
}
.checkbox-overlay .form-check-input {
    width: 18px;
    height: 18px;
    margin: 0;
}

/* ensure GLightbox modal doesn't overlap bottom fixed button badly */
.glightbox-clean .gslide {
  padding-bottom: 36px; /* give extra space to avoid overlap on small screens */
}
</style>

<!-- Floating Buttons -->
<a href="add-prescription.php?patient_id=<?php echo $patient_id; ?>" class="fab-add" title="Add Prescription">
  <i class="fas fa-camera"></i>
</a>

<button id="deleteSelectedBtn" class="fab-delete" title="Delete selected">
  <i class="fas fa-trash"></i>
  <span id="deleteCount" style="font-weight:600; margin-left:6px;"></span>
</button>

<!-- GLightbox JS -->
<script src="vendors/glightbox/glightbox.min.js"></script>

<script>
/* initialize GLightbox */
const lightbox = GLightbox({
    selector: '.glightbox',
    touchNavigation: true,
    loop: true,
    zoomable: true,
});

/* selection + delete logic */
const deleteBtn = document.getElementById('deleteSelectedBtn');
const deleteCount = document.getElementById('deleteCount');
const checkboxes = () => Array.from(document.querySelectorAll('.pres-checkbox'));
const grid = document.getElementById('prescriptionGrid');

function updateDeleteUI() {
  const checked = checkboxes().filter(c => c.checked).map(c => c.value);
  if (checked.length > 0) {
    deleteBtn.classList.add('show');
    deleteCount.textContent = checked.length;
  } else {
    deleteBtn.classList.remove('show');
    deleteCount.textContent = '';
  }
}

document.addEventListener('change', function (e) {
  if (e.target && e.target.classList.contains('pres-checkbox')) {
    updateDeleteUI();
  }
});

/* batch delete handler */
deleteBtn.addEventListener('click', function (e) {
  const checkedInputs = checkboxes().filter(c => c.checked);
  if (checkedInputs.length === 0) return;

  if (!confirm("Delete selected prescriptions? This cannot be undone.")) return;

  const ids = checkedInputs.map(i => parseInt(i.value, 10));

  // disable button while working
  deleteBtn.disabled = true;
  deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;Deleting';

  fetch('delete-prescriptions.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      patient_id: <?php echo $patient_id; ?>,
      ids: ids
    }),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    deleteBtn.disabled = false;
    deleteBtn.innerHTML = '<i class="fas fa-trash"></i><span id="deleteCount"></span>';
    if (data && data.status === 'success') {
      // remove deleted items from DOM
      ids.forEach(id => {
        const el = document.querySelector('.prescription-item[data-prescription-id="'+id+'"]');
        if (el) el.remove();
      });
      updateDeleteUI();
      // If no images left show empty message (simple check)
      if (!document.querySelector('.prescription-item')) {
        grid.innerHTML = "<p class='text-muted'>No prescriptions uploaded.</p>";
      }
      alert('Deleted ' + data.deleted_count + ' item(s).');
    } else {
      alert('Delete failed: ' + (data.message || 'unknown error'));
    }
  })
  .catch(err => {
    deleteBtn.disabled = false;
    deleteBtn.innerHTML = '<i class="fas fa-trash"></i><span id="deleteCount"></span>';
    console.error(err);
    alert('Network error while deleting. Try again.');
  });
});

/* accessibility: allow click on image to open lightbox without toggling checkbox */
document.querySelectorAll('.glightbox').forEach(a=>{
  a.addEventListener('click', function(e){
    // Do not stop propagation; GLightbox will open.
  });
});
</script>

</body>
</html>
