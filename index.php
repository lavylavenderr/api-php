<?php
require dirname(__FILE__) . "/inc/bootstrap.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = explode("/", $uri);

if ($uri[1] == "") {
    $msg = json_encode([
        "success" => true,
        "message" => "Welcome to Alexander's API! Take a look around if you'd like :D"
    ]);

    http_response_code(200);
    header("Content-Type: application/json");
    echo $msg;
    exit();
}

$routes = [
    'misc' => 'MiscController',
    'util' => 'UtilController'
];

if (isset($routes[$uri[1]])) {
    $controllerClass = $routes[$uri[1]];
    $controllerFile = "{$controllerClass}.php";
    require PROJECT_ROOT_PATH . "controllers/$controllerFile";

    $controllerObject = new $controllerClass();

    if (isset($uri[2]) && method_exists($controllerObject, $uri[2] . 'Action')) {
        $methodName = $uri[2] . 'Action';
        $controllerObject->$methodName();
    } else {
        $msg = json_encode([
            "success" => false,
            "message" => "Invalid endpoint"
        ]);

        http_response_code(404);
        header("Content-Type: application/json");
        echo $msg;
        exit();
    }
} else {
    $msg = json_encode([
        "success" => false,
        "message" => "Not Found"
    ]);

    http_response_code(404);
    header("Content-Type: application/json");
    echo $msg;
    exit();
}
?>
