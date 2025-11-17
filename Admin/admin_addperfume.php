<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$query = "SELECT brand_ID, brand_name FROM brands";
$resultBrands = $conn->query($query);
$resultAccords = $conn->query("SELECT * FROM accords");
$resultNotes = $conn->query("SELECT * FROM notes");
$resultCountries = $conn->query("SELECT * FROM countries");
$resultCurrencies = $conn->query("SELECT * FROM currencies");


if ($_SERVER['REQUEST_METHOD'] == "POST") {
  $perfumeName = $_POST['perfume_name'];
  $brand_ID = $_POST['brand'];
  $concentration = $_POST['concentration'];
  $gender = $_POST['gender'];
  $is_exclusive = $_POST['isExclusive'];
  $country_ID = $is_exclusive == '1' ? $_POST['country'] : NULL;
  $topNote = $_POST['topNote'];
  $middleNote = $_POST['middleNote'];
  $baseNote = $_POST['baseNote'];
  $mainAccord = $_POST['mainAccord'];
  $secondaryAccord = $_POST['secondaryAccord'];
  $imageName = $_FILES['image']['name'];
  $volumesString = $_POST['volume'];
  $sellingPricesString = $_POST['sellingPrice'];

  $volumes = explode(',', $volumesString);
  $sellingPrices = explode(',', $sellingPricesString);

  $countVolumes = count($volumes);
  $countSellingPrices = count($sellingPrices);

  $flag = false;


  $conn->query("START TRANSACTION;");

  try {
    if ($countVolumes != $countSellingPrices) {
      throw new Exception("Volume and Price count must match.");
    }
     //insert perfume
    $conn->query("SET @perfID = ''");
    $conn->query("CALL getLastPerfumeID(@perfID)");
    $resultPerfumeID = $conn->query("SELECT @perfID");
    $perfumeID = $resultPerfumeID->fetch_assoc()['@perfID'];

    if ($is_exclusive) {
      $res = $conn->query("INSERT INTO perfumes (perfume_ID, brand_ID, perfume_name, concentration, is_exclusive, Gender, country_ID, image_name) 
      VALUE ('$perfumeID', '$brand_ID', '$perfumeName', '$concentration', $is_exclusive, '$gender', '$country_ID', '$imageName')");
      $message = "Successfully inserted perfume!";
    } else {
      $res = $conn->query("INSERT INTO perfumes (perfume_ID, brand_ID, perfume_name, concentration, is_exclusive, Gender, image_name) 
      VALUE ('$perfumeID', '$brand_ID', '$perfumeName', '$concentration', $is_exclusive, '$gender', '$imageName')");
      $message = "Successfully inserted perfume!";
    }

    //insert perfume_volume
    for ($i = 0; $i < $countVolumes; $i++) {
      $conn->query("SET @perfvol = ''");
      $conn->query("CALL getLastPerfumeVolumeID(@perfvol);");
      $result = $conn->query("SELECT @perfvol");
      $perfumeVolumeID = $result->fetch_assoc()['@perfvol'];

      $query = "INSERT INTO perfume_volume (perfume_volume_ID, perfume_ID, volume, selling_price) 
              VALUE ('$perfumeVolumeID', '$perfumeID', $volumes[$i], $sellingPrices[$i])";
      $conn->query($query);
    }
    
    //get last perfume note ID
    $conn->query("SET @perfnote=''");
    $conn->query("CALL getLastPerfumeNoteID(@perfnote);");
    $result = $conn->query("SELECT @perfnote");
    $perfumeTopNoteID = $result->fetch_assoc()['@perfnote'];

    $query = "INSERT INTO perfume_notes (perfume_note_id, perfume_ID, note_ID, note_level) VALUE
              ('$perfumeTopNoteID', '$perfumeID', '$topNote', 'top')";
    $conn->query($query);

    $conn->query("SET @perfnote=''");
    $conn->query("CALL getLastPerfumeNoteID(@perfnote);");
    $result = $conn->query("SELECT @perfnote");
    $perfumeMiddleNoteID = $result->fetch_assoc()['@perfnote'];

    $query = "INSERT INTO perfume_notes (perfume_note_id, perfume_ID, note_ID, note_level) VALUE
              ('$perfumeMiddleNoteID', '$perfumeID', '$middleNote', 'middle')";
    $conn->query($query);

    $conn->query("SET @perfnote=''");
    $conn->query("CALL getLastPerfumeNoteID(@perfnote);");
    $result = $conn->query("SELECT @perfnote");
    $perfumeBaseNoteID = $result->fetch_assoc()['@perfnote'];

    $query = "INSERT INTO perfume_notes (perfume_note_id, perfume_ID, note_ID, note_level) VALUE
              ('$perfumeBaseNoteID', '$perfumeID', '$baseNote', 'base')";
    $conn->query($query);

    $conn->query("SET @perfacc = ''");
    $conn->query("CALL getLastPerfumeAccordID(@perfacc)");
    $result = $conn->query("SELECT @perfacc");
    $perfumeAccordID = $result->fetch_assoc()['@perfacc'];

    //insert into perfume_accords
    $query = "INSERT INTO perfume_accords (perfume_accord_id, perfume_id, accord_id, is_primary) 
              VALUE ('$perfumeAccordID', '$perfumeID', '$mainAccord', true)";
    $conn->query($query);

    $conn->query("SET @perfacc = ''");
    $conn->query("CALL getLastPerfumeAccordID(@perfacc)");
    $result = $conn->query("SELECT @perfacc");
    $perfumeAccordID = $result->fetch_assoc()['@perfacc'];

    $query = "INSERT INTO perfume_accords (perfume_accord_id, perfume_id, accord_id, is_primary) 
              VALUE ('$perfumeAccordID', '$perfumeID', '$secondaryAccord', false)";
    $conn->query($query);

    $message = "Successfully added perfume!";

    $conn->query("COMMIT;");

  } catch (Exception $e) {

    $message = "Error occurred: " . $e->getMessage();
    $flag = true;
    $conn->query("ROLLBACK;");

  }
}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Admin Panel - Add Perfume</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">

    <style>
      .main {
        align-content: center;
      }

      .card-header {
        background-color: #842A3B;
      }

    </style>
	</head>
	<body>

		<?php require'admin_sidebar.php'; ?>

    <div class="container main mt-5 mb-5">

      <?php if (isset($message)) { ?>
      <div class="alert <?php if ($flag) { ?> alert-danger <?php } else { ?> alert-success <?php } ?>">
        <?= $message ?>
      </div>
      <?php } ?>

      <div class="card shadow-sm">
        <div class="card-header text-white">
          <h4 class="mb-0">Add New Perfume</h4>
        </div>

        <div class="card-body">
          <form method="POST" action="#" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="perfume_name">Perfume Name</label>
                <input type="text" class="form-control" id="perfume_name" name="perfume_name" placeholder="Enter perfume name" required>
              </div>
              <div class="form-group col-md-6">
                <label for="brand">Brand</label>
                <select id="brand" name="brand" class="form-control" required>
                  <option selected disabled>Choose brand...</option>
                  <?php while ($row = $resultBrands->fetch_assoc()) { ?>
                  <option value="<?= $row['brand_ID'] ?>"><?= $row['brand_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="concentration">Concentration</label>
                <select id="concentration" name="concentration" class="form-control" required>
                  <option selected disabled>Choose concentration...</option>
                  <option value="Parfum">Parfum</option>
                  <option value="Eau de Parfum">Eau de Parfum</option>
                  <option value="Eau de Toilette">Eau de Toilette</option>
                  <option value="Eau de Cologne">Eau de Cologne</option>
                  <option value="Eau Fraiche">Eau Fraiche</option>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control" required>
                  <option selected disabled>Select gender...</option>
                  <option value="For her">For her</option>
                  <option value="For him">For him</option>
                  <option value="Unisex">Unisex</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12">
                <label for="volume">Volume(s) in ML</label>
                <input type="text" class="form-control" id="volume" name="volume" placeholder="Separate multiple volumes with commas (i.e. 50,100,200)" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12">
                <label for="sellingPrice">Selling Prices in USD</label>
                <input type="text" class="form-control" id="sellingPrice" name="sellingPrice" placeholder="Separate prices with commas (i.e. 50.0,200.00,1000.0). Count of prices and volumes must be the same." required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="isExclusive">Exclusive?</label>
                <select id="isExclusive" name="isExclusive" class="form-control" required>
                  <option value="0" selected>No</option>
                  <option value="1">Yes</option>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="country">Country</label>
                <select id="country" name="country" class="form-control">
                  <option selected disabled>Select country...</option>
                  <?php while ($row = $resultCountries->fetch_assoc()) { ?>
                  <option value="<?= $row['country_ID'] ?>"><?= $row['country_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="topNote">Top Note</label>
                <select id="topNote" name="topNote" class="form-control" required>
                  <option selected disabled>Select top note...</option>
                  <?php while ($row = $resultNotes->fetch_assoc()) { ?>
                  <option value="<?= $row['note_ID'] ?>"><?= $row['note_name'] ?></option>
                  <?php }  ?>
                </select>
              </div>

              <?php 
              $resultNotes = $conn->query("SELECT * FROM notes");
              ?>

              <div class="form-group col-md-4">
                <label for="middleNote">Middle Note</label>
                <select id="middleNote" name="middleNote" class="form-control" required>
                  <option selected disabled>Select middle note...</option>
                  <?php while ($row = $resultNotes->fetch_assoc()) { ?>
                  <option value="<?= $row['note_ID'] ?>"><?= $row['note_name'] ?></option>
                  <?php }  ?>
                </select>
              </div>

              <?php 
              $resultNotes = $conn->query("SELECT * FROM notes");
              ?>

              <div class="form-group col-md-4">
                <label for="baseNote">Base Note</label>
                <select id="baseNote" name="baseNote" class="form-control" required>
                  <option selected disabled>Select base note...</option>
                  <?php while ($row = $resultNotes->fetch_assoc()) { ?>
                  <option value="<?= $row['note_ID'] ?>"><?= $row['note_name'] ?></option>
                  <?php }  ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="mainAccord">Primary Accord</label>
                <select id="mainAccord" name="mainAccord" class="form-control">
                  <option selected disabled>Select primary accord...</option>
                  <?php while ($row = $resultAccords->fetch_assoc()) { ?>
                  <option value="<?= $row['accord_ID'] ?>"><?= $row['accord_name'] ?></option>
                  <?php } ?>
                </select>
              </div>

              <?php 
              $resultAccords = $conn->query("SELECT * FROM accords");
              ?>

              <div class="form-group col-md-6">
                <label for="secondaryAccord">Secondary Accord</label>
                <select id="secondaryAccord" name="secondaryAccord" class="form-control">
                  <option selected disabled>Select secondary accord...</option>
                  <?php while ($row = $resultAccords->fetch_assoc()) { ?>
                  <option value="<?= $row['accord_ID'] ?>"><?= $row['accord_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="image">Image File</label>
              <input type="file" class="form-control" id="image" name="image" placeholder="e.g., gentle_fluidity_gold.jpg" accept="image/png, image/jpeg, image/webp" required>
            </div>

            <div class="d-flex justify-content-end">
              <button type="reset" class="btn btn-secondary mr-2">Clear</button>
              <button type="submit" class="btn btn-primary">Add Perfume</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="spacer">*</div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <script src="../javascript/addperfume.js"></script>
    <?php $conn->close(); ?>
	</body>
</html>