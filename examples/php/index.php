<?php
require __DIR__ . '/vendor/autoload.php';

use Google_Client;
use Google_Service_Sheets;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Replace with your Google Sheet ID and range
$spreadsheetId = $_ENV['SPREADSHEET_ID'];
$sheetRange = $_ENV['SHEET_RANGE'];

// Load client secrets from a local file.
$client = new Google_Client();
$client->setApplicationName('Google Sheets API PHP Quickstart');
$client->setScopes(Google_Service_Sheets::SPREADSHEETS);
$client->setAuthConfig('credentials.json');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

$service = new Google_Service_Sheets($client);

// Function to fetch contacts from the API
function fetchContacts($api_key, $endpoint, $offset, $service, $spreadsheetId, $sheetRange) {
    $httpClient = new Client(['base_uri' => $endpoint]);
    try {
        $response = $httpClient->request('GET', '', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key
            ],
            'query' => [
                'offset' => $offset
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody(), true);
            foreach ($data['contacts'] as $contact) {
                storeContact($contact, $service, $spreadsheetId, $sheetRange);
            }

            if ($data['has_more']) {
                fetchContacts($api_key, $endpoint, $data['next_offset'], $service, $spreadsheetId, $sheetRange);
            }
        }
    } catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() == 429) {
            // If rate limited, wait for the specified time and then continue
            $waitTime = $e->getResponse()->getHeaderLine('Retry-After');
            sleep($waitTime);
            fetchContacts($api_key, $endpoint, $offset, $service, $spreadsheetId, $sheetRange);
        } else {
            throw $e;
        }
    }
}

// Function to store a contact in the Google Sheet
function storeContact($contact, $service, $spreadsheetId, $sheetRange) {
    $response = $service->spreadsheets_values->get($spreadsheetId, $sheetRange);
    $values = $response->getValues();

    // Check for duplicates
    foreach ($values as $row) {
        if ($row[1] == $contact['fields']['str::email']) {
            return;
        }
    }

    // Store the contact
    $values = [
        [$contact['id'], $contact['fields']['str::email']]
    ];
    $body = new Google_Service_Sheets_ValueRange([
        'values' => $values
    ]);
    $params = [
        'valueInputOption' => 'RAW'
    ];
    $service->spreadsheets_values->append($spreadsheetId, $sheetRange, $body, $params);
}

// Get Ortto API key and endpoint from environment variables
$api_key = $_ENV['ORTTO_API_KEY'];
$endpoint = $_ENV['ORTTO_API_ENDPOINT'];
$offset = 0;

fetchContacts($api_key, $endpoint, $offset, $service, $spreadsheetId, $sheetRange);
