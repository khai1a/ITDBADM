<?php
include('db_connect.php');

$endpoint = 'latest'; // most recent echange rates
$access_key = '45b553100570e5758c6bf9d2ed685ff7'; // api key

$ch = curl_init('https://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$json = curl_exec($ch);
curl_close($ch);

$data = json_decode($json, true);

if ($data['success']) {
    foreach ($data['rates'] as $code => $rate) {
        // convert to "fromUSD" factor
        // fixer.io gives EUR→currency
        // make it USD→currency.
        $usdRate = $data['rates']['USD'];
        $fromUSD = $rate / $usdRate;

        // updates currency table
        $stmt = $conn->prepare("UPDATE currencies SET fromUSD=? WHERE currency=?");
        $stmt->bind_param("ds", $fromUSD, $code);
        $stmt->execute();
        $stmt->close();
    }
    echo "Currencies updated successfully!";
} else {
    echo "API error: " . $data['error']['info'];
}
?>
