<?php

/**
 * This file contains functions to functions to interact with database for legacy support
 * 
 * @deprecated please try to use new functions whenever possible.
 * 
 * @package legacy
 */

// assign namespace
namespace legacy;

// define aliases
use db\competition;
use db\adapterCompetition;
use function db\connect;

// include required files
require_once(dirname(__FILE__) . "/../db/adapter/db_adapter_generic.php");
require_once(dirname(__FILE__) . "/../db/adapter/db_adapter_competition.php");

/**
 * Function checks if competition is valid
 * If a similar competition already exists it checks wether the credentials fit or not
 * 
 * @param string $date Date of the competition
 * @param string $name Name of the competition
 * @param string $location Location of the competition
 * @param string $credential Credential of the competition
 * @param string $areas Number of areas of the competition
 * @param string $feature_set Feature set of the competition
 * @param string $live Wether competition is live or not
 */
function competitionCheckInsert(
    string $date,
    string $name,
    string $location,
    string $credential,
    string $areas = "0",
    string $feature_set = "0",
    string $live = "0"
): array {
    // variable to store errors
    $errors = 0;

    // connect to database
    $db = connect();

    // try to parse input to competition (translate to old error scheme on the go)
    $competition = new competition();
    $errors |= translateErrorCodes($competition->parse("0", $date, $name, $location, $credential, $areas, $feature_set, $live, $db));

    // search for similar competition
    $competitions = adapterCompetition::search($db, true, [], $competition->{competition::KEY_DATE}, $competition->{competition::KEY_NAME}, $competition->{competition::KEY_LOCATION});

    // if more than one competition is found add legacy_error::CREDENTIAL_ID (makes not to much sense but better than nothing)
    if (count($competitions) > 1) {
        $errors |= legacy_error::COMPETITION_ID;

        // return errors
        return [null, $errors];
    }

    // check if one competition was found (if so check if credentials match and update it with new values, or if the don't add legacy_error::CREDENTIAL_ID)
    if (count($competitions) != 0) {
        // check if credentials match (use already processed credentials to prevent any errors due to bad characters)
        if (strcmp($competitions[0]->{competition::KEY_CREDENTIAL}, $competition->{competition::KEY_CREDENTIAL}) != 0) {
            $errors |= legacy_error::CREDENTIAL;

            // return error
            return [null, $errors];
        } else {
            // update id of competition
            $competition->updateId($competitions[0]->{competition::KEY_ID});

            // update competition in Database
            adapterCompetition::edit($db, [$competition]);

            // return updated competition
            return [$competition, $errors];
        }
    }

    // if all checks were false (in this case thats positive :) check wether the competition should be added to the database (meaning no errors occurred)
    if ($errors == 0) {
        $competition = (adapterCompetition::add($db, [$competition]))[0];
    }

    json_encode($competition);

    // return errors
    return [$competition, $errors];
}

/**
 * Function that helps with translating new error codes to legacy errors
 * 
 * @param int $errors error in new format
 * @param bool $loose enables less strict error conversion (errors happened due to illegal character removal are removed)
 * @return int error in old format
 */
function translateErrorCodes(int $errors, bool $loose = false): int
{
    $return = legacy_error::SUCCESS;
    $return |= (($errors & competition::ERROR_DATE) > 0) ? legacy_error::DATE : 0;

    // ignore small errors when desired
    if (!$loose) {
        $return |= (($errors & competition::ERROR_NAME) > 0) ? legacy_error::NAME : 0;
        $return |= (($errors & competition::ERROR_LOCATION) > 0) ? legacy_error::LOCATION : 0;
        $return |= (($errors & competition::ERROR_CREDENTIAL) > 0) ? legacy_error::CREDENTIAL : 0;
    }

    return $return;
}
