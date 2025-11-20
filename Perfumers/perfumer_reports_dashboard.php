<?php
include '../db_connect.php'; 

function fetchAll($conn, $sql) {
    $res = $conn->query($sql);
    $rows = [];
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

// top selling perfumes
$top_perfumes = fetchAll($conn, "
    SELECT p.perfume_name, SUM(od.quantity) AS total_sold
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    GROUP BY p.perfume_ID
    ORDER BY total_sold DESC
    LIMIT 10;
");

// most popular accords
$popular_accords = fetchAll($conn, "
    SELECT a.accord_name, SUM(od.quantity) AS use_count
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    JOIN perfume_accords pa ON pa.perfume_ID = p.perfume_ID
    JOIN accords a ON a.accord_ID = pa.accord_ID
    GROUP BY pa.accord_ID
    ORDER BY use_count DESC
    LIMIT 10;
");

// most popular notes
$popular_notes = fetchAll($conn, "
    SELECT n.note_name, SUM(od.quantity) AS use_count
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    JOIN perfume_notes pn ON pn.perfume_ID = p.perfume_ID
    JOIN notes n ON n.note_ID = pn.note_ID
    GROUP BY pn.note_ID
    ORDER BY use_count DESC
    LIMIT 10;
");

// concerntration popularity
$concentration_count = fetchAll($conn, "
    SELECT p.concentration, SUM(od.quantity) AS total
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    GROUP BY p.concentration;
");

// popu;arity by the country of origin of perfumes
$popularity_origin = fetchAll($conn, "
    SELECT c.country_name, SUM(od.quantity) AS total_sold
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    JOIN countries c ON c.country_ID = p.country_ID
    WHERE p.country_ID IS NOT NULL
    GROUP BY c.country_ID
    ORDER BY total_sold DESC
    LIMIT 10;
");

// popularty of perfumes based on customer countries
$popularity_customer = fetchAll($conn, "
    SELECT c.country_name, SUM(od.quantity) AS total_sold
    FROM order_details od
    JOIN orders o ON o.order_ID = od.order_ID
    JOIN customers cu ON cu.customer_ID = o.customer_ID
    JOIN countries c ON c.country_ID = cu.country_ID
    WHERE cu.country_ID IS NOT NULL
    GROUP BY c.country_ID
    ORDER BY total_sold DESC
    LIMIT 10;
");


$data_top_perfumes = [
    "labels" => array_column($top_perfumes, 'perfume_name'),
    "data"   => array_map('intval', array_column($top_perfumes, 'total_sold'))
];

$data_accords = [
    "labels" => array_column($popular_accords, 'accord_name'),
    "data"   => array_map('intval', array_column($popular_accords, 'use_count'))
];

$data_notes = [
    "labels" => array_column($popular_notes, 'note_name'),
    "data"   => array_map('intval', array_column($popular_notes, 'use_count'))
];

$data_concentration = [
    "labels" => array_column($concentration_count, 'concentration'),
    "data"   => array_map('intval', array_column($concentration_count, 'total'))
];

$data_country_origin = [
    "labels" => array_column($popularity_origin, 'country_name'),
    "data"   => array_map('intval', array_column($popularity_origin, 'total_sold'))
];

$data_country_customer = [
    "labels" => array_column($popularity_customer, 'country_name'),
    "data"   => array_map('intval', array_column($popularity_customer, 'total_sold'))
];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Perfumer Reports Dashboard</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">

    <style>
        .card-header { background:#842A3B; color:white; }
        .chart-box { height:300px; }
        .chart-small { height:220px; }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>

<body>

<?php include 'admin_sidebar.php'; ?>

<div class="content-wrapper">

<h3 class="page-title mb-5 mt-3">Reports Dashboard</h3>

<div class="card mb-4">
    <div class="card-header"><b>Top-Selling Perfumes</b></div>
    <div class="card-body">
        <canvas id="chartTopPerfumes" class="chart-box"></canvas>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><b>Most Popular Accords</b></div>
            <div class="card-body">
                <canvas id="chartAccords" class="chart-box"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><b>Most Used Notes</b></div>
            <div class="card-body">
                <canvas id="chartNotes" class="chart-box"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><b>Popularity by Country of Origin</b></div>
            <div class="card-body">
                <canvas id="chartOriginCountry" class="chart-box"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><b>Popularity by Customer Country</b></div>
            <div class="card-body">
                <canvas id="chartCustomerCountry" class="chart-box"></canvas>
            </div>
        </div>
    </div>

</div>
<div class="row">
    <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><b>Perfume by Concentration</b></div>
                <div class="card-body">
                    <canvas id="chartConcentration" class="chart-small"></canvas>
                </div>
            </div>
        </div>
    </div>
</div> 

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const topPerfumes = <?= json_encode($data_top_perfumes) ?>;
const accords = <?= json_encode($data_accords) ?>;
const notes = <?= json_encode($data_notes) ?>;
const conc = <?= json_encode($data_concentration) ?>;
const countryOrigin = <?= json_encode($data_country_origin) ?>;
const countryCustomer = <?= json_encode($data_country_customer) ?>;


new Chart(document.getElementById("chartTopPerfumes"), {
    type: 'bar',
    data: {
        labels: topPerfumes.labels,
        datasets: [{
            label: "Units Sold",
            data: topPerfumes.data,
        }]
    }
});

new Chart(document.getElementById("chartAccords"), {
    type: 'bar',
    data: {
        labels: accords.labels,
        datasets: [{
            label: "Accord Usage Count",
            data: accords.data,
        }]
    }
});

new Chart(document.getElementById("chartNotes"), {
    type: 'bar',
    data: {
        labels: notes.labels,
        datasets: [{
            label: "Note Usage Count",
            data: notes.data,
        }]
    }
});

new Chart(document.getElementById("chartConcentration"), {
    type: 'doughnut',
    data: {
        labels: conc.labels,
        datasets: [{
            label: "Perfume Count",
            data: conc.data,
        }]
    }
});

new Chart(document.getElementById("chartOriginCountry"), {
    type: 'bar',
    data: {
        labels: countryOrigin.labels,
        datasets: [{
            label: "Units Sold",
            data: countryOrigin.data,
        }]
    }
});

new Chart(document.getElementById("chartCustomerCountry"), {
    type: 'bar',
    data: {
        labels: countryCustomer.labels,
        datasets: [{
            label: "Units Sold",
            data: countryCustomer.data,
        }]
    }
});

</script>

<?php $conn->close(); ?>

</body>
</html>

