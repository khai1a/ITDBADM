<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$perfumeID = $_GET['id'];

$resultPerfume = $conn->query("SELECT p.perfume_ID, p.perfume_name, p.concentration, p.Gender, b.brand_name, p.image_name, country_ID
                                      FROM perfumes p
                                      JOIN brands b ON b.brand_ID = p.brand_ID
                                      WHERE p.perfume_ID = '$perfumeID'");
$row = $resultPerfume->fetch_assoc();

if ($row['country_ID'] != NULL) {
  $resultCountry = $conn->query("SELECT country_name FROM perfumes p
                                 JOIN countries c ON c.country_ID = p.country_ID
                                 WHERE p.perfume_ID = '$perfumeID'");
  $countryName = $resultCountry->fetch_assoc()['country_name'];
}

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getAllSecondaryAccords(@perf)");
$secondaryAccords = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getPrimaryAccord(@perf)");
$primaryAccord = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getAllSecondaryAccords(@perf)");
$secondaryAccords = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getNotes(@perf, 'top')");
$topNotes = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getNotes(@perf, 'middle')");
$middleNotes = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$conn->query("SET @perf = '$perfumeID'");
$conn->query("CALL getNotes(@perf, 'base')");
$baseNotes = $conn->query("SELECT @perf")->fetch_assoc()['@perf'];

$resultVolumes = $conn->query("SELECT * FROM perfume_volume
                    WHERE perfume_ID = '$perfumeID'
                    ORDER BY volume ASC");
                    
$resultNotes = $conn->query("SELECT * FROM notes");

$resultAccords = $conn->query("SELECT * FROM accords");

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Admin Panel - View Perfumes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
       body {
        font-family: 'Poppins', sans-serif;
      }

      .perfume-card img {
        height: 14rem;
        object-fit: contain;
      }

      .card-icons {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 10px;
      }

      .icon-btn {
        width: 34px;
        height: 34px;
        background: white;
        color: #333;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        transition: 0.2s;
      }

      .icon-btn:hover {
        background: #F5DAA7;
        color: black;
        transform: scale(1.05);
      }

      .icon-btn i {
        font-size: 16px;
        border-style: none;
      }

      .badge-pill-custom {
        border-radius: 999px;
        padding: 0.25rem 0.8rem;
        margin: 0.15rem;
        font-size: 0.8rem;
        background-color: #f5f5f5;
      }

      .badge-primary-accord {
        background-color: #F5DAA7;
        color: #000;
        font-weight: 600;
      }

      .section-title {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.5rem;
      }

      .notes-column h6 {
        font-weight: 600;
      }

      .notes-column {
        border-right: 1px solid #eee;
      }
      .notes-column:last-child {
        border-right: none;
      }

      .volumes-table td,
      .volumes-table th {
        font-size: 0.85rem;
        vertical-align: middle;
      }

      .country-pill {
        display: inline-block;
        padding: 0.2rem 0.7rem;
        border-radius: 999px;
        background-color: #f0f0f0;
        font-size: 0.75rem;
      }

      .gender-pill {
        display: inline-block;
        padding: 0.2rem 0.7rem;
        border-radius: 999px;
        background-color: #e8f5ff;
        font-size: 0.75rem;
      }

      .input-field {
        width: 5rem;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
        padding-left: 0.7rem;
        border-radius: 1rem;
        border: solid 1px rgba(0,0,0,0.15);
      }

      form {
        display: inline;
      }

    </style>
  </head>
  <body>
    <?php require 'admin_sidebar.php'; ?>

    <div class="container main mt-5 mb-5">
      <div class="row">
        <!-- LEFT: MAIN PERFUME CARD -->
        <div class="col-md-4 mb-4">
          <div class="card perfume-card shadow-sm position-relative">
            <div class="card-icons">
              <!-- Edit icon -->
              <a class="icon-btn" title="Edit" href="admin_editperfume.php?id=<?= $row['perfume_ID'] ?>">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <!-- Info icon (current page, could be disabled or link elsewhere) -->
              <a class="icon-btn" title="View Info" href="#">
                <i class="fa-solid fa-circle-info"></i>
              </a>
            </div>

            <?php 
              $image_file = $row['image_name'];
              if (!empty($image_file)) { ?>
                <img class="card-img-top" src="../images/<?= $row['image_name'] ?>" alt="Image of <?= htmlspecialchars($row['perfume_name']) ?>">
              <?php } else { ?>
                <img class="card-img-top" src="https://png.pngtree.com/png-vector/20250319/ourmid/pngtree-elegant-pink-perfume-bottle-for-women-clipart-illustration-png-image_15771804.png" alt="Image of <?= htmlspecialchars($row['perfume_name']) ?>">
              <?php } ?>

            <div class="card-body">
              <h5 class="card-title mb-1"><?= htmlspecialchars($row['perfume_name']) ?></h5>
              <p class="mb-1"><strong><?= htmlspecialchars($row['brand_name']) ?></strong></p>
              <p class="mb-1 text-muted"><?= htmlspecialchars($row['concentration']) ?></p>

              <div class="mt-2">
                <span class="gender-pill"><?= htmlspecialchars($row['Gender']) ?></span>
                <?php if (isset($countryName)): ?>
                  <span class="country-pill ml-1">Available only in: <?= htmlspecialchars($countryName) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: DETAILS (ACCORDS, NOTES, VOLUMES) -->
        <div class="col-md-8">

          <!-- ACCORDS + VOLUMES -->
          <div class="row">
            <!-- ACCORDS -->
            <div class="col-md-6 mb-4">
              <div class="card shadow-sm h-100">
                <div class="card-header">Accords</div>
                <div class="card-body">

                  <!-- MAIN ACCORD -->
                  <form method="POST" action="update_primary_accord.php">
                    <small class="text-muted d-block mb-1">Main accord</small>
                    <div class="input-group input-group-sm mb-3">
                     <select class="form-control">
                        <?php while ($rowAccord = $resultAccords->fetch_assoc()) { ?>
                          <option value="<?= $rowAccord['accord_ID'] ?>"
                            <?php if ($rowAccord['accord_name'] == $primaryAccord) { ?>
                                selected <?php } ?>>
                            <?= $rowAccord['accord_name']?>
                          </option>
                        <?php } ?>
                     </select>
                      <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary">Update</button>
                      </div>
                    </div>
                  </form>

                  <!-- OTHER ACCORDS LIST -->
                  <small class="text-muted d-block mb-1">Secondary accords</small>
                  <?php 
                    $secArr = !empty($secondaryAccords)
                              ? array_filter(array_map('trim', explode(',', $secondaryAccords)))
                              : [];
                  ?>

                  <?php foreach ($secArr as $acc): ?>
                    <form method="POST" action="delete_secondary_accord.php" class="d-inline-block mb-1">
                      <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                      <input type="hidden" name="accord_name" value="<?= htmlspecialchars($acc) ?>">
                      <span class="badge-pill-custom">
                        <?= htmlspecialchars($acc) ?>
                        <button class="btn btn-sm btn-link text-danger p-0" title="Delete">
                          &times;
                        </button>
                      </span>
                    </form>
                  <?php endforeach; ?>

                  <!-- ADD NEW ACCORD -->
                  <form method="POST" action="add_secondary_accord.php" class="mt-3">
                    <div class="input-group input-group-sm">
                     <select class="form-control">
                      <option disabled selected>Choose an accord...</option>
                      <?php 
                        $resultAccords = $conn->query("SELECT * FROM accords");
                        while ($rowAccord = $resultAccords->fetch_assoc()) { ?>
                          <option value="<?= $rowAccord['accord_ID'] ?>"><?= $rowAccord['accord_name'] ?></option>
                        <?php } ?>
                     </select>
                      <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary">Add</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- VOLUMES -->
            <div class="col-md-6 mb-4">
              <div class="card shadow-sm h-100">
                <div class="card-header">Available Volumes</div>
                <div class="card-body">

                  <?php if ($resultVolumes->num_rows > 0): ?>
                    <form method="POST" action="update_volumes.php">
                      <table class="table table-sm mb-0">
                        <thead>
                          <tr>
                            <th>Volume</th>
                            <th>Price ($) </th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($vol = $resultVolumes->fetch_assoc()): ?>
                            <tr>
                              <td>
                                <input type="number" name="volume_ml[]" class="form-control form-control-sm"
                                      value="<?= $vol['volume'] ?>" min="1">
                              </td>
                              <td>
                                <input type="text" name="price[]" class="form-control form-control-sm"
                                      value="<?= $vol['selling_price'] ?>">
                              </td>
                              <td>
                                <form method="POST" action="delete_volume.php">
                                  <input type="hidden" name="volume" value="<?= $vol['volume'] ?>">
                                  <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                                  <button class="btn btn-sm btn-link text-danger">
                                    <i class="fa-solid fa-trash"></i>
                                  </button>
                                </form>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>

                      <button class="btn btn-sm btn-success mt-2" title="Save changes">
                        <i class="fa-solid fa-save"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <hr>

                  <!-- ADD VOLUME -->
                  <form method="POST" action="add_volume.php">
                    <div class="section-title mb-2">Add volume</div>
                    <div class="form-row">
                      <div class="col-4">
                        <input type="number" min="1" class="form-control form-control-sm" name="new_volume" placeholder="ml">
                      </div>
                      <div class="col-4">
                        <input type="text" class="form-control form-control-sm" name="new_price" placeholder="Price">
                      </div>
                      <div class="col-4 align-self-center">
                        USD
                      </div>
                    </div>
                    <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                    <button class="btn btn-outline-secondary btn-sm mt-2">Add Volume</button>
                  </form>

                </div>
              </div>
            </div>
          </div>

         <div class="card shadow-sm">
  <div class="card-header">Notes</div>
  <div class="card-body">
    <div class="row">

                <!-- Top notes -->
                  <div class="col-md-4 notes-column mb-3">
                    <h6>Top Notes</h6>

                    <?php $topArr = array_filter(array_map('trim', explode(',', $topNotes ?? ''))); ?>
                    <?php foreach ($topArr as $note): ?>
                      <form method="POST" action="delete_note.php" class="d-inline-block mb-1">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <input type="hidden" name="note_name" value="<?= htmlspecialchars($note) ?>">
                        <input type="hidden" name="note_type" value="top">
                        <span class="badge-pill-custom">
                          <?= htmlspecialchars($note) ?>
                          <button class="btn btn-sm btn-link text-danger p-0">&times;</button>
                        </span>
                      </form>
                    <?php endforeach; ?>

                    <!-- Add note -->
                    <form method="POST" action="add_note.php">
                      <div class="input-group input-group-sm mt-2">
                        <select name="topNote" id="topNote" class="form-control">
                          <option selected disabled>Select...</option>
                          <?php while ($rowNotes = $resultNotes->fetch_assoc()) { ?>
                            <option value="<?= $rowNotes['note_ID'] ?>"><?= $rowNotes['note_name'] ?></option>
                          <?php } ?>
                        </select>
                        <input type="hidden" name="note_type" value="top">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary">Add</button>
                        </div>
                      </div>
                    </form>
                  </div>

                  <!-- Middle Notes -->
                  <div class="col-md-4 notes-column mb-3">
                    <h6>Middle Notes</h6>

                    <?php $midArr = array_filter(array_map('trim', explode(',', $middleNotes ?? ''))); ?>
                    <?php foreach ($midArr as $note): ?>
                      <form method="POST" action="delete_note.php" class="d-inline-block mb-1">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <input type="hidden" name="note_name" value="<?= htmlspecialchars($note) ?>">
                        <input type="hidden" name="note_type" value="middle">
                        <span class="badge-pill-custom">
                          <?= htmlspecialchars($note) ?>
                          <button class="btn btn-sm btn-link text-danger p-0">&times;</button>
                        </span>
                      </form>
                    <?php endforeach; ?>

                    <form method="POST" action="add_note.php">
                      <div class="input-group input-group-sm mt-2">
                        <select name="middleNote" id="middleNote" class="form-control">
                          <option selected disabled>Select...</option>
                          <?php $resultNotes = $conn->query("SELECT * FROM notes");
                            while ($rowNotes = $resultNotes->fetch_assoc()) { ?>
                            <option value="<?= $rowNotes['note_ID'] ?>"><?= $rowNotes['note_name'] ?></option>
                          <?php } ?>
                        </select>
                        <input type="hidden" name="note_type" value="middle">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary">Add</button>
                        </div>
                      </div>
                    </form>
                  </div>

                  <!-- Base notes -->
                  <div class="col-md-4 mb-3 notes-column">
                    <h6>Base Notes</h6>

                    <?php $baseArr = array_filter(array_map('trim', explode(',', $baseNotes ?? ''))); ?>
                    <?php foreach ($baseArr as $note): ?>
                      <form method="POST" action="delete_note.php" class="d-inline-block mb-1">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <input type="hidden" name="note_name" value="<?= htmlspecialchars($note) ?>">
                        <input type="hidden" name="note_type" value="base">
                        <span class="badge-pill-custom">
                          <?= htmlspecialchars($note) ?>
                          <button class="btn btn-sm btn-link text-danger p-0">&times;</button>
                        </span>
                      </form>
                    <?php endforeach; ?>

                    <form method="POST" action="add_note.php">
                      <div class="input-group input-group-sm mt-2">
                        <select name="baseNote" id="baseNote" class="form-control">
                          <option selected disabled>Select...</option>
                          <?php $resultNotes = $conn->query("SELECT * FROM notes");
                            while ($rowNotes = $resultNotes->fetch_assoc()) { ?>
                            <option value="<?= $rowNotes['note_ID'] ?>"><?= $rowNotes['note_name'] ?></option>
                          <?php } ?>
                        </select>
                        <input type="hidden" name="note_type" value="base">
                        <input type="hidden" name="perfume_ID" value="<?= $perfumeID ?>">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary">Add</button>
                        </div>
                      </div>
                    </form>
                  </div>

                </div>
              </div>
            </div>

        </div><!-- /col-md-8 -->
      </div><!-- /row -->
    </div><!-- /container -->

    <?php $conn->close(); ?>
  </body>
</html>