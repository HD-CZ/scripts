<?php

if(empty($_POST) && empty($_GET))
{
	echo "chyba-zpracovani-objednavky nejsou vyplněné žádné parametry";
	die();
}


    //Script identification for debug purpouses
$_POST['phpScriptUrl'] = $_SERVER['SCRIPT_NAME'];
$_POST['refererUrl'] = $_SERVER['HTTP_REFERER'];
$_POST['phpRequestUri'] = $_SERVER['REQUEST_URI'];

 //some spam robot
if($_POST['Secondary_Lead_Source__c'] == "Newsletter Sign-up")
{
    // header( "Location: /chyba-zpracovani-objednavky/?error=spam-robot");
    // die();
}


if($_POST['serviceName'] == "Písemný rozbor")
{
	// reCaptcha v3
	if(isset($_POST['recaptcha']))
		$captcha = $_POST['recaptcha'];
	else
		$captcha = false;
	
    // $captcha = false; //Test purpouses
	if (!$captcha) 
		showErrorAndSendEmailToAdmin('Něco se nepodařilo během ověřování, zda se jedná o spamovou objednávku, prosíme o vyplnění formuláře znovu.');
	else 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'secret' => '6Lc17ycbAAAAAEMM4mk0pbiZgGTXs_P0dIeHux3g',
			'response' => $captcha,
			'remoteip' => $_SERVER['REMOTE_ADDR']
		]);
		
		$response = json_decode(curl_exec($ch));
		curl_close($ch);
		
        // $response->success = false; //Test purpouses
        //if ($response->success == false) 
            //showErrorAndSendEmailToAdmin('Nepodařilo se rozpoznat, zda se jedná o spamovou objednávku nebo ne. Prosíme o načtení a vyplnění znova', $response);
	}

	// $response->score = 0.2; //Test purpouses
	if ($response->success == true && $response->score < 0.5)
		showErrorAndSendEmailToAdmin('Vaše objednávka byla vyhodnocená jako spamová ('.$response->score.'), prosíme o vyplnění formuláře znovu', $response);
	
	$_POST['recaptcha'] = "";
}


//set POST variables
$url = 'https://script.google.com/macros/s/AKfycbzJcSx6a45Ae8ciwZC1ki_jj9Farok48o_aY0MFTuKmV1obovRX/exec'; //skript address: https://docs.google.com/spreadsheets/

$_POST['language'] = $_POST['language'] ?: "cz";

//url-ify the data for the POST
foreach($_POST as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
rtrim($fields_string, '&');

// echo '<a href='.$url .'?'. $fields_string.'>'.$url .'?'. $fields_string.'</a>';
// return;


if($_POST['email2'] != "")
{
	mail('jindrich.sirucek@gmail.com', 'SPAM', $fields_string, 'From: Podpora HD<podpora@humandesign.cz>');
}

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url .'?'. $fields_string);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


//perform our request
$result = curl_exec($ch);



// echo implode(" ",$_POST);
// echo $result;

curl_close($ch);

$splittedResult = explode('###', $result); //splits response text by ### to: [0] - succesfull(true/false), [1] - order ID, [2] added params to append to redirect url
if($result === "SPAM")
	return header( "Location: /chyba-zpracovani-objednavky-s-p-a-m/");

if($splittedResult[0] === "TRUE")
{
	$paymentType = (isset($_POST['paymentType']))?$_POST['paymentType']:NULL;

	//Voucher 100% - rovnou přesměruje na děkovačku
	if($paymentType === "voucher_100%"){
		if($_POST['serviceName'] == "Písemný rozbor"){
			$resultObj = array("success" => "true", "redirectUrl" => $_SERVER['HTTP_HOST'] . "/pisemny-rozbor/objednavka-prijata/");
			echo json_encode($resultObj);
			die();
		}else{
			return header( "Location: ". "/pisemny-rozbor/objednavka-prijata/");
		}
	}
	
	//Voucher x% - přesměruje na slevovou platební stránku
	if($_POST['voucherCode'] != ""){
		if($_POST['serviceName'] == "Písemný rozbor"){
			$resultObj = array("success" => "true", "redirectUrl" => $_SERVER['HTTP_HOST'] . "/pisemny-rozbor/fakturacni-udaje-sleva/" .
				"?fullname=".urlencode($_POST['name'])."&email=".urlencode($_POST['email']).
				"&serviceQuantity=".$_POST['serviceQuantity']."&voucherCode=".$_POST['voucherCode']."&d=".$_POST['discount'].
				"&orderId=".urlencode($splittedResult[1]?: $result) . ($splittedResult[2]?: $result)); //splittedResult[2] additional url params encoded
			echo json_encode($resultObj);
			die();
		}else{
			return header( "Location: ". "/pisemny-rozbor/fakturacni-udaje-sleva/" .
				"?fullname=".urlencode($_POST['name'])."&email=".urlencode($_POST['email']).
				"&serviceQuantity=".$_POST['serviceQuantity']."&voucherCode=".$_POST['voucherCode']."&d=".$_POST['discount'].
				"&orderId=".urlencode($splittedResult[1]?: $result).urlencode($splittedResult[2]?: $result));
		}
	}
	//Žádný Voucher - přesměruje na normální platební stránku
	if($paymentType === "fapi"){
		if($_POST['serviceName'] == "Písemný rozbor"){
			$resultObj = array("success" => "true", "redirectUrl" => $_SERVER['HTTP_HOST'] .''. $_POST['fapiFormUrlRedirectRelative'] ."?fullname=".urlencode($_POST["name"])."&email=".urlencode($_POST["email"])."&serviceQuantity=".$_POST["serviceQuantity"]."&orderId=".urlencode($splittedResult[1]?: "").($splittedResult[2]?: ""));
			echo json_encode($resultObj);
			die();
		}else{
			return header( "Location: ". $_POST['fapiFormUrlRedirectRelative'] 
			."?fapi-form-name=".urlencode($_POST['fapi-form-name'])
			."&fapi-form-surname=".urlencode($_POST['fapi-form-surname'])
  		    ."&fapi-form-email=".urlencode($_POST['fapi-form-email'])
			."&serviceQuantity=".$_POST['serviceQuantity']
			."&orderId=".urlencode($splittedResult[1]?: "").($splittedResult[2]?: ""));
		}
	}
	if($paymentType === "payPal"){
		if($_POST['serviceName'] == "Písemný rozbor"){
			$resultObj = array("success" => "true", "redirectUrl" => $_POST['baseRedirectUrl'] . "/" . $_POST['paymentFormUrlRedirectRelative'] ."?fullname=".urlencode($_POST['name'])."&email=".urlencode($_POST['email'])."&serviceQuantity=".$_POST['serviceQuantity']."&orderId=".urlencode($splittedResult[1]?: ""));
			echo json_encode($resultObj);
			die();
		}else{
			return header( "Location: ". $_POST['baseRedirectUrl'] . "/" . $_POST['paymentFormUrlRedirectRelative'] ."?fullname=".urlencode($_POST['name'])."&email=".urlencode($_POST['email'])."&serviceQuantity=".$_POST['serviceQuantity']."&orderId=".urlencode($splittedResult[1]?: ""));
		}
	}
	if($paymentType === "none"){
		if($_POST['serviceName'] == "Písemný rozbor"){
			$resultObj = array("success" => "true", "redirectUrl" => $_POST['baseRedirectUrl'] . "/" . $_POST['successUrlRedirectRelative']);
			echo json_encode($resultObj);
			die();
		}else{
			return header( "Location: ". $_POST['baseRedirectUrl'] . "/" . $_POST['successUrlRedirectRelative']);
		}
	}
}



// echo print_r($splittedResult,true);
$subject ="Uživateli vyskočila chyba-zpracovani-objednavky";
mail("jindrich.sirucek+error@gmail.com", $subject, print_r(get_defined_vars(), true));
header( "Location: /chyba-zpracovani-objednavky/#" . $result);


/*
//show information regarding the request
print_r(curl_getinfo($curl_connection));

echo curl_errno($curl_connection) . '-' . 
                curl_error($curl_connection);
*/

function showErrorAndSendEmailToAdmin($errorText, $response = "{}")
{
	$message = $errorText;
	$message .= "\r\n\r\nPOST params:\r\n";
	foreach ($_POST as $key => $value){
		$message .= htmlspecialchars($key) . " : " . htmlspecialchars($value) . "\r\n";
	}
                	
  $message .= "\r\n\r\nRecaptcha params:\r\n";// . json_encode($response);
	// $response = json_decode($response);
  foreach ($response as $key => $value){
  	$message .= htmlspecialchars($key) ." : " . htmlspecialchars($value) . "\r\n";
  }

  $message .= "\r\nPOST JSON inline:\r\n" . json_encode($_POST) . "\r\n";

  mail('podpora@humandesign.cz', 'PR captcha chyba: ' . $errorText, $message);
  
  $resultObj = array("success" => "false", "errorText" => $errorText, "redirectUrl" => $_SERVER['HTTP_HOST'] . "/chyba-zpracovani-objednavky/?errorText=" . urlencode($errorText));
  // echo json_encode($resultObj);
  // die();
}

?>