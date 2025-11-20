<?php
include('includes/header.php');

if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    header("Location: logout.php");
    exit();
}
?>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
      <div class="container" data-layout="container">
        <script>
          var isFluid = JSON.parse(localStorage.getItem('isFluid'));
          if (isFluid) {
            var container = document.querySelector('[data-layout]');
            container.classList.remove('container');
            container.classList.add('container-fluid');
          }
        </script>

        <?php
        include('includes/sidebar.php');
        ?>

        <div class="content">

          <?php include('includes/navbar.php');?>

          <!-- ===============================================-->
<!--   Patient List Section -->
<!-- ===============================================-->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Patients</h5>
    <a href="create-patient.php" class="btn btn-sm btn-primary">
      <i class="fas fa-plus me-1"></i> Create Patient
    </a>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-bordered" id="patientsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Phone</th>
            <th>View Profile</th>
          </tr>
        </thead>
        <tbody>
          <?php
          include('connection.php');
          $sql = "SELECT patient_id, first_name, last_name, gender, phone_number FROM patients ORDER BY patient_id DESC";
          $result = $conn->query($sql);

          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
              echo '<tr>
                      <td>' . $row['patient_id'] . '</td>
                      <td>' . $full_name . '</td>
                      <td>' . $row['gender'] . '</td>
                      <td>' . $row['phone_number'] . '</td>
                      <td>
                        <a href="profile.php?id=' . $row['patient_id'] . '" class="btn btn-sm btn-info">
                          View
                        </a>
                      </td>
                    </tr>';
            }
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

          <?php include('includes/footer.php'); ?>
          
        </div>
      </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
          <!-- DataTables JS -->
<script>
  $(document).ready(function () {
      $('#patientsTable').DataTable();
  });
</script>
  </body>

</html>