<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">

  <!-- Sidebar Toggle -->
  <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarVerticalCollapse"
          aria-controls="navbarVerticalCollapse" 
          aria-expanded="false" 
          aria-label="Toggle Navigation">
      <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
  </button>

  <!-- Brand -->
  <a class="navbar-brand me-1 me-sm-3" href="index.php">
    <div class="d-flex align-items-center">
      <!-- <img class="me-2" src="assets/img/icons/clinic-logo.png" alt="" width="38" /> -->
      <span class="font-sans-serif fw-bold">Clinic PMS</span>
    </div>
  </a>

  <!-- Left Spacer -->
  <ul class="navbar-nav align-items-center d-none d-lg-block"></ul>

  <!-- Right Icons -->
  <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">

    <!-- DARK / LIGHT TOGGLE -->
    <li class="nav-item">
      <div class="theme-control-toggle fa-icon-wait px-2">
        <input class="form-check-input ms-0 theme-control-toggle-input" 
               id="themeControlToggle" 
               type="checkbox" 
               data-theme-control="theme" 
               value="dark" />
        <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" 
               for="themeControlToggle"
               data-bs-toggle="tooltip" 
               data-bs-placement="left"
               title="Switch to light theme">
          <span class="fas fa-sun fs-0"></span>
        </label>
        <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" 
               for="themeControlToggle"
               data-bs-toggle="tooltip" 
               data-bs-placement="left"
               title="Switch to dark theme">
          <span class="fas fa-moon fs-0"></span>
        </label>
      </div>
    </li>

    <!-- USER DROPDOWN -->
    <li class="nav-item dropdown">
      <a class="nav-link pe-0" id="navbarDropdownUser" href="#" 
         role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <div class="avatar avatar-xl">
          <img class="rounded-circle" src="assets/img/team/3-thumb.png" alt="User" />
        </div>
      </a>

      <div class="dropdown-menu dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
        <div class="bg-white dark__bg-1000 rounded-2 py-2">

          <!-- LOGOUT ONLY -->
          <a class="dropdown-item text-danger fw-bold" href="logout.php">
            <span class="fas fa-sign-out-alt me-2"></span>Logout
          </a>

        </div>
      </div>
    </li>

  </ul>
</nav>