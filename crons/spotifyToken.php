<?php
// Load Composer Packages & .env

require dirname(__FILE__) . "/../" . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__) . "/../");

$dotenv->safeLoad();

// Actually do what I need it to

use Predis\Client;

$redis = new Client("tcp://" . $_ENV["REDIS_URL"]);

// Form the Base64 for the Authorization Header

$clientId = $_ENV["SPOTIFY_CLIENT_ID"];
$clientSecret = $_ENV["SPOTIFY_CLIENT_SECRET"];
$credentials = $clientId . ':' . $clientSecret;
$encodedCredentials = base64_encode($credentials);

// Get POST Data Together

$url = "https://accounts.spotify.com/api/token";

$data = [
    "refresh_token" => $redis->get("refreshToken"),
    "grant_type" => "refresh_token"
];

$fields = http_build_query($data);

$headers = [
    'Authorization: Basic ' . $encodedCredentials,
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
];

// Initialize cURL, pass settings and  data, then execute

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// Decode JSON and set it in Redis

$json = json_decode($response, true);

$redis->set("accessToken", @$json["access_token"] ?: "");
?>