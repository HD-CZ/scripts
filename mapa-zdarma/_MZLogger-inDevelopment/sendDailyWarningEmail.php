<?php

/**
 * Sends a daily report email with reCAPTCHA warnings logged the previous day.
 */
function sendDailyReport() {
    $logDir = realpath(__DIR__ . '/logs/');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $logFile = $logDir . '/MZ_warnings_' . $yesterday . '.log';

    $to = 'podpora+mz_error@humandesign.cz';
	//$subject = '[PHP MZ] Daily warning report for ' . $yesterday; //defined lower
    $from = 'MZ error <podpora@humandesign.cz>';
    $boundary = md5(time());

    // Headers
    $headers = "From: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Message body
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

    if (file_exists($logFile)) {
        $logContents = file_get_contents($logFile);
        if ($logContents) {
            // Read the first line of the log file
            $firstLine = '';
            $fileHandle = fopen($logFile, 'r');
            if ($fileHandle) {
                $firstLine = fgets($fileHandle);
                fclose($fileHandle);
            }

            // Trim and sanitize the first line
            $firstLine = trim($firstLine);
			$subject = '[PHP MZ] '. $firstLine .'x Daily report for ' . $yesterday;

            $message .= "Please find the attached log file for the daily MZ warning report.\r\n";
            $message .= "--$boundary\r\n";

            // Attachment
            $filename = basename($logFile);
            $message .= "Content-Type: text/plain; name=\"$filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
            $message .= chunk_split(base64_encode($logContents)) . "\r\n";
            $message .= "--$boundary--";
        }
    } else {
		$subject = '[PHP MZ] Warnings: 0x Daily report for ' . $yesterday;
        $message .= "No warnings were produced yesterday.\r\n";
        $message .= "--$boundary--";
    }

    // Send email
    mail($to, $subject, $message, $headers);
}

// Call the function to send the daily report email
sendDailyReport();
?>
