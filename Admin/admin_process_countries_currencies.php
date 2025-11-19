<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? '';
    $action = $_POST['action'] ?? '';

    
    if ($entity === 'currency') {
        $currency = strtoupper(trim($_POST['currency']));
        $fromUSD = $_POST['fromUSD'];
        $currency_sign = trim($_POST['currency_sign']);

        if ($action === 'create') {
            $sql = "INSERT INTO currencies (currency, fromUSD, currency_sign)
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sds", $currency, $fromUSD, $currency_sign);
        } elseif ($action === 'update') {
            $sql = "UPDATE currencies
                    SET fromUSD = ?, currency_sign = ?
                    WHERE currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dss", $fromUSD, $currency_sign, $currency);
        }

        if (isset($stmt)) {
            try {
                $stmt->execute();
                $message = 'Currency saved successfully.';
                $status = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $status = 'danger';
            }
            $stmt->close();
        }
    }


    if ($entity === 'country') {
        $country_ID = strtoupper(trim($_POST['country_ID']));
        $country_name = trim($_POST['country_name']);
        $country_currency = $_POST['currency'];
        $vat_percent = $_POST['vat_percent'];

        if ($action === 'create') {
            $sql = "INSERT INTO countries (country_ID, country_name, currency, vat_percent)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssd", $country_ID, $country_name, $country_currency, $vat_percent);
        } elseif ($action === 'update') {
            $sql = "UPDATE countries
                    SET country_name = ?, currency = ?, vat_percent = ?
                    WHERE country_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssds", $country_name, $country_currency, $vat_percent, $country_ID);
        }

        if (isset($stmt)) {
            try {
                $stmt->execute();
                $message = 'Country saved successfully.';
                $status = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $status = 'danger';
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['delete_currency'])) {
    $code = $_GET['delete_currency'];
    $stmt = $conn->prepare("DELETE FROM currencies WHERE currency = ?");
    $stmt->bind_param("s", $code);
    try {
        $stmt->execute();
        $message = "Currency $code deleted.";
        $status = 'success';
    } catch (Exception $e) {
        $message = 'Error deleting currency: ' . $e->getMessage();
        $status = 'danger';
    }
    $stmt->close();
}

if (isset($_GET['delete_country'])) {
    $id = $_GET['delete_country'];
    $stmt = $conn->prepare("DELETE FROM countries WHERE country_ID = ?");
    $stmt->bind_param("s", $id);
    try {
        $stmt->execute();
        $message = "Country $id deleted.";
        $status = 'success';
    } catch (Exception $e) {
        $message = 'Error deleting country: ' . $e->getMessage();
        $status = 'danger';
    }
    $stmt->close();
}

header('Location: admin_countries_currencies.php?message=' . $message . "&status=" . $status);
exit();