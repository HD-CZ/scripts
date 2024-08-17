<?php
include 'SmartEmailing_API.php';
include dirname(__FILE__) . '/recaptchaLogger/recaptchaLogger.php';

//include dirname(__FILE__) . '/../' - this goes one level up and from there follow the path
include dirname(__FILE__) . '/../jirka/mapa_zdarma_assets/assets/countries.php';

// Empty call
if (empty($_POST) && empty($_GET))
{
	//echo var_dump($_GET); // Test purposes
	$errorMessage = showErrorAndSendEmailToAdmin("ERROR: nejsou vyplněné žádné parametry | " . $_SERVER['SCRIPT_NAME']);
	exit($errorMessage); //Empty request - nothing to process (probably a BOT)
}


// Script identification for debug purposes
$_POST['phpScriptUrl'] = $_SERVER['SCRIPT_NAME'];
$_POST['refererUrl'] = $_SERVER['HTTP_REFERER'] ?? '';


//ERROR HANDLING

/**
 * Custom error handler to convert errors to exceptions.
 *
 * @param int $errno The level of the error raised.
 * @param string $errstr The error message.
 * @param string $errfile The filename that the error was raised in.
 * @param int $errline The line number the error was raised at.
 * @throws ErrorException Throws an ErrorException based on the error.
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

/**
 * Custom exception handler to log uncaught exceptions.
 *
 * @param Exception $exception The uncaught exception.
 */
function customExceptionHandler($exception) {
	showErrorAndSendEmailToAdmin('ERROR: Uncaught Exception: ' . $exception->getMessage(), ['exception' => $exception->getTraceAsString()]);
	$resultObj = ["status" => "ERROR", "message" => $exception->getMessage()];
	echo json_encode($resultObj);
	exit();
}

// Set the custom error and exception handlers
set_error_handler("customErrorHandler");
set_exception_handler("customExceptionHandler");


// Simulate an empty request for testing
//$_POST = []; // Uncomment to simulate empty request
// test ok

// Simulate an incomplete request for testing
//$_POST = ['email' => 'test@example.com', 'name' => 'Test User','recaptcha' => 'test']; // Uncomment to simulate incomplete request
// test ok


//MAIN CODE
try {
	
	// reCAPTCHA Google v3
    $captcha = $_POST['recaptcha'] ?? false;
    if (!$captcha) {
        showErrorAndSendEmailToAdmin('WARNING: Captcha could not be verified IS EMPTY.');
        exit(); //Někdo pravděpodobně manipuloval s daty před odesláním na script
	}
	
	//Check captcha
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
	
	$recaptchaResponseString = curl_exec($ch);
	$response = json_decode($recaptchaResponseString);
	curl_close($ch);


	// Test purposes
	//$response->score = 0.2; // This should trigger the logging
	//$response->success = false; // Also set success to false to ensure logging
	
	// Handle custom spam score - log warnings to daily log file. Sent to admin by email using cron
	
    if (!$response->success || $response->score < 0.3) {
		$logger = new recaptchaLogger(); // Initiate a recaptchaLogger instance
		$logger->log('WARNING: Recaptcha nelze ověřit. MZ ale odeslána.',$response, $_POST); // log warning to a file
        //showErrorAndSendEmailToAdmin('WARNING: Recaptcha nelze ověřit. MZ ale odeslána.',json_encode($response, JSON_PRETTY_PRINT)); 
		//exit(); //not exiting bcs its just warning for us
    }
	
	} catch (Exception $exception) {
    	showErrorAndSendEmailToAdmin('ERROR: Captcha unknwon error: ' . $exception->getMessage(), 
									 ['exceptionTrace' => $exception->getTraceAsString(),
									 'captcha response' => json_encode($response, JSON_PRETTY_PRINT)]);
	//exit(); //not exiting bcs its just warning for us
	}
	

    // Default country if not provided
    if (empty($_POST['country'])) {
        $_POST['country'] = "Czech republic";
    }

    // Convert country code to country name
    if (strlen($_POST['country']) === 2) {
        $_POST['country'] = CI_COUNTRIES_ARRAY[$_POST['country']] ?? $_POST['country'];
    }
	
	//Process double optin logic
    $SEapi = new SEAPI(); // Create new instance of SEAPI class
    $SEAPIresponse = $SEapi->addNewContactToSEWithDoubleOptInAndJsonBirthData($_POST); // SE response Array

//	$SEAPIresponseGLOBAL = $SEAPIresponse;
//	if(empty($SEAPIresponse['isNewContact']))
//		showErrorAndSendEmailToAdmin('DeBUG:'. json_encode($SEAPIresponse));
		
    // If any error occurs in SE API request - tohle se ještě nestalo :D
    if ($SEAPIresponse['status'] === "ERROR") {
        showErrorAndSendEmailToAdmin('ERROR: SE API (přidání kontaktu + vyvolání DO chyba): ' . $SEAPIresponse['message']);
        $resultObj = ["status" => "ERROR", "message" => $SEAPIresponse['message']];
        echo json_encode($resultObj);
        exit();
    }


    // Prepare final result object to return back to HTML form
    $resultObj = ["status" => "??", "isNewContact" => $SEAPIresponse['isNewContact']];

    //NEW CONTACT: Contact is NEW, DO email was sent - exit and redirect
    if ($SEAPIresponse['isNewContact'] === true) {
		//Return response back to frontend to redirect to DO thank you page ("... one more step..")
		//show user page with info that DO email was send to their inbox to click
		$resultObj = ["status" => "OK", "isNewContact" => $SEAPIresponse['isNewContact']];
        echo json_encode($resultObj); 
        exit(); //Normal exit
	}

	//OLD CONTACT: Contact already exists, send to freechart directly	
	if ($SEAPIresponse['isNewContact'] === false) {
		try {
			$ch = curl_init();
			$url = 'https://astro.humandesign.cz/freechartApp/saveFreechart.php';
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true); // POST
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST)); // POST 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$resultString = curl_exec($ch);
			curl_close($ch);
			
			$response = json_decode($resultString);
			
			// String returned from queue script should be: "Mapa byla uložena do databáze"			
    		if ($response->message !== "Mapa byla uložena do databáze") {
				$resultObj['status'] = "ERROR";
				$resultObj['message'] = $response->message;
				showErrorAndSendEmailToAdmin('ERROR: MZ Could not save to QUEUE: ', $response->message);
			}

			//everything went ok
			$resultObj = ["status" => "OK", "isNewContact" => $SEAPIresponse['isNewContact']];
			echo json_encode($resultObj);
	        exit();
		} 
		catch(Exception $exception) {
			showErrorAndSendEmailToAdmin('ERROR: MZ SEND:' . $exception->getMessage(), 
										 ['Trace Stack' => $exception->getTraceAsString()]);
			
			$resultObj['status'] = "ERROR";
			$resultObj['message'] = $exception->getMessage();
			echo json_encode($resultObj);
	        exit();
		}
    }


/**
 * Logs error and sends an email to the admin.
 *
 * @param string $errorText The error message.
 * @param array|string $response Additional response data to log.
 */
function showErrorAndSendEmailToAdmin($errorText, $response="{}" ) {
    $message = $errorText;
    $message .= "\r\n\r\nPOST params:\r\n";
    foreach ($_POST as $key => $value) {
        $message .= htmlspecialchars($key) . " : " . htmlspecialchars($value) . "\r\n";
    }

    $message .= "\r\n\r\nStringified Debug Objects:\r\n";
    if (is_array($response) || is_object($response)) {
        foreach ($response as $key => $value) {
            $message .= htmlspecialchars($key) . " : " . htmlspecialchars($value) . "\r\n";
        }
    } else {
        $message .= $response;
    }

    $message .= "\r\nPOST JSON inline:\r\n" . json_encode($_POST) . "\r\n";

    mail('podpora+mz_error@humandesign.cz', '[MZ PHP] ' . $errorText, $message, 'From: MZ error<podpora@humandesign.cz>');
	return $message;
	//exit(); // You need to ensure the script to end after calling this function if you want to end script even after logging an error (e.g. captcha logs error but srcipts go on and on and on and on :D)
}
?>