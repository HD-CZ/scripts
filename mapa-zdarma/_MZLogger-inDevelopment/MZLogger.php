<?php

/**
 * Class RecaptchaLogger
 * Handles logging of reCAPTCHA warnings to daily log files.
 */
class MZLogger {
    private $logDir;

    /**
     * RecaptchaLogger constructor.
     * @param string $logDir Directory where log files will be stored.
     */
    public function __construct($logDir) {
        // Ensure the logDir ends with a directory separator
        $this->logDir = rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Create directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            if (!mkdir($this->logDir, 0755, true) && !is_dir($this->logDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->logDir));
            }
        }
    }

    /**
     * Logs a message and associated data to the daily log file.
     * @param string $message The message to log.
     * @param mixed $recaptchaResponse Additional data to log.
     * @param mixed $postData Additional data to log.
     */
    public function log($message, $recaptchaResponse, $postData) {
        $logFile = $this->logDir . 'MZ_warnings_' . date('Y-m-d') . '.log';
        
        // Read the existing log file to get the current number of warnings
        $numberOfWarnings = 0;
        $logContent = '';

        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            if ($logContent) {
                // Extract the first line which contains the number of warnings
                $lines = explode(PHP_EOL, $logContent);
                if (count($lines) > 0 && strpos($lines[0], 'Warnings:') === 0) {
                    $numberOfWarnings = (int)str_replace('Warnings:', '', $lines[0]);
                }
            }
        }

        // Increment the number of warnings
        $numberOfWarnings++;
		
        // Create the new log entry
        $logEntry = "----------------------------------------------------------------------------------------------------" . PHP_EOL;
        $logEntry .= date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        $logEntry .= "----------------------------------------------------------------------------------------------------" . PHP_EOL;
        $logEntry .= json_encode($recaptchaResponse, JSON_PRETTY_PRINT) . PHP_EOL;
        $logEntry .= json_encode($postData, JSON_PRETTY_PRINT) . PHP_EOL;

        // Prepend the number of warnings to the log content
        $newLogContent = 'Warnings: ' . $numberOfWarnings . PHP_EOL;
        $newLogContent .= $logContent ? implode(PHP_EOL, array_slice($lines, 1)) . PHP_EOL : '';
        $newLogContent .= $logEntry;

		
        // Write the updated log content back to the file
        if (file_put_contents($logFile, $newLogContent) === false) {
            throw new \RuntimeException('Failed to write to log file: ' . $logFile);
        }
    }
}
?>
