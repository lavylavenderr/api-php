<?php

use Predis\Client;
use Symfony\Component\HttpClient\HttpClient;

class SpotifyRouter extends BaseRouter
{
    private $redis;
    private $accessToken;
    private $refreshToken;
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::Create();
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => $_ENV["REDIS_HOST"],
            'port' => $_ENV["REDIS_PORT"],
            'username' => $_ENV["REDIS_USER"],
            'password' => $_ENV["REDIS_PASSWORD"]
        ]);
        $this->loadTokensFromRedis();

        if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->respondWithJson(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
        }
    }

    private function loadTokensFromRedis()
    {
        $this->accessToken = $this->redis->get("accessToken");
        $this->refreshToken = $this->redis->get("refreshToken");
    }

    private function refreshTokens()
    {
        $credentials = \Delight\Base64\Base64::encode($_ENV["SPOTIFY_CLIENT_ID"] . ':' . $_ENV["SPOTIFY_CLIENT_SECRET"]);
        $url = "https://accounts.spotify.com/api/token";
        $response = $this->httpClient->request("POST", $url, [
            'query' => [
                'refresh_token' => $this->redis->get("refreshToken"),
                'grant_type' => 'refresh_token'
            ],
            'headers' => ['Authorization' => "Basic " . $credentials],
        ]);
        $responseData = json_decode($response->getContent(), true);
        $this->redis->set("accessToken", $responseData["access_token"] ?? "");
        $this->loadTokensFromRedis();
    }

    public function login()
    {
        $authUrl = "https://accounts.spotify.com/authorize?" . http_build_query([
            "client_id" => $_ENV["SPOTIFY_CLIENT_ID"],
            "response_type" => "code",
            "redirect_uri" => $_ENV["REDIRECT_URL"],
            "scope" => "user-top-read",
        ]);
        header("Location: " . $authUrl);
        exit();
    }

    public function search()
    {
        if (!$this->redis->exists("accessToken")) {
            if (!$this->redis->exists("refreshToken")) {
                $this->respondWithJson(json_encode(["success" => false, "message" => "No Token"]));
            } else {
                $this->refreshTokens();
            }
        }

        $query = $this->getQueryStringParams()["query"] ?? "";
        if (empty($query)) {
            return $this->respondWithJson(json_encode(['success' => false, 'message' => 'Missing Query']), 400);
        }

        $queryURL = "https://api.spotify.com/v1/search?q=" . urlencode($query) . "&type=track&limit=10";
        $response = $this->httpClient->request("GET", $queryURL, ['auth_bearer' => $this->accessToken]);

        if ($response->getStatusCode() === 401) {
            $this->refreshTokens();
            $response = $this->httpClient->request("GET", $queryURL, ['auth_bearer' => $this->accessToken]);
        }

        $filteredTracks = array_map(fn($item) => [
            'artist' => $item['artists'][0]['name'],
            'track' => $item['name'],
            'album_covers' => $item['album']['images'],
            'spotifyId' => $item['id']
        ], json_decode($response->getContent(), true)['tracks']['items']);

        $this->respondWithJson(json_encode(["success" => true, "data" => $filteredTracks]), 200);
    }

    public function toptracks()
    {
        if ($this->redis->exists("topSongs")) {
            return $this->respondWithJson(json_encode(["success" => true, "data" => json_decode($this->redis->get("topSongs"))]), 200);
        }

        if (!$this->redis->exists("accessToken")) {
            if (!$this->redis->exists("refreshToken")) {
                $this->respondWithJson(json_encode(["success" => false, "message" => "No Token"]));
            } else {
                $this->refreshTokens();
            }
        }

        $url = "https://api.spotify.com/v1/me/top/tracks?limit=10&time_range=short_term";
        $response = $this->httpClient->request("GET", $url, ['auth_bearer' => $this->accessToken]);

        if ($response->getStatusCode() === 401) {
            $this->refreshTokens();
            $response = $this->httpClient->request("GET", $url, ['auth_bearer' => $this->accessToken]);
        }

        $filteredTracks = array_map(fn($item) => [
            'artist' => $item['artists'][0]['name'],
            'track' => $item['name'],
            'album_covers' => $item['album']['images']
        ], json_decode($response->getContent(), true)['items']);

        $this->redis->set("topSongs", json_encode($filteredTracks), 'EX', 5 * 60 * 60);
        $this->respondWithJson(json_encode(["success" => true, "data" => $filteredTracks]), 200);
    }

    public function callback()
    {
        $code = $this->getQueryStringParams()["code"] ?? "";

        if (empty($code)) {
            $this->respondWithJson(json_encode(["success" => false, "message" => "Invalid Code"]));
        }

        $credentials = \Delight\Base64\Base64::encode($_ENV["SPOTIFY_CLIENT_ID"] . ':' . $_ENV["SPOTIFY_CLIENT_SECRET"]);
        $response = $this->httpClient->request("POST", "https://accounts.spotify.com/api/token", [
            'headers' => ['Authorization' => "Basic " . $credentials],
            'query' => [
                'grant_type' => 'authorization_code',
                'redirect_uri' => $_ENV["REDIRECT_URL"],
                'code' => $code
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->respondWithJson(json_encode(["success" => false, "message" => "Invalid response from token server"]));
        }

        $responseData = json_decode($response->getContent(), true);
        $this->accessToken = $responseData["access_token"] ?? "";
        $this->refreshToken = $responseData["refresh_token"] ?? "";

        if (empty($this->accessToken) || empty($this->refreshToken)) {
            $this->respondWithJson(json_encode(["success" => false, "message" => "Invalid response from token server"]));
        }

        $this->redis->set("accessToken", $this->accessToken);
        $this->redis->set("refreshToken", $this->refreshToken);
        $this->respondWithJson(json_encode(["success" => true, "message" => "OK"]), 200);
    }
}
