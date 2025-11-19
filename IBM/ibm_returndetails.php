<?php

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$return_ID = $_GET['id'] ?? null;
if (!$return_ID) {
    die("No return ID specified.");
}

$result = $conn->query("
	SELECT 
        r.return_ID,
        r.order_detail_ID,
        r.customer_ID,
        r.staff_ID,
        r.quantity AS return_quantity,
        r.reason,
        r.status,
        r.refund_amount,
        r.refund_method,
        r.date_requested,
        r.last_update,
        od.order_ID,
        od.perfume_volume_ID,
        od.quantity AS ordered_quantity,
        od.unit_price,
        od.currency,
				CONCAT(p.perfume_name, ' ', pv.volume, 'ml') AS order_item,
				p.image_name,
				c.currency_sign
    FROM returns r
    JOIN order_details od ON od.order_detail_ID = r.order_detail_ID
		JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
		JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
		JOIN currencies c ON c.currency = od.currency
    WHERE r.return_ID = '$return_ID'");

$return = $result->fetch_assoc();

if (!$return) {
    die("Return record not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Return Details - <?= htmlspecialchars($return['return_ID']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
    <style>

				.perfume-card img {
        	height: 14rem;
        	object-fit: contain;
      	}

        body {
					font-family: 'Poppins', sans-serif; 
				}

        .main {
					margin-top: 20px; 
				}

        .label-col {
					width: 200px; font-weight: 600; 
				}

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: .75rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: .25rem;
        }
    </style>
</head>
<body>
<?php

if (file_exists('ibm_sidebar.php')) require 'ibm_sidebar.php';
?>

<div class="container main mb-5 p-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="page-title">Return Details</h3>
        <a href="ibm_managereturns.php" class="btn btn-secondary btn-sm">Back to Returns</a>
    </div>

		<div class="d-flex flex-row">
			<div class="card perfume-card shadow-sm">
            <?php 
              $image_file = $return['image_name'];
              if (!empty($image_file)) { ?>
                <img class="card-img-top" src="../images/<?= $return['image_name'] ?>" alt="Image of <?= htmlspecialchars($row['perfume_name']) ?>">
              <?php } else { ?>
                <img class="card-img-top" src="https://png.pngtree.com/png-vector/20250319/ourmid/pngtree-elegant-pink-perfume-bottle-for-women-clipart-illustration-png-image_15771804.png" alt="Image of <?= htmlspecialchars($row['perfume_name']) ?>">
              <?php } ?>

            <div class="card-body">
              <p class="card-text">Order Item: <?= htmlspecialchars($return['order_item']) ?></p>
              <p class="card-text">Quantity: <?= htmlspecialchars($return['return_quantity']) ?></p>
              <p class="card-text">Unit Price: <?php echo $return['currency_sign'] . number_format($return['unit_price']); ?> </p>
            </div>
          </div>

			<div class="card mb-4 ml-4">
        <div class="card-body">
            <div class="section-title">Return Information</div>
            <div class="row return mb-2">
                <div class="col label-col">Return ID</div>
                <div class="col"><?= htmlspecialchars($return['return_ID']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">Customer ID</div>
                <div class="col">
                    <?= htmlspecialchars($return['customer_ID'] ?? '—') ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">
									<?php if ($return['status'] === 'Refunded') { ?>
										Quantity Returned
									<?php } else { ?>
										Quantity to Return
									<?php } ?>
								</div>
                <div class="col"><?= htmlspecialchars($return['return_quantity']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">Reason</div>
                <div class="col">
                    <?= nl2br(htmlspecialchars($return['reason'] ?? 'No reason provided')) ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">Status</div>
                <div class="col">
                    <?php
                    $badgeClass = 'secondary';
                    switch ($return['status']) {
                        case 'Requested': $badgeClass = 'warning'; break;
                        case 'Approved':  $badgeClass = 'info';    break;
                        case 'Rejected':  $badgeClass = 'danger';  break;
                        case 'Refunded':  $badgeClass = 'success'; break;
                    }
                    ?>
                    <span class="badge badge-<?= $badgeClass ?>">
                        <?= htmlspecialchars($return['status']) ?>
                    </span>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">Refund Amount</div>
                <div class="col">
                    <?= $return['refund_amount'] !== null 
                        ? htmlspecialchars(htmlspecialchars($return['currency_sign'] . number_format($return['refund_amount'], 2)))
                        : '—' ?>
                </div>
            </div> 
            <div class="row mb-2">
                <div class="col label-col">Date Requested</div>
                <div class="col"><?= htmlspecialchars($return['date_requested']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col label-col">Last Update</div>
                <div class="col"><?= htmlspecialchars($return['last_update']) ?></div>
            </div>
						<div class="row mb-0">
							<div class="col">
								<?php if ($return['status'] === 'Requested') { ?>
									<div class="container mt-3 space-between">
										<form method="post" action="process_returns.php" style="display:inline-block;">
												<input type="hidden" name="return_ID" value="<?= $return['return_ID'] ?>">
												<input type="hidden" name="action" value="approve">
												<input type="hidden" name="page" value="details">
												<button type="submit" class="btn btn-success"
																onclick="return confirm('Approve return <?= $return['return_ID'] ?>?');">
														Approve
												</button>
										</form>
										<form method="post" action="process_returns.php" style="display:inline-block;">
												<input type="hidden" name="return_ID" value="<?= $return['return_ID'] ?>">
												<input type="hidden" name="action" value="reject">
												<input type="hidden" name="page" value="details">
												<button type="submit" class="btn btn-danger"
																onclick="return confirm('Reject return <?= $return['return_ID'] ?>?');">
														Reject
												</button>
										</form>
									</div>
								<?php } ?>
							</div>
						</div>
        </div>
    	</div>
		</div>
</div>
</body>
</html>