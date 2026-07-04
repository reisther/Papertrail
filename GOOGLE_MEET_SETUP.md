# Google Meet Integration Setup Guide

This guide will help you set up Google Meet integration for the PaperTrail MS defense scheduling system.

## Features

- **Automatic Google Meet Link Generation**: Create unique Google Meet links for each defense schedule
- **Google Calendar Integration**: Automatically create calendar events with Meet links
- **Email Invitations**: Send calendar invites to all participants (student, adviser, panel members)
- **Fallback Support**: If API integration fails, generates simple Google Meet links
- **Multiple Platform Support**: Choose between Google Meet, manual links, Zoom, or Teams

## Prerequisites

1. Google Cloud Console account
2. Google Calendar API enabled
3. OAuth 2.0 credentials configured

## Setup Instructions

### Step 1: Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Calendar API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Calendar API"
   - Click "Enable"

### Step 2: Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth 2.0 Client ID"
3. Configure the consent screen if prompted
4. Choose "Web application" as application type
5. Add authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (for local development)
   - `https://papertrailpsu.com/auth/google/callback` (for production)
6. Download the JSON credentials file

### Step 3: Laravel Configuration

1. **Install Google API Client** (if not already installed):
   ```bash
   composer require google/apiclient
   ```

2. **Place Credentials File**:
   - Copy the downloaded JSON file to `storage/app/google-credentials.json`

3. **Update Environment Variables**:
   - Copy settings from `.env.google-meet-example` to your `.env` file
   - Update with your actual credentials:
   ```env
   GOOGLE_APPLICATION_NAME="PaperTrail MS"
   GOOGLE_CALENDAR_ID=primary
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   GOOGLE_CREDENTIALS_PATH=storage/app/google-credentials.json
   ```

4. **Run Database Migration**:
   ```bash
   php artisan migrate
   ```

### Step 4: OAuth Authorization (First Time Setup)

1. Create a route for Google OAuth (add to `routes/web.php`):
   ```php
   Route::get('/auth/google', function() {
       $googleMeetService = new \App\Services\GoogleMeetService();
       return redirect($googleMeetService->getAuthUrl());
   });

   Route::get('/auth/google/callback', function(Request $request) {
       $googleMeetService = new \App\Services\GoogleMeetService();
       if ($googleMeetService->handleCallback($request->get('code'))) {
           return redirect('/defense-schedule')->with('success', 'Google Meet integration configured successfully!');
       }
       return redirect('/defense-schedule')->with('error', 'Failed to configure Google Meet integration.');
   });
   ```

2. Visit `/auth/google` in your browser to authorize the application
3. Complete the OAuth flow

## Usage

### Creating Defense Schedules with Google Meet

1. Go to the defense schedule creation form
2. Select "Google Meet (Auto-generate)" as the meeting platform
3. Check "Send calendar invites to participants" if desired
4. Fill in other schedule details
5. Submit the form

The system will:
- Create a Google Calendar event
- Generate a unique Google Meet link
- Send calendar invites to all participants
- Store the meeting information in the database

### Manual Google Meet Links

If you prefer not to use the full API integration:
1. Select "Manual Link Entry" as the meeting platform
2. Click "Generate Meet Link" to create a simple Google Meet link
3. Or enter your own meeting link

## Troubleshooting

### Common Issues

1. **"Google Meet integration is not properly configured"**
   - Check that the credentials file exists at the specified path
   - Verify the JSON file is valid
   - Ensure Google Calendar API is enabled

2. **"Failed to create Google Meet event"**
   - Check OAuth authorization is complete
   - Verify calendar permissions
   - Check application logs for detailed error messages

3. **Calendar invites not sent**
   - Ensure participant email addresses are valid
   - Check Google Calendar API quotas
   - Verify OAuth scopes include calendar access

### Logs

Check Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

### Testing

Test the integration:
1. Create a test defense schedule with Google Meet
2. Check that the calendar event is created
3. Verify the Meet link works
4. Confirm invites are sent to participants

## Security Considerations

1. **Credentials Security**:
   - Never commit the Google credentials JSON file to version control
   - Add `storage/app/google-credentials.json` to `.gitignore`
   - Use environment variables for sensitive configuration

2. **OAuth Tokens**:
   - Tokens are stored in `storage/app/google_token.json`
   - Ensure this file has proper permissions
   - Consider encrypting stored tokens for production

3. **API Quotas**:
   - Monitor Google Calendar API usage
   - Implement rate limiting if needed
   - Consider caching strategies for high-volume usage

## API Limits

Google Calendar API has the following limits:
- 1,000,000 requests per day
- 100 requests per 100 seconds per user
- 1,000 requests per 100 seconds

For most educational institutions, these limits should be sufficient.

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review Laravel logs for error details
3. Verify Google Cloud Console configuration
4. Test with a simple calendar event creation

## Additional Features

The Google Meet integration supports:
- Updating existing calendar events
- Deleting calendar events when schedules are cancelled
- Fallback to simple Meet links if API fails
- Multiple meeting platforms (Google Meet, Zoom, Teams, Manual)
