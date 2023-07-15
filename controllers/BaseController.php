<?php

class BaseController
{
    public function __call($name, $arguments)
    {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
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

    protected function sendOutput($data, $httpHeaders = [])
    {
        header_remove('Set-Cookie');
        
        foreach ($httpHeaders as $httpHeader) {
            header($httpHeader);
        }
        
        echo $data;
        exit;
    }

}

?>