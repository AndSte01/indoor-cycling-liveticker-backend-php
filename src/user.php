<?php

/**
 * Add, Update and get competitions via the internet
 * 
 * The script to add, update and get competitions from the web.
 * 
 * @package IO
 */

// set namespace
namespace IO\user;

// define aliases
use db\user;
use errors;
use managerAuthentication;
use managerUser;
use function db\connect;
use function db\utils\authenticationErrorsToString;
use function db\utils\parseVerifyUser;
use function db\utils\userErrorsToString;

// Error logging
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// import required files
require_once("db/managers/db_managers_authentication.php");
require_once("db/managers/db_managers_user.php");
require_once("db/adapter/db_adapter_generic.php");
require_once("db/utils/db_utils_authentication.php");
require_once("db/utils/db_utils_user.php");

// realm for authentication
$realm = "global";

// get params
$param_method = $_GET["method"];

// get json from body
$json = file_get_contents('php://input'); // empty if GET

// set correct content type
header("Content-Type: application/json");

// check what the client wants to do

// check if method has valid options
if (!in_array($param_method, [null, "add", "edit", "remove", "logout"]))
    die(errors::to_error_string([errors::PARAM_OUT_OF_RANGE]));

// connect to the database
$db = connect();

// if the client wants to add a user no authentication is required
if ($param_method == "add") {
    // try to parse json
    $user = parseVerifyUser($json);

    // check if any error string was returned, and die with the error string
    if (is_string($user))
        die($user);

    // now we know, the user was parsed successfully and $user is of type user

    // create user manager
    $user_manager = new managerUser($db);

    // try to add user to the database
    $error = $user_manager->add($user);

    //check for any errors
    if ($error != 0)
        die(userErrorsToString($error));

    // if no errors happened die with success message
    die(errors::to_error_string([errors::SUCCESS]));
}

// all other actions require authentication

// create user and authentication manager
$user_manager = new managerUser($db);
$authentication_manager = new managerAuthentication($user_manager, $realm);

// if the user wants to logout do it before initiating new login routine
if ($param_method == "logout")
    $authentication_manager->logout();

// initiated login routine
$result = $authentication_manager->initiateLoginRoutine();

// check if login was successful, else die with error as string
if ($result != 0)
    die(authenticationErrorsToString($result));

// decide how to proceed
switch ($param_method) {
    case null:
        // if method is null, a successful authentication is all we are looking for
        die(errors::to_error_string([errors::SUCCESS]));

    case "remove":
        // get the currently logged in user and remove it
        $errors = $user_manager->remove($authentication_manager->getCurrentUser());

        // some error handling
        if ($errors != 0)
            die(userErrorsToString($errors));

        // if everything went right return success error
        die(errors::to_error_string([errors::SUCCESS]));

    case "edit":
        // try to parse json
        $user = parseVerifyUser($json);

        // check if any error string was returned, and die with the error string
        if (is_string($user))
            die($user);

        // now we know, the user was parsed successfully and $user is of type user
        $errors = $user_manager->edit($authentication_manager->getCurrentUser(), $user);

        //check for any errors
        if ($error != 0)
            die(userErrorsToString($error));

        // if no errors happened die with success message
        die(errors::to_error_string([errors::SUCCESS]));
}

// unnecessary but safe is safe
exit();
