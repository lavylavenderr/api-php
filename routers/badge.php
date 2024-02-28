<?php
use Symfony\Component\HttpClient\HttpClient;

class BadgeRouter extends BaseRouter
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::Create();

        if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->respondWithJson(json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ]), 200);
        }
    }

    public function spotify()
    {
        $response = $this->httpClient->request("GET", "https://api.lanyard.rest/v1/users/" . $_ENV["DISCORD_ID"]);
        $responseData = json_decode($response->getContent(), true);

        if (!$responseData['data']['listening_to_spotify']) {
            $data = json_encode([
                'schemaVersion' => 1,
                'namedLogo' => 'spotify',
                'logoColor' => 'white',
                'color' => '1db954',
                'label' => 'listening to',
                'message' => 'nothin :3'
            ]);

            return $this->respondWithJson($data, 200);
        }

        $songName = $responseData['data']['spotify']['song'];
        $songArtist = $responseData['data']['spotify']['artist'];

        $data = json_encode([
            'schemaVersion' => 1,
            'namedLogo' => 'spotify',
            'logoColor' => 'white',
            'color' => '1db954',
            'label' => 'listening to',
            'message' => $songName . ' by ' . $songArtist
        ]);

        return $this->respondWithJson($data, 200);
    }

    public function playing()
    {
        $response = $this->httpClient->request("GET", "https://api.lanyard.rest/v1/users/" . $_ENV["DISCORD_ID"]);
        $responseData = json_decode($response->getContent(), true);

        $activityArray = $responseData['data']['activities'];
        $filteredActivity = null;
        
        foreach ($activityArray as $activity) {
            if ($activity['type'] == 0 && $activity['application_id'] !== '782685898163617802') {
                $filteredActivity = $activity;
                break;
            }
        }

        $activityName = ($filteredActivity) ? $filteredActivity['name'] : 'nothing :3';
        $data = json_encode([
            'schemaVersion' => 1,
            'color' => '5865F2',
            'label' => 'playing',
            'message' => $activityName
        ]);

        return $this->respondWithJson($data, 200);
    }

    public function status()
    {
        $response = $this->httpClient->request("GET", "https://api.lanyard.rest/v1/users/" . $_ENV["DISCORD_ID"]);
        $responseData = json_decode($response->getContent(), true);

        $color;

        switch ($responseData['data']['discord_status']) {
            case 'online':
                $color = 'green';
            break;
            case 'idle':
                $color = 'yellow';
            break;
            case 'dnd':
                $color = 'red';
            break;
            default:
                $color = 'lightgrey';
            break;
        }

        $data = json_encode([
            "schemaVersion" => 1,
            "color" => $color,
            "label" => "currently",
            "message" => $responseData['data']['discord_status']
        ]);

        return $this->respondWithJson($data, 200);
    }
}