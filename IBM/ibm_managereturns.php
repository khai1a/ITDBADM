<?php
require 'check_session.php';

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
    SELECT 
        CONCAT(p.perfume_name, ' ', pv.volume, 'ml') AS order_item,
        r.return_ID,
        r.customer_ID,
        r.quantity,
        r.status,
        r.refund_amount,
        r.date_requested,
        r.last_update,
        c.currency_sign
    FROM returns r
        JOIN order_details od ON od.order_detail_ID = r.order_detail_ID
        JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
        JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
        JOIN orders o ON o.order_ID = od.order_ID
        JOIN currencies c ON c.currency = o.currency
    WHERE r.status = '$filterStatus'
      AND o.order_type = 'Online'
    ORDER BY r.date_requested DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>IBM Panel - Manage Returns</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .header .card-title {
            font-weight: bold;
            color: #A3485A;
            font-size: 13px;
        }

        .item {
            border: none;
            border-radius: 1em;
            background-color: rgba(231, 214, 213, 1);
            margin-bottom: 0.7rem;
            cursor: pointer;
        }

        .item .card-body {
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
        }

        .badge {
            font-size: 9px !important;
        }

        button {
            font-size: 12px !important;
        }

        .btn-success {
            background-color: rgba(53, 173, 87, 1);
            border-color: rgba(53, 173, 87, 1);
        }

        .btn-danger {
            background-color: rgba(181, 61, 61, 1);
            border-color: rgba(181, 61, 61, 1);
        }

        .scroll-box {
            max-height: 350px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .scroll-box::-webkit-scrollbar {
            width: 8px;
        }

        .scroll-box::-webkit-scrollbar-thumb {
            background: #c8a8a8;
            border-radius: 10px;
        }

        .scroll-box::-webkit-scrollbar-track {
            background: #f3e8e8;
        }

        .card-text {
            font-size: 12px;
            margin-bottom: 0.2rem;
        }

        .returns-header-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .page-title {
            color: #842A3B;
            font-weight: bold;
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
            <select name="status"
                    class="form-control form-control-sm mr-2"
                    onchange="this.form.submit()">
                <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $filterStatus ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript>
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            </noscript>
        </form>
    </div>

    <?php if ($message) { ?>
        <div class="alert alert-<?= htmlspecialchars($statusClass) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php } ?>

    <div class="card header">
        <div class="card-body">
            <div class="container text-left">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title returns-header-label">Order Item</h5>
                    </div>
                    <div class="col">
                        <h5 class="card-title returns-header-label">Return ID</h5>
                    </div>
                    <div class="col">
                        <h5 class="card-title returns-header-label">Quantity</h5>
                    </div>
                    <div class="col">
                        <h5 class="card-title returns-header-label">Status</h5>
                    </div>
                    <div class="col">
                        <h5 class="card-title returns-header-label">Refund Amount</h5>
                    </div>
                    <div class="col">
                        <h5 class="card-title returns-header-label">Date Requested</h5>
                    </div>
                    <div class="col-2">
                        <h5 class="card-title returns-header-label">Actions</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="scroll-box mt-2">
        <?php if ($returnsResult->num_rows > 0) { ?>
            <?php while ($row = $returnsResult->fetch_assoc()) { ?>
                <?php
                $badgeClass = 'secondary';
                switch ($row['status']) {
                    case 'Requested': $badgeClass = 'warning'; break;
                    case 'Approved':  $badgeClass = 'info';    break;
                    case 'Rejected':  $badgeClass = 'danger';  break;
                    case 'Refunded':  $badgeClass = 'success'; break;
                }
                ?>
                <div class="card item"
                     onclick="window.location='ibm_returndetails.php?id=<?= $row['return_ID'] ?>'">
                    <div class="card-body">
                        <div class="container text-left">
                            <div class="row align-items-center">
                                <div class="col">
                                    <p class="card-text">
                                        <?= htmlspecialchars($row['order_item']) ?>
                                    </p>
                                </div>
                                <div class="col">
                                    <p class="card-text">
                                        <?= htmlspecialchars($row['return_ID']) ?>
                                    </p>
                                </div>
                                <div class="col">
                                    <p class="card-text">
                                        <?= htmlspecialchars($row['quantity']) ?>
                                    </p>
                                </div>
                                <div class="col">
                                    <span class="badge badge-<?= $badgeClass ?> status-badge">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </div>
                                <div class="col">
                                    <p class="card-text">
                                        <?= htmlspecialchars($row['currency_sign']) ?>
                                        <?= htmlspecialchars($row['refund_amount']) ?>
                                    </p>
                                </div>
                                <div class="col">
                                    <p class="card-text">
                                        <?= htmlspecialchars($row['date_requested']) ?>
                                    </p>
                                </div>
                                <div class="col-2" onclick="event.stopPropagation();">
                                    <?php if ($row['status'] === 'Requested'): ?>
                                        <form method="post"
                                              style="display:inline-block;"
                                              action="process_returns.php">
                                            <input type="hidden" name="return_ID"
                                                   value="<?= $row['return_ID'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit"
                                                    class="btn btn-success btn-sm mb-1"
                                                    onclick="return confirm('Approve return <?= $row['return_ID'] ?>?');">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="post"
                                              style="display:inline-block;"
                                              action="process_returns.php">
                                            <input type="hidden" name="return_ID"
                                                   value="<?= $row['return_ID'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Reject return <?= $row['return_ID'] ?>?');">
                                                Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">
                                            No actions
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card item">
                <div class="card-body">
                    <div class="container text-left">
                        <div class="row">
                            <div class="col">
                                <p class="card-text text-center mb-0">
                                    No returns found for status
                                    "<strong><?= htmlspecialchars($filterStatus) ?></strong>".
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

</div>

</body>
</html>
