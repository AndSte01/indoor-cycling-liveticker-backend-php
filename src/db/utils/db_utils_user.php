<?php

/**
 * Some functions that might be useful for working with authentication
 * 
 * @package Database\Utilities
 */

// assign namespace
namespace db\utils;

// define aliases

use db\user;
use errors;
use managerUserInterface;

// import required filed
require_once(dirname(__FILE__) . "/../managers/db_managers_authentication_interface.php");
require_once(dirname(__FILE__) . "/../representatives/db_representatives_user.php");
require_once(dirname(__FILE__) . "/../../errors.php");

/**
 * Converts user errors to a string of errors defined in errors.php
 * 
 * @param int $error the error from managerAuthenticationInterface
 * @param bool $prepareDie Prepares the header for immediate call of die() afterwards
 * 
 * @return string the errors as a string
 */
function userErrorsToString(int $error,  bool $prepareDie = false): string
{
    switch ($error) {
        case managerUserInterface::ERROR_ADAPTER:
            return errors::to_error_string([errors::INTERNAL_ERROR], $prepareDie);

        case managerUserInterface::ERROR_ALREADY_EXISTING:
            return errors::to_error_string([errors::ALREADY_EXISTS], $prepareDie);

        case managerUserInterface::ERROR_ID:
            return errors::to_error_string([errors::PARAM_OUT_OF_RANGE], $prepareDie);

        case managerUserInterface::ERROR_INVALID_CHARACTERS:
            return errors::to_error_string([errors::INVALID_CHARACTERS], $prepareDie);

        case managerUserInterface::ERROR_NAME:
            return errors::to_error_string([errors::INVALID_CHARACTERS], $prepareDie);

        case managerUserInterface::ERROR_PASSWORD:
            return errors::to_error_string([errors::INVALID_CHARACTERS], $prepareDie);

        default:
            return errors::to_error_string([]);
    }
}

/**
 * parses and verifies an user
 * 
 * @param string $json JSON representation of the user to add or update
 * @param mysqli $db The database to work with
 * 
 * @return string|user Either the error string or a successfully parsed user (note: user is_string())
 */
function parseVerifyUser(string $json): string|user
{
    // decode json to assoc array
    $decoded = json_decode($json, true);

    // check if decode was possible
    if ($decoded == null)
        return errors::to_error_string([errors::INVALID_JSON]);

    // check if decode contained all required fields
    if ($decoded[user::KEY_NAME] == null || $decoded[user::KEY_PASSWORD] == null)
        return errors::to_error_string([errors::MISSING_INFORMATION]);

    // create empty competition to parse data to
    $user = new user();
    $user->parse(
        "", // no id required
        $decoded[user::KEY_NAME],
        $decoded[user::KEY_PASSWORD],
    );

    return $user;
}
