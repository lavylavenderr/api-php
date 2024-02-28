<?php
// Import everything, set memory & response function
require dirname(__FILE__) . "/inc/bootstrap.php";

// Parse the request URI
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = explode("/", $uri);

// Index route
if ($uri[1] === "") {
    $msg = json_encode([
        "success" => true,
        "message" => "Welcome to Alexander's API! Take a look around if you'd like."
    ]);
    
    BaseRouter::respondWithJson($msg, 200);
}

// Fastify Equivalent of Importing Routers
$routes = [
    'misc',
    'spotify',
    'badge',
    'weather'
];

// Check if the endpoint exists in the routes
if (in_array($uri[1], $routes)) {
    $router = ucfirst($uri[1]) . "Router";
    $routerFile = "{$uri[1]}.php";
    
    require_once PROJECT_ROOT_PATH . "routers/{$routerFile}";

    $routerObject = new $router();

    // Check if the method exists in the controller
    if (isset($uri[2]) && method_exists($routerObject, $uri[2])) {
        $methodName = $uri[2];
        $routerObject->$methodName();
    } else {
        BaseRouter::respondWithJson(json_encode(["success" => false, "message" => "Invalid endpoint"]), 404);
    }
} else {
    BaseRouter::respondWithJson(json_encode(["success" => false, "message" => "Not Found"]), 404);
}
?>
