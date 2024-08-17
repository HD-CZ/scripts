<?php

//set POST variables
$url = 'https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec?woocomerceWebhook=true'; //skript address
$_POST['source'] = "hd.cz/ woocommerceWebhook.php";
$_POST['refererPage'] = $_SERVER['HTTP_REFERER'];

$refererQuery = parse_url($_POST['refererPage'], PHP_URL_QUERY);
parse_str($refererQuery, $refererParams);

//url-ify the data for the POST
foreach($_POST as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
rtrim($fields_string, '&');


//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url .'&'. $fields_string);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//perform our request\

$result = curl_exec($ch);
curl_close($ch);


?>