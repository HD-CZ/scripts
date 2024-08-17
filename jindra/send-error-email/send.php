<?php

// if(!$_POST['chartObject']){
//     //http_response_code(400);
//     showErrorAndSendEmailToAdmin("Missing POST data", $_GET);
//     exit;
// }


try{
    //Log each reaqeust
    date_default_timezone_set('Europe/Prague');
    $date = new \DateTime('now');
    $reqDump = "\r\n" . $date->format('j.n.Y G:i:s') . ' ' . json_encode($_POST);
    $jsonData = file_put_contents('error-emails-all.log', $reqDump, FILE_APPEND | LOCK_EX); // | LOCK_EX
    
    showErrorAndSendEmailToAdmin();
    
}
catch(Exception $e){
    
    date_default_timezone_set('Europe/Prague');
    $date = new \DateTime('now');
    $reqDump = "\r\n" . $date->format('j.n.Y G:i:s') . ' ' . json_encode($_POST);
    $jsonData = file_put_contents('error-emails-failed.log', $reqDump, FILE_APPEND | LOCK_EX); // | LOCK_EX
}



function showErrorAndSendEmailToAdmin()
{
    $message = "\r\n\r\nPOST params:\r\n";
    foreach ($_POST as $key => $value){
        $message .= htmlspecialchars($key) . " : " . htmlspecialchars($value) . "\r\n";
    }
	
	$message .= "\r\n\r\nGET params:\r\n";
	foreach ($_GET as $key => $value){
        $message .= htmlspecialchars($key) . " : " . htmlspecialchars($value) . "\r\n";
    }
    
    $message = str_replace( "&quot;", '"', $message);
    mail('podpora+web_error@humandesign.cz', 'Web JS error: ' . $_POST["subject"] . " | " . $_SERVER['SCRIPT_NAME'], $message, 'From: Web JS Error HD<podpora@humandesign.cz>');
    echo $message;
}

?>