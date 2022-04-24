<?php

// Error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// import required files
require_once(dirname(__FILE__) . "/../../../db/adapter/db_adapter_generic.php");
require_once(dirname(__FILE__) . "/../../../db/managers/db_managers_user.php");
require_once(dirname(__FILE__) . "/../../../db/managers/db_managers_authentication.php");
require_once(dirname(__FILE__) . "/../../../errors.php");

// define aliases
use function db\connect;

// realm used for login
$realm = "test";

// connect to database
$db = connect();

// create providers
$userProvider = new managerUser($db);

// create user manager
$manager = new managerAuthentication($userProvider, $realm);

// do a simple login routine
$return = $manager->initiateLoginRoutine();

// leave if login wasn't successful
if ($return != 0) {
    $db->close();
    echo errorsToString($return);
    exit($return);
}

// print a small success message
printf("you are logged in");


function errorsToString(int $errors): string
{
    switch ($errors) {
        case managerAuthenticationInterface::ERROR_DISMISSED_AUTHENTICATION:
            return errors::to_error_string([errors::ACCESS_DENIED]);

        case managerAuthenticationInterface::ERROR_NO_SUCH_USER:
            return errors::to_error_string([errors::NOT_EXISTING]);

        case managerAuthenticationInterface::ERROR_INVALID_PASSWORD:
            return errors::to_error_string([errors::ACCESS_DENIED]);

        case managerAuthenticationInterface::ERROR_INVALID_RESPONSE:
            return errors::to_error_string([errors::INVALID_REQUEST]);

        default:
            return "";
    }
}
