<?php
use Symfony\Component\HttpClient\HttpClient;

class PhoneRouter extends BaseRouter
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::Create();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->respondWithJson(
                json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ]), 405
            );
        }
    }

    public function alarm()
    {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (empty($requestData)) return $this->respondWithJson(json_encode(['success' => false, 'message' => 'missing time']), 400);

        $webhookUrl = $_ENV["PHONE_WEBHOOK"];
        $this->httpClient->request("POST", $webhookUrl, [
            'json' => [
                    "username" => "Alexander's iPhone",
                    "embeds" => [
                        [
                            "description" => "<@{$_ENV["DISCORD_ID"]}>'s **{$requestData["time"]}** alarm has gone off.",
                            "type" => "rich",
                            "color" => hexdec("967bb6")    
                        ]    
                ]
            ]
        ]);

        return $this->respondWithJson(json_encode(["success" => true, "message" => "OK"]), 200);
    }

    public function chargingStart()
    {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (empty($requestData)) return $this->respondWithJson(json_encode(['success' => false, 'message' => 'Missing Percentage']), 400);

        $webhookUrl = $_ENV["PHONE_WEBHOOK"];
        $this->httpClient->request("POST", $webhookUrl, [
            'json' => [
                    "username" => "Alexander's iPhone",
                    "embeds" => [
                        [
                            "description" => "<@{$_ENV["DISCORD_ID"]}> has started charging their phone. Current charge is **{$requestData["percentage"]}%**",
                            "type" => "rich",
                            "color" => hexdec("967bb6")    
                        ]    
                ]
            ]
        ]);

        return $this->respondWithJson(json_encode(["success" => true, "message" => "OK"]), 200);
    }

    public function chargingStop()
    {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (empty($requestData)) return $this->respondWithJson(json_encode(['success' => false, 'message' => 'Missing Percentage']), 400);

        $webhookUrl = $_ENV["PHONE_WEBHOOK"];
        $this->httpClient->request("POST", $webhookUrl, [
            'json' => [
                    "username" => "Alexander's iPhone",
                    "embeds" => [
                        [
                            "description" => "<@{$_ENV["DISCORD_ID"]}> has unplugged their phone. Current charge is **{$requestData["percentage"]}%**",
                            "type" => "rich",
                            "color" => hexdec("967bb6")    
                        ]    
                ]
            ]
        ]);

        return $this->respondWithJson(json_encode(["success" => true, "message" => "OK"]), 200);
    }

    public function payment()
    {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (empty($requestData)) return $this->respondWithJson(json_encode(['success' => false, 'message' => 'Missing Percentage']), 400);

        $webhookUrl = $_ENV["PHONE_WEBHOOK"];
        $this->httpClient->request("POST", $webhookUrl, [
            'json' => [
                    "embeds" => [
                        [
                            "description" => "<@{$_ENV["DISCORD_ID"]}> has just made a purchase with Apple Pay totalling to **{$requestData["amount"]}**",
                            "type" => "rich",
                            "color" => hexdec("967bb6")    
                        ]    
                ]
            ]
        ]);

        return $this->respondWithJson(json_encode(["success" => true, "message" => "OK"]), 200);
    }
}
