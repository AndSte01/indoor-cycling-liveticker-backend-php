<?php

/**
 * Add, edit, remove and get disciplines via the internet
 * 
 * The script to add, edit, remove and get disciplines from the web.
 * 
 * @package IO
 */

// set namespace
namespace IO\discipline;

// define aliases
use DateTime;
use db\discipline;
use db\user;
use errors;
use managerAuthentication;
use managerCompetition;
use managerDiscipline;
use managerUser;
use mysqli;
use db\adapterGeneric;
use function db\utils\authenticationErrorsToString;
use function db\utils\competitionErrorsToString;

// Error logging
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// import required files
require_once("db/managers/db_managers_authentication.php");
require_once("db/managers/db_managers_user.php");
require_once("db/managers/db_managers_discipline.php");
require_once("db/managers/db_managers_competition.php");
require_once("db/utils/db_utils_competition.php");
require_once("db/utils/db_utils_authentication.php");

// realm for authentication
$realm = "global";

// get params
$param_method = $_GET["method"];
$param_competition_id = $_GET["competition"];
$param_timestamp = $_GET["timestamp"];

// get json from body
$json = file_get_contents('php://input'); // empty if GET

// set correct content type
header("Content-Type: application/json");


// check if competition id is (correctly) provided
if (filter_var($param_competition_id, FILTER_VALIDATE_INT) !== false) {
    // if competition_id is a number convert it to an int
    $param_competition_id = intval($param_competition_id);

    // check if competition id is in rage
    if ($param_competition_id < 1)
        die(errors::to_error_string([errors::PARAM_OUT_OF_RANGE], true));
} else {
    // if competition_id is neither null nor a number return error
    die(errors::to_error_string([errors::MISSING_INFORMATION], true));
}


// check what the client wants to do

// if it is null the client wants to get disciplines
if ($param_method == null)
    die(getDisciplines($param_competition_id, $param_timestamp));

// $param_method is at this point obviously not null (so check if it contains an correct keyword)
// checked to prevent unnecessary code execution
if (!in_array($param_method, ["add", "edit", "remove"]))
    die(errors::to_error_string([errors::PARAM_OUT_OF_RANGE], true));


// all available methods require authentication and a user management so initiate that

// connect to database
$db = adapterGeneric::connect();

// create user manager and authentication manager
$user_manager = new managerUser($db);
$authentication_manager = new managerAuthentication($user_manager, $realm);

// initiated login routine
$result = $authentication_manager->initiateLoginRoutine();

// check if login was successful, else die with error as string
if ($result != 0)
    die(authenticationErrorsToString($result));

// verify if user has access to desired competition
$competition_manager = new managerCompetition($db);
$competition_manager->setCurrentUserId($authentication_manager->getCurrentUser()->{user::KEY_ID});

$errors = $competition_manager->userHasAccess($param_competition_id);
if ($errors != 0)
    die(competitionErrorsToString($errors, true));

// decide what the user want's todo
switch ($param_method) {
    case "add":
        die(parseVerifyModifyDiscipline($json, 0, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));

    case "edit":
        die(parseVerifyModifyDiscipline($json, 1, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));

    case "remove":
        die(parseVerifyModifyDiscipline($json, 2, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));
        break;

    default:
        exit();
}

// unnecessary but safe is safe
exit();


// --- functions used above ---

/**
 * Gets disciplines from database
 * 
 * @param string $competition_id The id of the competition of whom disciplines shall be returned
 * @param string $timestamp only get disciplines that were modified (or added) after this timestamp
 * 
 * @return string String of a JSON array either containing the occurred errors or the competitions
 */
function getDisciplines($competition_id, $timestamp): string
{
    // work with the discipline manager to get the disciplines

    // connect to database
    $db = adapterGeneric::connect();

    // create discipline manager
    $discipline_manager = new managerDiscipline($db, $competition_id);

    // get current timestamp from database (it is important to get it before asking for disciplines)
    $new_timestamp =  adapterGeneric::getCurrentTime($db)->getTimestamp();

    // decide what to do
    switch (true) {
        case ($timestamp == ""): // if $timestamp is not set make it null
            $result = $discipline_manager->getDiscipline();
            break;

        case (filter_var($timestamp, FILTER_VALIDATE_INT) !== false): // if timestamp is a number try to convert it to unix time
            // create new datetime, convert timestamp to int and apply timestamp to DateTime object
            // get disciplines with the timestamp
            $result = $discipline_manager->getDiscipline((new DateTime())->setTimestamp(intval($timestamp)));
            break;

        default: // if timestamp is neither null nor a number return error
            return errors::to_error_string([errors::NaN], true);
    }

    // merge new timestamp and result into array and return ist as json
    return json_encode(array_merge([$new_timestamp], $result), JSON_UNESCAPED_UNICODE);
}

/**
 * Adds, edits or removes a discipline to/from database
 * 
 * @param string $json JSON representation of the discipline to work with
 * @param int $action 0 = try to add the discipline, 1 = try to edit the given discipline, 2 = remove discipline
 * @param int $competitionId The competition id to work with
 * @param mysqli $db The database to work with
 * 
 * @return string String ready for sending to client
 */
function parseVerifyModifyDiscipline(string $json, int $action, int $competitionId, mysqli $db): string
{
    // decode json to assoc array
    $decoded = json_decode($json, true);

    // check if decode was possible
    if ($decoded == null)
        return errors::to_error_string([errors::INVALID_JSON]);

    // create empty discipline and parse data
    $discipline = new discipline();
    $discipline->parse(
        $decoded[discipline::KEY_ID],
        "", // no timestamp required
        "", // no competition id required
        $decoded[discipline::KEY_TYPE],
        $decoded[discipline::KEY_FALLBACK_NAME],
        $decoded[discipline::KEY_ROUND],
        $decoded[discipline::KEY_FINISHED]
    );

    // use discipline manager to complete task
    $discipline_manager = new managerDiscipline($db, $competitionId);

    // either add, edit or remove discipline
    switch ($action) {
        case 0: // add
            // try to add discipline to database
            $result = $discipline_manager->add($discipline);

            // do error handling
            if (is_int($result))
                return competitionErrorsToString($result);

            // if no error ocurred return discipline (in $result) as json
            return json_encode($result, JSON_UNESCAPED_UNICODE);

        case 1: // edit
            $result = $discipline_manager->edit($discipline);
            return competitionErrorsToString($result);

        case 2: // remove
            $result = $discipline_manager->remove($discipline);
            return competitionErrorsToString($result);
    }
}
