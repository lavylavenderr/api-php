<?php
use Symfony\Component\HttpClient\HttpClient;

class WeatherRouter extends BaseRouter
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::Create();
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            $this->respondWithJson(
                json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ])
            );
        }
    }

    public function metar()
    {
        $params = $this->getQueryStringParams() ?: [];
        $icao = @$params["icao"] ?: "";

        if (empty($icao)) {
            $this->respondWithJson(json_encode(array("success" => false, "message" => "Invalid ICAO")), 200);
        }

        $response = $this->httpClient->request("GET", "https://avwx.rest/api/metar/{$icao}", [
            'headers' => [
                'Authorization' => $_ENV["AVWX_API"]    
            ]    
        ]);
        $responseData = json_decode($response->getContent(), true);
        return $this->respondWithJson(json_encode($responseData), 200);
    }

    public function taf()
    {
        $params = $this->getQueryStringParams() ?: [];
        $icao = @$params["icao"] ?: "";

        if (empty($icao)) {
            $this->respondWithJson(json_encode(array("success" => false, "message" => "Invalid ICAO")), 200);
        }

        $response = $this->httpClient->request("GET", "https://avwx.rest/api/taf/{$icao}", [
            'headers' => [
                'Authorization' => $_ENV["AVWX_API"]    
            ]    
        ]);
        $responseData = json_decode($response->getContent(), true);
        return $this->respondWithJson(json_encode($responseData), 200);
    }
}

?>