// Load environment variables from .env file
require('dotenv').config();

// Import necessary packages
const {google} = require('googleapis');
const axios = require('axios');

// Initialize Google Sheets API client
const sheets = google.sheets('v4');
const auth = new google.auth.GoogleAuth({
  credentials: JSON.parse(process.env.GOOGLE_CREDENTIALS),
  scopes: ['https://www.googleapis.com/auth/spreadsheets'],
});

// Function to fetch contacts from the Ortto API
async function fetchContacts(auth, spreadsheetId, sheetRange) {
  // Get Ortto API key and endpoint from environment variables
  const apiKey = process.env.ORTTO_API_KEY;
  const endpoint = process.env.ORTTO_API_ENDPOINT;
  let offset = 0;

  // Loop until all contacts are fetched
  while (true) {
    try {
      // Make a GET request to the Ortto API
      const response = await axios.get(endpoint, {
        headers: {
          Authorization: `Bearer ${apiKey}`,
        },
        params: {
          offset: offset,
        },
      });

      const data = response.data;
      // Loop through the contacts and store each one in the Google Sheet
      for (const contact of data.contacts) {
        await storeContact(auth, spreadsheetId, sheetRange, contact);
      }

      // If there are no more contacts, break the loop
      if (!data.has_more) {
        break;
      }

      // Otherwise, update the offset for the next request
      offset = data.next_offset;
    } catch (error) {
      if (error.response.status === 429) {
        // If rate limited, wait for the specified time and then continue
        const waitTime = error.response.headers['retry-after'];
        await new Promise(resolve => setTimeout(resolve, waitTime * 1000));
      } else {
        throw error;
      }
    }
  }
}

// Function to store a contact in the Google Sheet
async function storeContact(auth, spreadsheetId, sheetRange, contact) {
  // Get the existing values in the Google Sheet
  const response = await sheets.spreadsheets.values.get({
    auth: auth,
    spreadsheetId: spreadsheetId,
    range: sheetRange,
  });

  const values = response.data.values || [];

  // Check for duplicates
  for (const row of values) {
    if (row[1] === contact.fields['str::email']) {
      return;
    }
  }

  // Append the contact to the Google Sheet
  await sheets.spreadsheets.values.append({
    auth: auth,
    spreadsheetId: spreadsheetId,
    range: sheetRange,
    valueInputOption: 'RAW',
    requestBody: {
      values: [[contact.id, contact.fields['str::email']]],
    },
  });
}

// Get spreadsheet ID and sheet range from environment variables
const spreadsheetId = process.env.SPREADSHEET_ID;
const sheetRange = process.env.SHEET_RANGE;

// Fetch contacts from the Ortto API and store them in the Google Sheet
fetchContacts(auth, spreadsheetId, sheetRange);
