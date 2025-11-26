<?php
include('includes/header.php');

if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}
?>
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
      <?php include('includes/navbar.php');?>

      <div class="card mb-4">
        <div class="card-header">

          <!-- SEARCH -->
          <div class="mb-3">
            <input id="searchInput" type="search" class="form-control"
                   placeholder="Search by name, ID or phone...">
          </div>

          <!-- SORT -->
          <div class="mb-2">
            <select id="sortSelect" class="form-select">
              <option value="id_desc">Newest First (ID ↓)</option>
              <option value="id_asc">Oldest First (ID ↑)</option>
              <option value="name_asc">Name A → Z</option>
              <option value="name_desc">Name Z → A</option>
            </select>
          </div>

        </div>

        <div class="card-body">
          <div id="cardsContainer" class="row g-3"></div>

          <div id="noResults" class="text-center text-muted py-4" style="display:none;">
            <p class="mb-0">No patients found.</p>
          </div>

          <div id="loading" class="text-center py-4" style="display:none;">
            <div class="spinner-border"></div>
            <div class="mt-2">Loading...</div>
          </div>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>
</main>

<!-- CARD UI -->
<style>
.patient-card {
  transition: transform .08s ease, box-shadow .08s ease;
  cursor: pointer;
  position: relative;
}
.patient-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.patient-meta small {
  color: #6c757d;
}

/* Three-dot dropdown button */
.card-dots .btn {
  padding: 4px 6px;
  font-size: 14px;
  color: #6c757d;
}

.card-dots .dropdown-menu {
  min-width: 160px;
  font-size: 14px;
}

.dropdown-menu .dropdown-item i {
  width: 18px;
}

/* Floating Create Button */
.fab-create {
  position: fixed;
  bottom: 25px;
  right: 25px;
  z-index: 999;
  background: #0d6efd;
  color: white;
  border-radius: 50%;
  width: 62px;
  height: 62px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.25);
  text-decoration: none;
}
.fab-create:hover {
  background: #0b5ed7;
}
</style>

<!-- FLOATING BUTTON -->
<a href="create-patient.php" class="fab-create">
  <i class="fas fa-plus"></i>
</a>

<script>
const cardsContainer = document.getElementById('cardsContainer');
const searchInput = document.getElementById('searchInput');
const sortSelect   = document.getElementById('sortSelect');
const noResults    = document.getElementById('noResults');
const loading      = document.getElementById('loading');

let debounceTimer = null;

function escapeHtml(unsafe) {
    if (!unsafe && unsafe !== 0) return '';
    return String(unsafe)
      .replaceAll('&', "&amp;")
      .replaceAll('<', "&lt;")
      .replaceAll('>', "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
}

function buildCard(patient) {
  const name = (patient.first_name || '') + (patient.last_name ? ' ' + patient.last_name : '');
  const displayName = escapeHtml(name || 'No Name');
  const phone = escapeHtml(patient.phone_number || '—');
  const id = patient.patient_id;
  const created = patient.created_at ? escapeHtml(patient.created_at) : '—';

  return `
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
      <div class="card patient-card h-100" onclick="location.href='profile.php?id=${id}'">

        <!-- THREE DOT DROPDOWN -->
        <div class="dropdown position-absolute top-0 end-0 p-2 card-dots"
             onclick="event.stopPropagation();">

          <button class="btn btn-link text-muted btn-sm dropdown-toggle dropdown-caret-none"
                  data-bs-toggle="dropdown" type="button">
            <span class="fas fa-ellipsis-h"></span>
          </button>

          <div class="dropdown-menu dropdown-menu-end shadow-sm">

            <a class="dropdown-item" href="edit-patient.php?id=${id}">
              <i class="fas fa-edit text-primary me-2"></i> Edit
            </a>

            <button class="dropdown-item text-danger"
                    onclick="deletePatient(${id}); event.stopPropagation();">
              <i class="fas fa-trash me-2"></i> Delete
            </button>

          </div>
        </div>

        <!-- MAIN CARD BODY -->
        <div class="card-body">

          <h6 class="mb-1">${displayName}</h6>

          <div class="patient-meta mb-2">
            <small class="d-block"><strong>ID:</strong> ${id}</small>
            <small class="d-block"><strong>Phone:</strong> ${phone}</small>
          </div>

          

        </div>
      </div>
    </div>
  `;
}

async function fetchPatients() {
  loading.style.display = 'block';
  noResults.style.display = 'none';
  cardsContainer.innerHTML = '';

  const q = encodeURIComponent(searchInput.value.trim());
  const sort = encodeURIComponent(sortSelect.value);

  try {
    // Prevent browser caching
    const res = await fetch(`get-patients.php?q=${q}&sort=${sort}&_=${Date.now()}`, {
      cache: "no-store"
    });

    if (res.status === 401) {
      // cookie expired → force logout
      window.location = "logout.php";
      return;
    }

    if (!res.ok) {
      throw new Error("Network error");
    }

    const data = await res.json();
    console.log("Patients:", data);

    loading.style.display = 'none';

    if (!Array.isArray(data) || data.length === 0) {
      noResults.style.display = 'block';
      return;
    }

    // PHP already sorts based on dropdown
    cardsContainer.innerHTML = data.map(buildCard).join('');

  } catch (err) {
    console.error("Fetch error:", err);
    loading.style.display = 'none';
    noResults.style.display = 'block';
    noResults.innerHTML = `<p class="text-danger">Unable to load patients.</p>`;
  }
}


function deletePatient(id) {
  if (!confirm("Are you sure you want to delete this patient? This cannot be undone.")) return;

  fetch("delete-patient.php?id=" + id)
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        alert("Patient deleted successfully");
        fetchPatients();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(() => alert("Error deleting patient."));
}

searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPatients, 300);
});

sortSelect.addEventListener('change', fetchPatients);

window.addEventListener('load', () => fetchPatients(Date.now()));
</script>

</body>
</html>
