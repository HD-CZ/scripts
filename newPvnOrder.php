<?php
//Still a lot in use 26.10.2022
if(empty($_POST) && empty($_GET))
{
	echo "chyba-zpracovani-objednavky nejsou vyplněné žádné parametry";
	die();
}

//set POST variables
$url = 'https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec'; //BE

//Script identification for debug purpouses
$_POST['phpScriptUrl'] = $_SERVER['SCRIPT_NAME'];
$_POST['refererUrl'] = $_SERVER['HTTP_REFERER'];
$_POST['phpRequestUri'] = $_SERVER['REQUEST_URI'];

//url-ify the data for the POST
$fields_string = "";
foreach($_POST as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
rtrim($fields_string, '&');

// echo '<a href='.$debugURL .'?'. $fields_string.'>'.$debugURL .'?'. $fields_string.'</a>';
// return;

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url .'?'. $fields_string);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//perform our request\

$result = curl_exec($ch);
// echo implode(" ",$_POST);
// echo $url .'?'. $fields_string;
// echo $result;

curl_close($ch);


$splittedResult = explode('###', $result); //splits response text by ### to: [0] - succesfull(true/false), [1] - order ID

$splittedResult[0] === "TRUE";

echo $result;



// 
/*
//show information regarding the request
print_r(curl_getinfo($curl_connection));

echo curl_errno($curl_connection) . '-' . 
                curl_error($curl_connection);
*/

?>