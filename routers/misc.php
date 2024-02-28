<?php
class MiscController extends BaseController
{
    public function __construct()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET" || $_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->sendOutput(
                json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ]),
                ['Content-Type: application/json', 'HTTP/1.1 405 Method Not Allowed']
            );
        }
    }

    public function time()
    {
        $timezone = new DateTimeZone("America/Los_Angeles");
        $date = new DateTime('now', $timezone);

        $dateString = $date->format('m-d-Y');
        $timeString = $date->format('H:i:s');

        $this->sendOutput(
            json_encode([
                'success' => true,
                'data' => [
                    'date' => $dateString,
                    'time' => $timeString
                ]
            ]),
            ['Content-Type: application/json', 'HTTP/1.1 200 OK']
        );
    }
}

?>