<?php

/**
 * Some functions that might be useful for working with authentication
 * 
 * @package Database\Utilities
 */

// assign namespace
namespace db\utils;

// define aliases
use errors;
use managerAuthenticationInterface;

// import required filed
require_once(dirname(__FILE__) . "/../managers/db_managers_authentication_interface.php");
require_once(dirname(__FILE__) . "/../../errors.php");

/**
 * Converts authentication errors to a string of errors defined in errors.php
 * 
 * @param int $error the error from managerAuthenticationInterface
 * 
 * @return string the errors as a string
 */
function authenticationErrorsToString(int $error): string
{
    // don't use prepareDie, elsewise the authentication headers are overwritten
    switch ($error) {
        case managerAuthenticationInterface::ERROR_DISMISSED_AUTHENTICATION:
            return errors::to_error_string([errors::AUTHENTICATION_REQUIRED]);

        case managerAuthenticationInterface::ERROR_FORCED_AUTHENTICATION:
            return errors::to_error_string([errors::AUTHENTICATION_REQUIRED]);

        case managerAuthenticationInterface::ERROR_INVALID_PASSWORD:
            return errors::to_error_string([errors::ACCESS_DENIED]);

        case managerAuthenticationInterface::ERROR_NO_SUCH_USER:
            return errors::to_error_string([errors::NOT_EXISTING]);

        case managerAuthenticationInterface::ERROR_INVALID_RESPONSE:
            return errors::to_error_string([errors::INVALID_REQUEST]);

        default:
            return errors::to_error_string([]);
    }
}
