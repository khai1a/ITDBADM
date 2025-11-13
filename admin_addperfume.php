<?php 
$dbpath = "db_connect.php";
$dbpath = realpath($dbpath);

include($dbpath);

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Admin Panel</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="css/admin_sidebar.css" rel="stylesheet">
    <link href="css/admin_general.css" rel="stylesheet">

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
      <div class="card shadow-sm">
        <div class="card-header text-white">
          <h4 class="mb-0">Add New Perfume</h4>
        </div>

        <div class="card-body">
          <form>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="perfumeName">Perfume Name</label>
                <input type="text" class="form-control" id="perfumeName" name="perfume_name" placeholder="Enter perfume name">
              </div>
              <div class="form-group col-md-6">
                <label for="brand">Brand</label>
                <select id="brand" name="brand" class="form-control">
                  <option selected disabled>Choose brand...</option>
                  <option value="">Dior</option>
                  <option value="">Chanel</option>
                  <option value="">Tom Ford</option>
                  <option value="">Xerjoff</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="concentration">Concentration</label>
                <select id="concentration" name="concentration" class="form-control">
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
                <select id="gender" name="Gender" class="form-control">
                  <option selected disabled>Select gender...</option>
                  <option value="For her">For her</option>
                  <option value="For him">For him</option>
                  <option value="Unisex">Unisex</option>
                </select>
              </div>
            </div>

            <div class="form-row">

              <div class="form-group col-md-6">
                <label for="isExclusive">Exclusive?</label>
                <select id="isExclusive" name="is_exclusive" class="form-control">
                  <option value="0" selected>No</option>
                  <option value="1">Yes</option>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="country">Country</label>
                <select id="country" name="country" class="form-control">
                  <option selected disabled>Select country...</option>
                  <option value="">Japan</option>
                  <option value="">France</option>
                  <option value="">United Arab Emirates</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="topNotes">Top Note</label>
                <select id="topNotes" name="topNote" class="form-control">
                  <option selected disabled>Select top note...</option>
                  <option value="">Sandalwood</option>
                  <option value="">Oud</option>
                  <option value="">Lilac</option>
                  <option value="">Musk</option>
                  <option value="">Tubereuse</option>
                  <option value="">Vanilla</option>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label for="middleNotes">Middle Note</label>
                <select id="middleNotes" name="middleNote" class="form-control">
                  <option selected disabled>Select middle note...</option>
                  <option value="">Sandalwood</option>
                  <option value="">Oud</option>
                  <option value="">Lilac</option>
                  <option value="">Musk</option>
                  <option value="">Tubereuse</option>
                  <option value="">Vanilla</option>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label for="baseNotes">Base Note</label>
                <select id="baseNotes" name="baseNote" class="form-control">
                  <option selected disabled>Select base note...</option>
                  <option value="">Sandalwood</option>
                  <option value="">Oud</option>
                  <option value="">Lilac</option>
                  <option value="">Musk</option>
                  <option value="">Tubereuse</option>
                  <option value="">Vanilla</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="mainAccord">Primary Accord</label>
                <select id="mainAccord" name="baseNote" class="form-control">
                  <option selected disabled>Select primary accord...</option>
                  <option value="">Fresh</option>
                  <option value="">Aquatic</option>
                  <option value="">Amber</option>
                  <option value="">Floral</option>
                  <option value="">Vanilla</option>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="secondaryAccord">Secondary Accord</label>
                <select id="secondaryAccord" name="secondaryAccord" class="form-control">
                  <option selected disabled>Select secondary accord...</option>
                  <option value="">Fresh</option>
                  <option value="">Aquatic</option>
                  <option value="">Amber</option>
                  <option value="">Floral</option>
                  <option value="">Vanilla</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="imageName">Image File</label>
              <input type="text" class="form-control" id="imageName" name="image_name" placeholder="e.g., gentle_fluidity_gold.jpg">
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
	</body>
</html>