<?php

class BaseRouter
{
    public function __call($name, $arguments)
    {
        $this->respondWithJson(json_encode(["success" => false, "message" => "Not Found"]), 200);
    }

    protected function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);
        return $uri;
    }

    protected function getQueryStringParams()
    {
        parse_str(@$_SERVER['QUERY_STRING'] ?: "", $query);
        return $query;
    }

    static function respondWithJson($data, $statusCode) 
    {
        http_response_code($statusCode);
        header("Content-Type: application/json");
        echo $data;
        exit();
    }
}

?>