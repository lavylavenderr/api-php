<?php

/* Root Dir so I don't gotta type that shit out  */

define("PROJECT_ROOT_PATH", dirname(__FILE__) . "/../");

/* Load Composer Dependencies */

require PROJECT_ROOT_PATH . "/vendor/autoload.php";

/* API Controller */

require_once PROJECT_ROOT_PATH . "/controllers/BaseController.php";

/* .env Shit */

$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT_PATH);

$dotenv->safeLoad();

?>
