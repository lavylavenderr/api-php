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
        $this->redis = new Client("tcp://" . $_ENV["REDIS_URL"]);
        $this->loadTokensFromRedis();

        if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->respondWithJson(
                json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ])
            );
        }
    }

    private function loadTokensFromRedis()
    {
        $this->accessToken = $this->redis->get("accessToken");
        $this->refreshToken = $this->redis->get("refreshToken");
    }

    private function refreshTokens($credentials)
    {
        $url = "https://accounts.spotify.com/api/token";
        $response = $this->httpClient->request("POST", $url, [
            'query' => [
                'refresh_token' => $this->redis->get("refreshToken"),
                'grant_type' => 'refresh_token'
            ],
            'headers' => [
                'Authorization' => "Basic ". $credentials    
            ],    
        ]);
        $responseData = json_decode($response->getContent(), true);

        $this->redis->set("accessToken", @$responseData["access_token"] ?: "");
        $this->redis->set("refreshToken", @$responseData["refresh_token"] ?: "");
        $this->loadTokensFromRedis();
    }

    public function login()
    {
        $clientId = $_ENV["SPOTIFY_CLIENT_ID"];
        $redirectUri = $_ENV["REDIRECT_URL"];

        $scopes = "user-top-read";

        $authUrl = "https://accounts.spotify.com/authorize" . "?" . http_build_query([
            "client_id" => $clientId,
            "response_type" => "code",
            "redirect_uri" => $redirectUri,
            "scope" => $scopes,
        ]);

        header("Location: " . $authUrl);
        exit();
    }

    public function toptracks()
    {
        if (!$this->redis->exists("accessToken")) {
            $this->respondWithJson(json_encode(array("success" => false, "message" => "No Token")), 200);
        }

        $url = "https://api.spotify.com/v1/me/top/tracks?limit=10&time_range=short_term";
        $clientId = $_ENV["SPOTIFY_CLIENT_ID"];
        $clientSecret = $_ENV["SPOTIFY_CLIENT_SECRET"];

        $credentials = $clientId . ':' . $clientSecret;
        $encodedCredentials = base64_encode($credentials);

        $response = $this->httpClient->request("GET", $url, ['auth_bearer' => $this->accessToken]);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 401) {
            $this->refreshTokens($encodedCredentials);
            $response = $this->httpClient->request("GET", $url, ['auth_bearer' => $this->accessToken]);
        }

        $responseData = json_decode($response->getContent(), true);
        $filteredTracks = [];

        foreach ($responseData['items'] as $item) {
            $artistName = $item['artists'][0]['name'];
            $trackName = $item['name'];
            $albumCovers = $item['album']['images'];

            $filteredTracks[] = [
                'artist' => $artistName,
                'track' => $trackName,
                'album_covers' => $albumCovers
            ];
        }

        $this->respondWithJson(json_encode(["success" => true, "data" => $filteredTracks]), 200);
    }

    public function callback()
    {
        $params = $this->getQueryStringParams() ?: [];
        $code = @$params["code"] ?: "";

        if (empty($code)) {
            $this->respondWithJson(json_encode(array("success" => false, "message" => "Invalid Code")), 200);
        }

        $clientId = $_ENV["SPOTIFY_CLIENT_ID"];
        $clientSecret = $_ENV["SPOTIFY_CLIENT_SECRET"];

        $url = "https://accounts.spotify.com/api/token";
        $credentials = $clientId . ':' . $clientSecret;
        $encodedCredentials = base64_encode($credentials);

        $response = $this->httpClient->request("POST", $url, [
            'headers' => [
                'Authorization' => "Basic ". $encodedCredentials    
            ],
            'query' => [
                'grant_type' => 'authorization_code',
                'redirect_uri' => $_ENV["REDIRECT_URL"],
                'code' => $code
            ]
        ]);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            return $this->respondWithJson(json_encode(["success" => false, "message" => "Invalid response from token server"]), 200);
        }

        $responseData = json_decode($response->getContent(), true);
        $this->accessToken = @$responseData["access_token"] ?: "";
        $this->refreshToken = @$responseData["refresh_token"] ?: "";

        if (empty($this->accessToken) || empty($this->refreshToken)) {
            $this->respondWithJson(json_encode(array("success" => false, "message" => "Invalid response from token server")), 400);
        }

        // Store the tokens in Redis
        $this->redis->set("accessToken", $this->accessToken);
        $this->redis->set("refreshToken", $this->refreshToken);

        $this->respondWithJson(json_encode(array("success" => true, "message" => "OK")), 200);
    }
}
