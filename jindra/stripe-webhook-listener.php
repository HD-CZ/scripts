<?php
// Get input data from the original request
$requestData = $_REQUEST;
$payload = @file_get_contents('php://input');
//echo $payload;
//$jsonData = json_encode($payload);

// Data, která chcete odeslat
$postData = array(
  'notifObj' => $payload
);
//var_dump($postData);

// Target URL where the requests will be redirected
$targetUrl = 'https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec'; //https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec
//https://putsreq.com/FOnCFwKu4E0U0uLMyaEy

// Append GET parameters to the target URL
if (!empty($_GET)) {
    $targetUrl .= '?' . http_build_query($_GET);
}


// Create a new request using cURL
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


// Set input data for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($postData));
}

// Get the response from the target URL
$response = curl_exec($ch);

// Pass the response back to the client
header('Content-Type: application/json');
echo $response;

// Close the cURL connection
curl_close($ch);
?>
