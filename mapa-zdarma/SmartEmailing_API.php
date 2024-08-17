<?php
// https://humandesign.cz/scripts/petr/generate_DO_SE_jindra.php
// birth data json template for debugging
//$birthDataJson = '{"sourceUrl":"\/mapa\/dev\/","formType":"MOB","recaptcha":"","name":"Jindra test","email":"jindrichsirucek@gmail.com","day":"25","month":"4","year":"1965","hour":"07","minute":"51","city":"Prague","country":"Czech Republic","phpScriptUrl":"\/scripts\/mapaZdarma-DEV.php","refererUrl":"https:\/\/humandesign.cz\/mapa\/dev\/"}';

//$birthDataJson = '{"sourceUrl":"\/mapa\/dev\/","formType":"MOB","recaptcha":"","name":"Petr test","email":"beek.spk@gmail.com","day":"25","month":"4","year":"1965","hour":"07","minute":"51","city":"Prague","country":"Czech Republic","phpScriptUrl":"\/scripts\/mapaZdarma-DEV.php","refererUrl":"https:\/\/humandesign.cz\/mapa\/dev\/"}';

/**
 * SEAPI class to interact with Smart Emailing API.
 */
class SEAPI {
    /**
     * @var string $user The username for SE API.
	 * @var string $token The token for SE API.
	 * @var string $endpoint The endpoint for SE API.
     */
    private $user = 'jakub@humandesign.cz';
    private $token = 'uydpgx3zxauf6m8goo56rwwocqpi86kvzjzwjqho';
	private $endpoint = "https://app.smartemailing.cz/api/v3/import";
    
    /**
     * @var int $campaignEmailID The SE email campaign ID.
     */
    private $campaignEmailID = 335;
    
    /**
     * @var string $senderFrom The sender email address.
     */
    private $senderFrom = "info@humandesign.cz";
    
    /**
     * @var string $senderReply The sender reply-to email address.
     */
    private $senderReply = "info@humandesign.cz";
    
    /**
     * @var string $senderName The sender name.
     */
    private $senderName = "Jakub - humandesign.cz";
    
    /**
     * @var string $confirmationUrl The URL for DO confirmation thank you page.
     */
    private $confirmationUrl = "https://humandesign.cz/mapa/email-potvrzen/";
    
    /**
     * @var int $contactListID The SE contact list ID.
     */
    private $contactListID = 28;
    
    /**
     * @var string $contactStatus The contact status in the contact list.
     */
    private $contactStatus = "confirmed";
    
    /**
     * @var int $customFieldBirthtDataID The SE custom field ID for birth data.
     */
    private $customFieldBirthtDataID = 26;
    
    /**
     * @var int $customFieldUuidID The SE custom field ID for UUID.
     */
    private $customFieldUuidID = 32;
    
    /**
     * @var int $purposeID The DO purpose ID for the contact.
     */
    private $purposeID = 1; //1 - Oprávněný zájem

    /**
     * Adds a new contact to SE with double opt-in and JSON birth data.
     *
     * @param string $email The email address of the contact.
     * @param string $birthDataJson The JSON string containing birth data.
     * @return array The response from SE API.
     */
    public function addNewContactToSEWithDoubleOptInAndJsonBirthData($postData) {
		
        $payload = $this->preparePayload($postData);
        $payloadJson = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->token);
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payloadJson)
        ]);
    
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->handleSEResponse($result, $httpCode);
    }

    /**
     * Prepares the payload for the SE API request.
     *
     * @param array $postData from html form
     * @return array The payload for the SE API request.
     */
    private function preparePayload($postData) {
        $generatedUuid = $postData["created"];
		$email = $postData["email"];
		$postData['recaptcha'] = ""; // Remove recaptcha to not save redundant data to SE custom field
		$birthDataJson = json_encode($postData); // Prepare form data string for SE custom field
        
        return [
            "settings" => [
                "update" => true,
                "preserve_unsubscribed" => false,
                "skip_invalid_emails" => false,
                "double_opt_in_settings" => [
                    "campaign" => [
                        "email_id" => $this->campaignEmailID,
                        "sender_credentials" => [
                            "from" => $this->senderFrom,
                            "reply_to" => $this->senderReply,
                            "sender_name" => $this->senderName
                        ],
                        "confirmation_thank_you_page_url" => $this->confirmationUrl ."?id=". $generatedUuid . "&email=" . urlencode($email)
                    ],
                    "send_to_mode" => "new-in-database",
					//if user tries to send new request before accepting DO email it will send new email - this value needs to be small so user can recieve new email with request for DO
                    "silence_period" => [
                        "unit" => "seconds",
                        "value" => 1
                    ]
                ]
            ],
            "data" => [
                [
                    "emailaddress" => $email,
                    "contactlists" => [
                        [
                            "id" => $this->contactListID,
                            "status" => $this->contactStatus
                        ]
                    ],
                    "customfields" => [
                        [
                            "id" => $this->customFieldBirthtDataID,
                            "value" => (string) $birthDataJson
                        ],
                        [
                            "id" => $this->customFieldUuidID,
                            "value" => (string) $generatedUuid
                        ]
                    ],
                    "purposes" => [
                        [
                            "id" => $this->purposeID
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Handles the SE API response.
     *
     * @param string $result The result from the SE API request.
     * @param int $httpCode The HTTP status code from the SE API request.
     * @return array The processed response.
     */
    private function handleSEResponse($result, $httpCode) {
        if ($httpCode >= 200 && $httpCode < 300) {
            $response = json_decode($result, true);
            
            // Determine the response type
            if (!empty($response['double_opt_in_map'])) {
                // Double opt-in triggered
                return [
                    'status' => 'OK',
                    'isNewContact' => true,
                ];
            } elseif (!empty($response['contacts_map'])) {
                // Contact is in the database or is blacklisted
                return [
                    'status' => 'OK',
                    'isNewContact' => false,
                ];
            } else {
                // Unexpected response (not likely)
                return ['status' => 'ERROR', 'message' => 'Unexpected response from the server '.$result];
            }
        } else {
            // API response error
            return ['status' => 'ERROR', 'message' => 'API response error ('.$httpCode.'): '.$result];
        }
    }
}

// Usage example test - debug
/*$api = new SEAPI();
$response = $api->addNewContactToSEWithDoubleOptInAndJsonBirthData('beek.spk@gmail.com', $birthDataJson);
print_r($response);
echo json_encode($response);
echo json_encode($birthDataJson);*/
?>
