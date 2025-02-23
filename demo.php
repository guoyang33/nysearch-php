<?php
// require_once './Config.php';
// $apiKey = Config::API_KEY;
// $json = file_get_contents('https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&datatype=json&symbol=VOO&apikey=' . $apiKey);
// $data = json_decode($json, true);
// print_r($data);
// exit;

// $d1 = date_create(date('Y-m-d'));
// print($d1->format('Y-m-d'));
try {    
    $a = 'a';
    $b = $a / 3;
} catch (ErrorException $e) {
    print('error');
} finally {
    echo 'done';
}