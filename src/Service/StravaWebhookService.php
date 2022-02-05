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
                'callback_url' => 'https://cc.leicesterforest.com/strava/webhook', //this needs fixing
                'verify_token' => 'STRAVA',
            ]
        ]);

        if ($response->getStatusCode() === Response::HTTP_CREATED) {
            return json_decode($response->getContent())->id;
        }

        return null;
    }

    public function unsubscribe(): bool
    {
        $id = $this->view();

        if (!$id) {
            return false;
        }
    
        //Create a new client
        $httpClient = HttpClient::create(['base_uri' => self::API_URL]);
        //Get response
        $response = $httpClient->request('DELETE', "push_subscriptions/$id", [
            'body' => [
                'client_id' => $_ENV['STRAVA_ID'],
                'client_secret' => $_ENV['STRAVA_SECRET'],
            ]
        ]);
    
        if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            return true;
        }
    
        return false;
    }

    public function view(): int|null
    {
        //Create a new client
        $httpClient = HttpClient::create(['base_uri' => self::API_URL]);
        //Get response
        $response = $httpClient->request('GET', 'push_subscriptions', [
            'body' => [
                'client_id' => $_ENV['STRAVA_ID'],
                'client_secret' => $_ENV['STRAVA_SECRET'],
            ]
        ]);
            
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $body = json_decode($response->getContent());
    
            if ($body) {
                return $body[0]->id; // each application can have only 1 subscription
            } else {
                return null; // no subscription found
            }
        }
    
        return null;
    }

    public function validate(string $mode, string $token, string $challenge): Response|JsonResponse
    {
        // Checks if a token and mode is in the query string of the request
        if ($mode && $token) {
            // Verifies that the mode and token sent are valid
            if ($mode === 'subscribe' && $token === 'STRAVA') {
                // Responds with the challenge token from the request
                return new JsonResponse(['hub.challenge' => $challenge], Response::HTTP_OK, []);
            } else {
                // Responds with '403 Forbidden' if verify tokens do not match
                return new Response('', Response::HTTP_FORBIDDEN, []);
            }
        }

        // Responds with '403 Forbidden' if verify tokens do not match
        return new Response('', Response::HTTP_FORBIDDEN, []);
    }
}