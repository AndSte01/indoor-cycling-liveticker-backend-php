<?php

/**
 * Some functions that might be useful for working with competitions
 * 
 * @package Database\Utilities
 */

// assign namespace
namespace db\utils;

// define aliases
use errors;
use managerCompetition;
use managerCompetitionInterface;

// import required filed
require_once(dirname(__FILE__) . "/../managers/db_managers_competition_interface.php");
require_once(dirname(__FILE__) . "/../../errors.php");

/**
 * Converts competition errors to a string of errors defined in errors.php
 * 
 * @param int $error the error from managerCompetitionInterface
 * 
 * @return string the errors as a string
 */
function competitionErrorsToString(int $errors, bool $prepareDie = false): string
{
    switch ($errors) {
        case managerCompetition::ERROR_ALREADY_EXISTING:
            return errors::to_error_string([errors::ALREADY_EXISTS], $prepareDie);

        case managerCompetition::ERROR_MISSING_INFORMATION:
            return errors::to_error_string([errors::MISSING_INFORMATION], $prepareDie);

        case managerCompetition::ERROR_NOT_EXISTING:
            return errors::to_error_string([errors::NOT_EXISTING], $prepareDie);

        case managerCompetition::ERROR_OUT_OF_RANGE:
            return errors::to_error_string([errors::PARAM_OUT_OF_RANGE], $prepareDie);

        case managerCompetition::ERROR_WRONG_USER_ID:
            return errors::to_error_string([errors::ACCESS_DENIED], $prepareDie);

        default:
            return errors::to_error_string([errors::SUCCESS]);
    }
}
