<?php

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

isset($_GET['message']) ? $message = $_GET['message'] : $message = '';
isset($_GET['statusClass']) ? $statusClass = $_GET['statusClass'] : $statusClass = '';

$filterStatus = $_GET['status'] ?? 'Requested';
$validStatuses = ['Requested','Approved','Rejected','Refunded'];

if (!in_array($filterStatus, $validStatuses)) {
    $filterStatus = 'Requested';
}

$returnsResult = $conn->query("
 SELECT CONCAT(p.perfume_name, ' ', pv.volume, 'ml') AS order_item, r.return_ID, r.customer_ID, r.quantity, r.status, r.refund_amount, r.date_requested, r.last_update, c.currency_sign
    FROM returns r
		JOIN order_details od ON od.order_detail_ID = r.order_detail_ID
		JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
		JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
		JOIN orders o ON o.order_ID = od.order_ID
        JOIN currencies c ON c.currency = o.currency
    WHERE r.status = '$filterStatus' AND o.order_type = 'Online'
    ORDER BY r.date_requested DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>IBM Panel - Manage Returns</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
		
    <style>
        body {
					font-family: 'Poppins', sans-serif; 
				}

        .table td, .table th {
					vertical-align: middle; 
				}

				tr {
					cursor: pointer;
					border-bottom: solid rgba(203, 203, 203, 1) 1px;
				}

				th {
					font-size: 11px;
				}

				td {
					font-size: 12px;
				}

        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
        }

				button {
					font-size: 12px !important;
				}

				.badge{
					font-size: 9px !important;
				}

				.btn-success {
					background-color: rgba(53, 173, 87, 1);
				}

				.btn-danger {
					background-color: rgba(181, 61, 61, 1);
				}

    </style>
</head>
<body>

<?php require 'ibm_sidebar.php'; ?>

<div class="container main mb-5 p-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="page-title">Manage Returns</h3>

        <form method="get" class="form-inline">
            <label class="mr-2">Status:</label>
            <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $filterStatus ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-primary">Filter</button></noscript>
        </form>
    </div>

    <?php if ($message) { ?>
        <div class="alert alert-<?= $statusClass ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php } ?>

    <div class="table-responsive">
        <table class="table table-sm">
            <thead class="thead-light">
                <tr>
										<th>Order Item</th>
                    <th>Return ID</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Refund Amount</th>
                    <th>Date Requested</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($returnsResult->num_rows > 0) { ?>
                <?php while ($row = $returnsResult->fetch_assoc()) { ?>
                    <tr onclick="window.location='ibm_returndetails.php?id=<?= $row['return_ID'] ?>'">
											
												<td><?= htmlspecialchars($row['order_item']) ?></td>
                        <td><?= htmlspecialchars($row['return_ID']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td>
                            <?php
                            $badgeClass = 'secondary';
                            switch ($row['status']) {
                                case 'Requested': $badgeClass = 'warning'; break;
                                case 'Approved':  $badgeClass = 'info';    break;
                                case 'Rejected':  $badgeClass = 'danger';  break;
                                case 'Refunded':  $badgeClass = 'success'; break;
                            }
                            ?>
                            <span class="badge badge-<?= $badgeClass ?> status-badge">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['currency_sign']) . htmlspecialchars($row['refund_amount']) ?></td>
                        <td><?= htmlspecialchars($row['date_requested']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'Requested'): ?>
                                <form method="post" style="display:inline-block;" action="process_returns.php">
                                    <input type="hidden" name="return_ID" value="<?= $row['return_ID'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm"
                                            onclick="return confirm('Approve return <?= $row['return_ID'] ?>?');">
                                        Approve
                                    </button>
                                </form>
                                <form method="post" style="display:inline-block;" action="process_returns.php">
                                    <input type="hidden" name="return_ID" value="<?= $row['return_ID'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Reject return <?= $row['return_ID'] ?>?');">
                                        Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.85rem;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="12" class="text-center">No returns found for status "<?= htmlspecialchars($filterStatus) ?>".</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>