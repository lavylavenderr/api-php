<?php
class UtilController extends BaseController
{
    public function __construct()
    {
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
            "key" => MAPQUEST
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