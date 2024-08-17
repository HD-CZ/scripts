<?php

// The target URL to send requests to
$url = "https://astro.humandesign.cz/freechartApp/saveFreechart.php";



// Number of requests to send
$totalRequests = 150;

// Total time to send all requests (in seconds)
$totalTime = 1 * 60; // 1 minute

// Time interval between requests (in seconds)
$interval = $totalTime / $totalRequests;

for ($i = 1; $i <= $totalRequests; $i++) {

	// Sample data to be sent in each request
$sampleData = [
    "created" => time() * 1000,  
    "sourceUrl" => "/some/path",
    "formType" => "sampleForm",
    "recaptcha" => "sampleRecaptchaToken",
    "email" => "podpora@humandesign.cz",
    "day" => rand(1, 28),
    "month" => "8",        
    "year" => "2024",
    "hour" => "12",
    "minute" => "30",
    "city" => "Prague",
    "country" => "Czech Republic"
];
	 // Update the name for each request
    $sampleData["name"] = "Petr-test-$i";
    
    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sampleData));

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo "Request $i failed: " . curl_error($ch) . "\n";
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo "Request $i completed with HTTP status code $httpCode\n";
    }

    // Close the cURL session
	echo var_dump($response) ."<br />";
    curl_close($ch);

    // Wait for the specified interval before sending the next request
    usleep($interval * 1000000); // Convert seconds to microseconds for usleep
}

echo "All $totalRequests requests completed.\n";

?>
