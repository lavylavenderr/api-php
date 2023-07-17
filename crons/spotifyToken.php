<?php
// Load Composer Packages & .env

require dirname(__FILE__) . "/../" . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__) . "/../");

$dotenv->safeLoad();

// Actually do what I need it to

use Predis\Client;

$redis = new Client("tcp://" . $_ENV["REDIS_URL"]);

$url = "https://accounts.spotify.com/api/token";

$headers = [
    'Authorization: Basic ' . $redis->get("refreshToken"),
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

$json = json_decode($response, true);

// Store the tokens in Redis
$redis->set("accessToken", @$json["access_token"] ?: "");
$redis->set("refreshToken", @$json["refresh_token"] ?: "");
?>