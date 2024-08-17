<?php
//Still a lot in use 26.10.2022
if(empty($_POST) && empty($_GET))
{
	echo "chyba-zpracovani-objednavky nejsou vyplněné žádné parametry";
	die();
}

if(empty($_POST['baseRedirectUrl']))
{
	echo "chyba-zpracovani-objednavky odeslání neproběhlo z formuláře";
	die();
}

//set POST variables
$url = 'https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec'; //skript address: https://docs.google.com/spreadsheets/
$_POST['source'] = "hd.cz/newOrderSeminar.php";
// $_POST['refererPage'] = $_SERVER['HTTP_REFERER'];

$refererQuery = parse_url($_POST['refererPage'], PHP_URL_QUERY);
parse_str($refererQuery, $refererParams);
$_POST['variableSymbol'] = $refererParams['vs'];


// $debugURL = "https://script.google.com/a/humandesign.cz/macros/s/AKfycbwsWCpz4WP2_ClYB4mItkw6kTejV6zU5_97sIUeydY3/dev";

//url-ify the data for the POST
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
$redirectUrlFinal = $_POST['baseRedirectUrl'] . $_POST['successRedirectRelativeUrl'];

if ($splittedResult[0] === "TRUE")
{
	$paymentType = (isset($_POST['paymentType']))?$_POST['paymentType']:NULL;
	if($paymentType === "fapi")
		header( "Location: ". $_POST['fapiFormUrlRedirectAbsolute'] ."?fullname=".urlencode($_POST['name'])."&email=".urlencode($_POST['email'])."&serviceQuantity=".$_POST['serviceQuantity']."&orderId=".urlencode($splittedResult[1]?: ""));
	else
		header( "Location: " . $redirectUrlFinal);
}
else
{
	{
	// echo print_r($splittedResult,true);
		$subject ="Uživateli vyskočila chyba-zpracovani-objednavky";
		mail("jindrich.sirucek+error@gmail.com", $subject, print_r(get_defined_vars(), true));
	}
	if($_POST['failureRedirectRelativeUrl'])
		header( "Location: ". $_POST['baseRedirectUrl'] . $_POST['failureRedirectRelativeUrl'] . "#" . $result);
}





// 
/*
//show information regarding the request
print_r(curl_getinfo($curl_connection));

echo curl_errno($curl_connection) . '-' . 
                curl_error($curl_connection);
*/

?>