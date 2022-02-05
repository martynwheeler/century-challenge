<?php
namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class StravaWebhookService
{
    public const API_URL = 'https://www.strava.com/api/v3/';

    public function subscribe(): int|null
    {
        //Create a new client
        $httpClient = HttpClient::create(['base_uri' => self::API_URL]);
        //Get response
        $response = $httpClient->request('POST', 'push_subscriptions', [
            'body' => [
                'client_id' => $_ENV['STRAVA_ID'],
                'client_secret' => $_ENV['STRAVA_SECRET'],
                'callback_url' => 'https://cc.leicesterforest.com/strava/webhook',
                'verify_token' => 'STRAVA',
            ]
        ]);

//        if ($response->status() === Response::HTTP_CREATED) {
//            return json_decode($response->body())->id;
//        }
//        dd($response);
        return null;
    }

    public function unsubscribe(): bool
    {
        //
    }

    public function view(): int|null
    {
        //
    }

    public function validate(string $mode, string $token, string $challenge): Response|JsonResponse
    {
    // Checks if a token and mode is in the query string of the request
    if ($mode && $token) {
        // Verifies that the mode and token sent are valid
        if ($mode === 'subscribe' && $token === $this->verify_token) {
            // Responds with the challenge token from the request
            return response()->json(['hub.challenge' => $challenge]);
        } else {
            // Responds with '403 Forbidden' if verify tokens do not match
            return response('', Response::HTTP_FORBIDDEN);
        }
    }

    return response('', Response::HTTP_FORBIDDEN);
    }
}