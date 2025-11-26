<?php
include('includes/header.php');
include('connection.php');

// If cookie already exists → redirect to dashboard
if (isset($_COOKIE['user_id']) && isset($_COOKIE['username'])) {
    header("Location: dashboard.php");
    exit;
}

$login_error = "";

// Handle login submit
if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === "" || $password === "") {
        $login_error = "Please enter both username and password.";
    } else {
        // Prepare statement
        $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $db_username, $db_password_hash);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $db_password_hash)) {

                // Set cookies for 7 days
                $expiry = time() + (86400 * 7); // 7 days
                
                setcookie("user_id", $user_id, $expiry, "/", "", false, true);
                setcookie("username", $db_username, $expiry, "/", "", false, true);

                $stmt->close();
                $conn->close();

                header("Location: dashboard.php");
                exit;
            } else {
                $login_error = "Invalid username or password.";
            }
        } else {
            $login_error = "Invalid username or password.";
        }

        $stmt->close();
    }
}
?>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<main class="main" id="top">
  <div class="container-fluid">
    <div class="row min-vh-100 flex-center g-0">
      <div class="col-lg-8 col-xxl-5 py-3 position-relative"><img class="bg-auth-circle-shape" src="assets/img/icons/spot-illustrations/bg-shape.png" alt="" width="250"><img class="bg-auth-circle-shape-2" src="../../../assets/img/icons/spot-illustrations/shape-1.png" alt="" width="150">
        <div class="card overflow-hidden z-index-1">
          <div class="card-body p-0">
            <div class="row g-0 h-100">
              <div class="col-md-5 text-center bg-card-gradient">
                <div class="position-relative p-4 pt-md-5 pb-md-7 light">
                  <div class="bg-holder bg-auth-card-shape" style="background-image:url(assets/img/icons/spot-illustrations/half-circle.png);"></div>

                  <div class="z-index-1 position-relative">
                    <a class="link-light mb-4 font-sans-serif fs-4 d-inline-block fw-bolder" href="javascript:void()">Rec Vault</a>
                    <p class="opacity-75 text-white">Rec Vault — where patient care stays first.</p>
                  </div>
                </div>
              </div>

              <div class="col-md-7 d-flex flex-center">
                <div class="p-4 p-md-5 flex-grow-1">
                  <div class="row flex-between-center">
                    <div class="col-auto">
                      <h3>Admin Login</h3>
                    </div>
                  </div>

                  <!-- Error Message -->
                  <?php if ($login_error != ""): ?>
                    <div class="alert alert-danger py-2"><?php echo $login_error; ?></div>
                  <?php endif; ?>

                  <form method="POST">
                    <div class="mb-3">
                      <label class="form-label" for="card-email">Username</label>
                      <input class="form-control" id="card-email" type="text" name="username" required />
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="card-password">Password</label>
                      <input class="form-control" id="card-password" type="password" name="password" required />
                    </div>

                    <div class="mb-3">
                      <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Log in</button>
                    </div>
                  </form>

                </div>
              </div>
            </div> <!-- row end -->
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->

<?php include('includes/footer.php'); ?>