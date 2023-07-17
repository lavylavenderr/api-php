<?php
use Predis\Client;

class UtilController extends BaseController
{
    private $redis;
    private $accessToken;
    private $refreshToken;

    public function __construct()
    {
        $this->redis = new Client("tcp://" . $_ENV["REDIS_URL"]);

        $this->loadTokensFromRedis();

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            $this->sendOutput(
                json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ]),
                ['Content-Type: application/json', 'HTTP/1.1 405 Method Not Allowed']
            );
        }
    }

    private function loadTokensFromRedis()
    {
        $this->accessToken = $this->redis->get("accessToken");
        $this->refreshToken = $this->redis->get("refreshToken");
    }

    public function spotifyLoginAction()
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

    public function toptracksAction()
    {
        if (!$this->redis->exists("accessToken")) {
            $this->sendOutput(
                json_encode(array("success" => false, "message" => "No Token")),
                ["Content-Type: application/json", "HTTP/1.1 200 OK"]
            );
        }

        $url = "https://api.spotify.com/v1/me/top/tracks?limit=10";

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $this->sendOutput(
            $response,
            ["Content-Type: application/json", "HTTP/1.1 200 OK"]
        );
    }

    public function callbackAction()
    {
        $params = $this->getQueryStringParams() ?: [];
        $code = @$params["code"] ?: "";

        if (empty($code)) {
            $this->sendOutput(
                json_encode(array("success" => false, "message" => "Invalid Code")),
                ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
            );
        }

        $clientId = $_ENV["SPOTIFY_CLIENT_ID"];
        $clientSecret = $_ENV["SPOTIFY_CLIENT_SECRET"];

        $url = "https://accounts.spotify.com/api/token";
        $credentials = $clientId . ':' . $clientSecret;
        $encodedCredentials = base64_encode($credentials);

        $data = [
            "code" => $code,
            "redirect_uri" => $_ENV["REDIRECT_URL"],
            "grant_type" => "authorization_code"
        ];

        $fields = http_build_query($data);

        $headers = [
            'Authorization: Basic ' . $encodedCredentials,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        $json = json_decode($response, true);

        $this->accessToken = @$json["access_token"] ?: "";
        $this->refreshToken = @$json["refresh_token"] ?: "";

        if (empty($this->accessToken) || empty($this->refreshToken)) {
            $this->sendOutput(
                json_encode(array("success" => false, "message" => "Invalid response from token server")),
                ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
            );
        }

        // Store the tokens in Redis
        $this->redis->set("accessToken", $this->accessToken);
        $this->redis->set("refreshToken", $this->refreshToken);

        $this->sendOutput(
            json_encode(array("success" => true, "message" => "OK")),
            ["Content-Type: application/json", "HTTP/1.1 200 OK"]
        );
    }

    public function mapAction()
    {
        $params = $this->getQueryStringParams() ?: [];

        $type = @$params["type"] ?: "dark";
        $size = @$params["size"] ?: "1200,800";
        $center = @$params["center"] ?: "Oceanside, CA";

        $url = "https://www.mapquestapi.com/staticmap/v5/map" . "?" . http_build_query([
            "center" => $center,
            "size" => $size,
            "type" => $type,
            "key" => $_ENV["MAPQUEST"]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $this->sendOutput(
                json_encode([
                    'success' => false,
                    'message' => $error
                ]),
                ['Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error']
            );
            curl_close($ch);
            return;
        }

        curl_close($ch);

        $base64 = base64_encode($response);
        $imageData = base64_decode($base64);

        $this->sendOutput(
            $imageData,
            ["Content-Type: image/png", "HTTP/1.1 200 OK"]
        );
    }
}