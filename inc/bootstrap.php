<?php

/* Root Dir so I don't gotta type that shit out, then load Composer Dependencies and API Controller  */

define("PROJECT_ROOT_PATH", dirname(__FILE__) . "/../");

require PROJECT_ROOT_PATH . "/vendor/autoload.php";
require_once PROJECT_ROOT_PATH . "/routers/base.php";

/* .env Shit */

$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();

?>
