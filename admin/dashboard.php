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

          <!-- FULL-WIDTH SEARCH -->
          <div class="mb-3">
            <input id="searchInput" type="search" class="form-control" placeholder="Search by name, ID or phone...">
          </div>

          <!-- SORT DROPDOWN BELOW SEARCH -->
          <div class="mb-2">
            <select id="sortSelect" class="form-select">
              <option value="id_desc">Sort: Newest first</option>
              <option value="id_asc">Sort: Oldest first</option>
              <option value="name_asc">Sort: Name A → Z</option>
              <option value="name_desc">Sort: Name Z → A</option>
            </select>
          </div>

          <small class="text-600">Tap a card to open profile</small>
        </div>

        <div class="card-body">
          <!-- Cards injected here -->
          <div id="cardsContainer" class="row g-3"></div>

          <!-- No results -->
          <div id="noResults" class="text-center text-muted py-4" style="display:none;">
            <p class="mb-0">No patients found.</p>
          </div>

          <!-- Loading spinner -->
          <div id="loading" class="text-center py-4" style="display:none;">
            <div class="spinner-border" role="status"></div>
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
.card-patient {
  transition: transform .08s ease, box-shadow .08s ease;
  cursor: pointer;
}
.card-patient:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* Remove avatar → adjust padding */
.card-patient .no-avatar-space {
  padding-left: 0 !important;
}

.patient-meta small {
  color: #6c757d;
}

/* Floating Create Button (FAB) */
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
  box-shadow: 0 4px 16px rgba(0,0,0,0.25);
  font-size: 28px;
  text-decoration: none;
}
.fab-create:hover {
  background: #0b5ed7;
  color: #fff;
}
</style>

<!-- FLOATING BUTTON -->
<a href="create-patient.php" class="fab-create">
  <i class="fas fa-plus"></i>
</a>

<!-- AJAX SEARCH + SORT -->
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
  const sanitizedName = escapeHtml(name || 'No Name');

  const phone = patient.phone_number || '—';
  const id = patient.patient_id;
  const created = patient.created_at ? escapeHtml(patient.created_at) : '—';

  return `
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
      <div class="card card-patient h-100 no-avatar-space" onclick="location.href='profile.php?id=${id}'">
        <div class="card-body">

          <h6 class="mb-1">${sanitizedName}</h6>

          <div class="patient-meta mb-2">
            <small class="d-block"><strong>ID:</strong> ${id}</small>
            <small class="d-block"><strong>Phone:</strong> ${escapeHtml(phone)}</small>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <a href="profile.php?id=${id}" class="btn btn-sm btn-outline-primary">View Profile</a>
            <small class="text-muted">${created}</small>
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
    const res = await fetch(`get-patients.php?q=${q}&sort=${sort}`);
    if (!res.ok) throw new Error();

    const data = await res.json();
    loading.style.display = 'none';

    if (!data.length) {
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

searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPatients, 300);
});

sortSelect.addEventListener('change', fetchPatients);

window.addEventListener('load', fetchPatients);
</script>

</body>
</html>
