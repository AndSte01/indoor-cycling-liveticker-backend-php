<?php

/**
 * Add, edit, remove and get competitions via the internet
 * 
 * The script to add, edit, remove and get competitions from the web.
 * 
 * @package IO
 */

// set namespace
namespace IO\competition;

// define aliases
use db\competition;
use db\user;
use errors;
use managerAuthentication;
use managerCompetition;
use managerUser;
use mysqli;
use function db\connect;
use function db\utils\authenticationErrorsToString;
use function db\utils\competitionErrorsToString;

// some constants used later
const GET_COMPETITIONS_LIMIT_DEFAULT = 10;
const GET_COMPETITIONS_LIMIT_MAX = 100;

// Error logging
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// import required files
require_once("db/managers/db_managers_authentication.php");
require_once("db/managers/db_managers_user.php");
require_once("db/managers/db_managers_competition.php");
require_once("db/utils/db_utils_competition.php");
require_once("db/utils/db_utils_authentication.php");

// realm for authentication
$realm = "global";

// get params
$param_method = $_GET["method"];
$param_days = $_GET["days"];
$param_limit = $_GET["limit"];
$param_id = $_GET["id"];

// get json from body
$json = file_get_contents('php://input'); // empty if GET

// set correct content type
header("Content-Type: application/json");

// check what the client wants to do

// if it is null the client wants to get competitions
if ($param_method == null) {
    switch ($param_id) {
        case null:
            die(getCompetitionsGeneric($param_days, $param_limit));
            break;

        default:
            die(getCompetitionsId($param_id));
            break;
    }
}

// $param_method is at this point obviously not null (so check if it contains an correct keyword)
// checked to prevent unnecessary code execution
if (!in_array($param_method, ["add", "edit", "remove"]))
    die(errors::to_error_string([errors::PARAM_OUT_OF_RANGE], true));

// all available methods require authentication and a user management so initiate that

// connect to database
$db = connect();

// create user manager and authentication manager
$user_manager = new managerUser($db);
$authentication_manager = new managerAuthentication($user_manager, $realm);

// initiated login routine
$result = $authentication_manager->initiateLoginRoutine();

// check if login was successful, else die with error as string
if ($result != 0)
    die(authenticationErrorsToString($result));

// decide what the user want's todo
switch ($param_method) {
    case "add":
        die(parseVerifyModifyCompetition($json, 0, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));

    case "edit":
        die(parseVerifyModifyCompetition($json, 1, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));

    case "remove":
        die(parseVerifyModifyCompetition($json, 2, $authentication_manager->getCurrentUser()->{user::KEY_ID}, $db));
        break;

    default:
        exit();
}

// unnecessary but safe is safe
exit();

// --- functions used above ---

/**
 * Gets competition from database
 * 
 * @param string $days how many days back competitions should be displayed
 * @param string $limit how many competitions should be returned (default GET_DEFAULT_COMPETITIONS_LIMIT)
 * 
 * @return string String of a JSON array either containing the occurred errors or the competitions
 */
function getCompetitionsGeneric($daysSinceToday, $limit): string
{
    // check variables, it is very important to correctly interpret null

    // use switch inversion trick
    switch (true) {
        case ($daysSinceToday == ""): // if $day is not set make variable null
            $daysSinceToday = null;
            break;

        case (filter_var($daysSinceToday, FILTER_VALIDATE_INT) !== false): // if date is a number convert it to an int
            $daysSinceToday = abs(intval($daysSinceToday)); // ignore negative values
            break;

        default: // if date is neither null nor a number return error
            return errors::to_error_string([errors::NaN], true);
            break;
    }

    switch (true) {
        case ($limit == ""): // if $limit is not set make variable null
            $limit = GET_COMPETITIONS_LIMIT_DEFAULT; // default limit
            break;

        case (filter_var($limit, FILTER_VALIDATE_INT) !== false): // if limit is a number convert it to an int
            $limit = intval($limit);

            // put limit in maximal range
            $limit = $limit > GET_COMPETITIONS_LIMIT_MAX ? GET_COMPETITIONS_LIMIT_MAX : $limit;
            break;

        default: // if limit is neither null nor a number return error
            return errors::to_error_string([errors::NaN], true);
            break;
    }

    // work with the competition manager to get the competitions

    // connect to database
    $db = connect();

    // create competition manager
    $competition_manager = new managerCompetition($db);

    // try to get competitions
    $result = $competition_manager->getCompetitionsGeneric($daysSinceToday, $limit);

    // return the result
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * Gets a single competition by looking up his id
 * 
 * @param string $id the id of the competition one wants to get
 * 
 * @return string String of an json array either containing the occurred errors or the competitions
 */
function getCompetitionsId($id): string
{
    // validate input variables

    // check if id is an int
    if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
        $id = intval($id);
    } else {
        return errors::to_error_string([errors::NaN], true);
    }

    // check if id is in valid range
    if ($id < 1) {
        return errors::to_error_string([errors::PARAM_OUT_OF_RANGE], true);
    }

    // connect to database
    $db = connect();

    // create competition manager
    $competition_manager = new managerCompetition($db);

    // try to get competitions
    $result = $competition_manager->getCompetitionById($id);

    // return the result
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * Adds, edits or removes a competitions to/from database
 * 
 * @param string $json JSON representation of the competition to work with
 * @param int $action 0 = try to add the competition, 1 = try to edit the given competition, 2 = remove competition
 * @param int $userId The user id to work with
 * @param mysqli $db The database to work with
 * 
 * @return string String ready for sending to client
 */
function parseVerifyModifyCompetition(string $json, int $action, int $userId, mysqli $db): string
{
    // decode json to assoc array
    $decoded = json_decode($json, true);

    // check if decode was possible
    if ($decoded == null)
        return errors::to_error_string([errors::INVALID_JSON], true);

    // create empty competition to parse data to
    $competition = new competition();
    $competition->parse(
        strval($decoded[competition::KEY_ID]),
        strval($decoded[competition::KEY_DATE]),
        strval($decoded[competition::KEY_NAME]),
        strval($decoded[competition::KEY_LOCATION]),
        "", // no user id required all done by authentication manager
        strval($decoded[competition::KEY_AREAS]),
        strval($decoded[competition::KEY_FEATURE_SET]),
        strval($decoded[competition::KEY_LIVE])
    );

    // use competition manager to complete task
    $competition_manager = new managerCompetition($db);

    // set the user id in competition manager
    $competition_manager->setCurrentUserId($userId);

    // either add, edit or remove competition
    switch ($action) {
        case 0: // add
            // try to add competition to database
            $result = $competition_manager->add($competition);

            // do error handling
            if (is_int($result))
                return competitionErrorsToString($result);

            // if no error ocurred return competition (in $result) as json
            return json_encode($result, JSON_UNESCAPED_UNICODE);

        case 1: // edit
            $result = $competition_manager->edit($competition);
            return competitionErrorsToString($result);

        case 2: // remove
            $result = $competition_manager->remove($competition);
            return competitionErrorsToString($result);
    }
}
