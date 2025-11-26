<nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
  <div class="d-flex align-items-center">
    <div class="toggle-icon-wrapper">
      <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle"
              data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation">
        <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
      </button>
    </div>

    <!-- BRAND -->
    <a class="navbar-brand" href="dashboard.php">
      <div class="d-flex align-items-center py-3">
        <!-- <img class="me-2" src="assets/img/icons/clinic-logo.png" alt="" width="40"> -->
        <span class="font-sans-serif fw-bold">Rec Vault</span>
      </div>
    </a>
  </div>

  <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
    <div class="navbar-vertical-content scrollbar">
      <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">

        <!-- Dashboard -->
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-home"></span></span>
              <span class="nav-link-text ps-1">Dashboard</span>
            </div>
          </a>
        </li>

        <!-- Logout -->
        <li class="nav-item mt-3">
          <a class="nav-link text-danger" href="logout.php">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sign-out-alt"></span></span>
              <span class="nav-link-text ps-1 fw-bold">Logout</span>
            </div>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>