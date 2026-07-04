<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
use Carbon\Carbon;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoogleMeetService
{
    private $client;
    private $calendar;
    private ?int $userId;

    public function __construct(?User $user = null)
    {
        $this->userId = $user?->id;
        $this->initializeClient();
    }

    /**
     * Initialize Google Client
     */
    private function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName(config('services.google.application_name', 'PaperTrail MS'));
            $this->client->setScopes([Calendar::CALENDAR]);
            
            $credentialsContent = $this->getCredentialsConfig();
            
            // Check if it's a web application credentials file
            if (!isset($credentialsContent['web']) && !isset($credentialsContent['installed'])) {
                throw new Exception('Google credentials file must be for a web application or installed application. Please download the correct OAuth 2.0 Client ID credentials from Google Cloud Console.');
            }
            
            $this->client->setAuthConfig($credentialsContent);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');
            $this->client->setIncludeGrantedScopes(true);
            
            // Set redirect URI for OAuth flow
            $redirectUri = config('services.google.redirect_uri');
            if ($redirectUri) {
                $this->client->setRedirectUri($redirectUri);
            }

            // Load stored access token
            $this->loadToken();

            $this->calendar = new Calendar($this->client);
        } catch (Exception $e) {
            Log::error('Failed to initialize Google Client: ' . $e->getMessage());
            throw new Exception('Google Meet integration is not properly configured: ' . $e->getMessage());
        }
    }

    /**
     * Create a Google Calendar event with Meet link
     */
    public function createMeetingEvent($title, $description, $startTime, $endTime, $attendees = [])
    {
        try {
            $event = new Event([
                'summary' => $title,
                'description' => $description,
                'start' => new EventDateTime([
                    'dateTime' => Carbon::parse($startTime)->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]),
                'end' => new EventDateTime([
                    'dateTime' => Carbon::parse($endTime)->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]),
                'conferenceData' => new ConferenceData([
                    'createRequest' => new CreateConferenceRequest([
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => new ConferenceSolutionKey([
                            'type' => 'hangoutsMeet'
                        ])
                    ])
                ]),
                'attendees' => $this->formatAttendees($attendees),
            ]);

            $calendarId = config('services.google.calendar_id', 'primary');
            $createdEvent = $this->calendar->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all'
            ]);

            $entryPoints = $createdEvent->getConferenceData()?->getEntryPoints() ?? [];
            $meetLink = collect($entryPoints)
                ->first(fn ($entryPoint) => method_exists($entryPoint, 'getEntryPointType') && $entryPoint->getEntryPointType() === 'video')
                ?->getUri();

            if (! $meetLink) {
                throw new Exception('Google Calendar did not return a usable Google Meet link.');
            }

            return [
                'event_id' => $createdEvent->getId(),
                'meet_link' => $meetLink,
                'calendar_link' => $createdEvent->getHtmlLink(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Google Meet event: ' . $e->getMessage());
            throw new Exception('Failed to create Google Meet event: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Google Calendar event
     */
    public function updateMeetingEvent($eventId, $title, $description, $startTime, $endTime, $attendees = [])
    {
        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $event = $this->calendar->events->get($calendarId, $eventId);

            $event->setSummary($title);
            $event->setDescription($description);
            $event->setStart(new EventDateTime([
                'dateTime' => Carbon::parse($startTime)->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => Carbon::parse($endTime)->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]));
            $event->setAttendees($this->formatAttendees($attendees));

            $updatedEvent = $this->calendar->events->update($calendarId, $eventId, $event, [
                'sendUpdates' => 'all'
            ]);

            $entryPoints = $updatedEvent->getConferenceData()?->getEntryPoints() ?? [];
            $meetLink = collect($entryPoints)
                ->first(fn ($entryPoint) => method_exists($entryPoint, 'getEntryPointType') && $entryPoint->getEntryPointType() === 'video')
                ?->getUri();

            if (! $meetLink) {
                throw new Exception('Google Calendar did not return a usable Google Meet link.');
            }

            return [
                'event_id' => $updatedEvent->getId(),
                'meet_link' => $meetLink,
                'calendar_link' => $updatedEvent->getHtmlLink(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to update Google Meet event: ' . $e->getMessage());
            throw new Exception('Failed to update Google Meet event: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Google Calendar event
     */
    public function deleteMeetingEvent($eventId)
    {
        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $this->calendar->events->delete($calendarId, $eventId, [
                'sendUpdates' => 'all'
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete Google Meet event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format attendees for Google Calendar
     */
    private function formatAttendees($attendees)
    {
        $formattedAttendees = [];
        foreach ($attendees as $attendee) {
            if (is_string($attendee)) {
                $formattedAttendees[] = ['email' => $attendee];
            } elseif (is_array($attendee) && isset($attendee['email'])) {
                $formattedAttendees[] = $attendee;
            }
        }
        return $formattedAttendees;
    }

    /**
     * Check if Google Meet integration is properly configured
     */
    public function isConfigured()
    {
        if (config('services.google.credentials_json')) {
            return true;
        }

        return config('services.google.credentials_path') &&
               file_exists(config('services.google.credentials_path'));
    }

    /**
     * Get authorization URL for OAuth setup
     */
    public function getAuthUrl()
    {
        // Ensure redirect URI is set
        $redirectUri = config('services.google.redirect_uri');
        if (!$redirectUri) {
            throw new Exception('Google redirect URI is not configured. Please set GOOGLE_REDIRECT_URI in your .env file.');
        }
        
        // Force set the redirect URI to ensure it's correct
        $this->client->setRedirectUri($redirectUri);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent select_account');
        
        // Verify what redirect URI the client is actually using
        Log::info('Google OAuth redirect URI from config: ' . $redirectUri);
        Log::info('Google Client redirect URI: ' . $this->client->getRedirectUri());
        
        $authUrl = $this->client->createAuthUrl();
        Log::info('Google OAuth auth URL: ' . $authUrl);
        
        return $authUrl;
    }

    /**
     * Handle OAuth callback and store tokens
     */
    public function handleCallback($code)
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error']);
            }

            $this->storeToken($token);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to handle Google OAuth callback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load stored access token
     */
    private function loadToken()
    {
        $token = $this->getStoredToken();
        if ($token) {
            $this->client->setAccessToken($token);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    $this->storeToken($this->client->getAccessToken());
                }
            }
        } else {
            // Token file doesn't exist - OAuth setup required
            Log::warning('Google OAuth token not found. Please complete the OAuth setup first.');
        }
    }

    /**
     * Check if OAuth token exists and is valid
     */
    public function hasValidToken()
    {
        $token = $this->getStoredToken();
        if (! $token) {
            return false;
        }

        try {
            $this->client->setAccessToken($token);

            if (! $this->client->isAccessTokenExpired()) {
                return true;
            }

            $refreshToken = $this->client->getRefreshToken() ?? ($token['refresh_token'] ?? null);
            if (! $refreshToken) {
                return false;
            }

            $refreshedToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($refreshedToken['error'])) {
                Log::error('Failed to refresh Google OAuth token: ' . $refreshedToken['error']);
                return false;
            }

            if (! isset($refreshedToken['refresh_token'])) {
                $refreshedToken['refresh_token'] = $refreshToken;
            }

            $this->client->setAccessToken($refreshedToken);
            $this->storeToken($refreshedToken);

            return true;
        } catch (Exception $e) {
            Log::error('Invalid Google OAuth token: ' . $e->getMessage());
            return false;
        }
    }

    private function getCredentialsConfig(): array
    {
        $credentialsJson = config('services.google.credentials_json');

        if ($credentialsJson) {
            $decoded = base64_decode($credentialsJson, true);
            $json = $decoded !== false ? $decoded : $credentialsJson;
            $credentials = json_decode($json, true);

            if (! is_array($credentials)) {
                throw new Exception('Invalid GOOGLE_CREDENTIALS_JSON. Paste the full Google OAuth JSON or its base64 value.');
            }

            return $credentials;
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if ($clientId && $clientSecret) {
            return [
                'web' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'redirect_uris' => array_values(array_filter([config('services.google.redirect_uri')])),
                ],
            ];
        }

        $credentialsPath = config('services.google.credentials_path');

        if (! file_exists($credentialsPath)) {
            throw new Exception('Google credentials file not found at: ' . $credentialsPath);
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        if (! is_array($credentials)) {
            throw new Exception('Invalid Google credentials file format. Please ensure it is valid JSON.');
        }

        return $credentials;
    }

    private function getStoredToken(): ?array
    {
        if (Schema::hasTable('google_oauth_tokens')) {
            $query = DB::table('google_oauth_tokens')
                ->where('provider', 'google_calendar');

            if ($this->hasUserScopedTokens()) {
                if (! $this->userId) {
                    return null;
                }

                $query->where('user_id', $this->userId);
            }

            $token = $query->value('token');

            if ($token) {
                $decoded = is_array($token) ? $token : json_decode($token, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        if ($this->userId) {
            return null;
        }

        $tokenPath = storage_path('app/google_token.json');
        if (file_exists($tokenPath)) {
            $decoded = json_decode(file_get_contents($tokenPath), true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function storeToken(array $token): void
    {
        if (Schema::hasTable('google_oauth_tokens')) {
            if ($this->hasUserScopedTokens()) {
                if (! $this->userId) {
                    throw new Exception('A PaperTrail user is required before storing Google authorization.');
                }

                DB::table('google_oauth_tokens')->updateOrInsert(
                    ['user_id' => $this->userId, 'provider' => 'google_calendar'],
                    [
                        'token' => json_encode($token),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                return;
            }

            DB::table('google_oauth_tokens')->updateOrInsert(
                ['provider' => 'google_calendar'],
                [
                    'token' => json_encode($token),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if ($this->userId) {
            return;
        }

        $tokenPath = storage_path('app/google_token.json');
        @file_put_contents($tokenPath, json_encode($token));
    }

    private function hasUserScopedTokens(): bool
    {
        return Schema::hasColumn('google_oauth_tokens', 'user_id');
    }
}
