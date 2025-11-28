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

          <!-- SEARCH + SORT BUTTON ROW -->
          <div class="d-flex align-items-center">
            
            <!-- SEARCH -->
            <input id="searchInput" type="search" 
              class="form-control me-2"
              placeholder="Search by name, ID or phone...">

            <!-- SORT BUTTON -->
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle"
                      type="button" id="sortBtn"
                      data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-sort"></i>
              </button>

              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="setSort('id_desc')">Newest First (ID ↓)</a></li>
                <li><a class="dropdown-item" href="#" onclick="setSort('id_asc')">Oldest First (ID ↑)</a></li>
                <li><a class="dropdown-item" href="#" onclick="setSort('name_asc')">Name A → Z</a></li>
                <li><a class="dropdown-item" href="#" onclick="setSort('name_desc')">Name Z → A</a></li>
              </ul>
            </div>
          </div>

          <!-- HIDDEN SELECT (kept for compatibility) -->
          <select id="sortSelect" class="d-none">
            <option value="id_desc">Newest First (ID ↓)</option>
            <option value="id_asc">Oldest First (ID ↑)</option>
            <option value="name_asc">Name A → Z</option>
            <option value="name_desc">Name Z → A</option>
          </select>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

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

.card-dots .btn {
  padding: 4px 6px;
  font-size: 14px;
  color: #6c757d;
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
const searchInput   = document.getElementById('searchInput');
const sortSelect    = document.getElementById('sortSelect');
const noResults     = document.getElementById('noResults');
const loading       = document.getElementById('loading');

let debounceTimer = null;

// Update hidden select + refresh
function setSort(value) {
  sortSelect.value = value;
  fetchPatients();
}

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

  return `
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
      <div class="card patient-card h-100" onclick="location.href='profile.php?id=${id}'">

        <div class="dropdown position-absolute top-0 end-0 p-2 card-dots"
             onclick="event.stopPropagation();">

          <button class="btn btn-link text-muted btn-sm dropdown-toggle dropdown-caret-none"
                  data-bs-toggle="dropdown" type="button">
            <span class="fas fa-ellipsis-v"></span>
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

        <div class="card-body">
          <h6 class="mb-1">${displayName}</h6>
          <div class="patient-meta mb-2">
            <small><strong>ID:</strong> ${id}</small><br>
            <small><strong>Phone:</strong> ${phone}</small>
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
  const sort = sortSelect.value;

  try {
    const res = await fetch(`get-patients.php?q=${q}&sort=${sort}&_=${Date.now()}`, {
      cache: "no-store"
    });

    if (res.status === 401) {
      window.location = "logout.php";
      return;
    }

    const data = await res.json();

    loading.style.display = 'none';

    if (!Array.isArray(data) || data.length === 0) {
      noResults.style.display = 'block';
      return;
    }

    cardsContainer.innerHTML = data.map(buildCard).join('');

  } catch (err) {
    loading.style.display = 'none';
    noResults.style.display = 'block';
    noResults.innerHTML = `<p class="text-danger">Unable to load patients.</p>`;
  }
}

async function deletePatient(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this action.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  });
  
  if (result.isConfirmed) {
    fetch("delete-patient.php?id=" + id)
      .then(res => res.json())
      .then(data => {
        if (data.status === "success") {
          Swal.fire({
            title: 'Deleted successfully',
            icon: 'success'
          });
          fetchPatients();
        } else {
          Swal.fire({
            title: 'Error',
            text: data.message,
            icon: 'error'
          });
        }
      })
      .catch(() => {
        Swal.fire({
          title: 'Error deleting patient',
          icon: 'error'
        });
      });
  }
}

searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPatients, 300);
});

window.addEventListener('load', fetchPatients);
</script>

<script>
/* Check if running as PWA */
function isPWA() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           navigator.standalone === true;
}

/* Safe exit logic */
function tryExitPWA() {
    // 1. Try hard close (may succeed on Android Chrome)
    window.close();

    // 2. Try Android WebView API (Cordova-style)
    if (navigator.app && navigator.app.exitApp) {
        navigator.app.exitApp();
    }

    // 3. Fallback: Go to a blank local endpoint (clean exit illusion)
    window.location.href = "about:blank";
}

/* Enable exit on BACK button ONLY on dashboard */
if (isPWA()) {

    // Push a state so back button triggers popstate
    history.pushState({ pwaExit: true }, "");

    window.addEventListener("popstate", function (event) {
        if (event.state && event.state.pwaExit) {
            tryExitPWA();
        } else {
            // Normal navigation backup
            history.back();
        }
    });
}
</script>


</body>
</html>
