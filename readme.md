# Ortto to Google Sheets Sync

This Node.js application fetches contacts from the Ortto API and stores them in a Google Sheet. It checks for duplicates based on the email field before storing a contact. If the rate limit is reached, it waits for the specified time before continuing.

## Setup

1. Clone this repository to your local machine.

2. Install the necessary packages by running `npm install` in your terminal.

3. Create a `.env` file in your project root directory and add your Ortto API key, endpoint, Google Sheet ID, and sheet range:

    ```env
    ORTTO_API_KEY=your_api_key
    ORTTO_API_ENDPOINT=your_api_endpoint
    SPREADSHEET_ID=your_spreadsheet_id
    SHEET_RANGE=your_sheet_range
    ```

4. Create a `credentials.json` file in your project root directory and add your Google Sheets API credentials.

## Usage

Run the application by executing `node index.js` in your terminal.

## License

This project is licensed under the MIT License.
