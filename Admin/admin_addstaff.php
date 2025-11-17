<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$query = "SELECT * FROM branches";
$resultBranches = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $role = $_POST['role'];
  $branch_id = $_POST['branch'] ?? 'NULL';
  $username = $_POST['username'];
  $password = $_POST['password'];

  $conn->query("SET @staffID = ''");
  $conn->query("CALL getLastStaffID(@staffID)");
  $resultStaffID = $conn->query("SELECT @staffID");
  $staffID = $resultStaffID->fetch_assoc()['@staffID'];

  if ($branch_id != 'NULL') {
     $query = "INSERT INTO staff (staff_ID, branch_ID, `role`, `username`, `password`)
            VALUE ('$staffID', '$branch_id', '$role', '$username', SHA2('$password', 256))";
  } else {
    $query = "INSERT INTO staff (staff_ID, branch_ID, `role`, `username`, `password`)
            VALUE ('$staffID', $branch_id, '$role', '$username', SHA2('$password', 256))";
  }

  try {
    $conn->query($query);
    $message = "Staff account successfully created!";
    $status = "success";
  } catch (Exception $e) {
    $message = $e->getMessage();
    $status = "danger";
  }

}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Admin Panel - Add Staff</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">

    <style>
      .main {
        align-items: center;
      }

      .card-header {
        background-color: #842A3B;
      }

    </style>
	</head>
	<body>

		<?php require'admin_sidebar.php'; ?>

    <div class="container main">
      <div class="container mt-5 mb-5">

      <?php if (isset($message)) { ?>
      <div class="alert alert-<?= $status ?>">
        <?= $message ?>
      </div>
      <?php } ?>

        <div class="card shadow-sm">
          <div class="card-header text-white">
            <h4 class="mb-0">Create New Staff Member</h4>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="role">Role</label>
                  <select id="role" name="role" class="form-control" required>
                    <option value="" selected disabled>Choose role...</option>
                    <option value="Branch Manager">Branch Manager</option>
                    <option value="Branch Employee">Branch Employee</option>
                    <option value="Perfumer">Perfumer</option>
                    <option value="Inter-Branch Manager">Inter-Branch Manager</option>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label for="branch">Branch</label>
                  <select id="branch" name="branch" class="form-control">
                    <option value="" selected disabled>Choose branch...</option>
                    <?php while ($row = $resultBranches->fetch_assoc()) { ?>
                    <option value="<?= $row['branch_ID'] ?>"><?= $row['branch_ID'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                </div>
                <div class="form-group col-md-6">
                  <label for="password">Password</label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
              </div>
                
              <div class="form-row mb-3">
                <label for="confirmpassword">Confirm Password</label>
                <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" placeholder="Confirm password" required>
              </div>

              <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-secondary mr-2">Clear</button>
                <button type="submit" class="btn btn-primary">Create Staff Account</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
     
     
    </div>

    <div class="spacer">*</div>

		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <script src="../javascript/create_staff_validation.js"></script>

    <?php $conn->close(); ?>
	</body>
</html>