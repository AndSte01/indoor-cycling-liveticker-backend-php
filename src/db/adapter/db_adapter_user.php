<?php

/**
 * Adapter to deal with users in the database
 * 
 * this file contains a set of methods and constants to work with users in the database, in an complicated not easy way to use.
 * please do not invoke the methods directly, instead consider using the tools provided in "db_utils_user.php"
 * 
 * FUNCTIONS IN THIS SCRIPT DOES NOT CHECK FOR ERRORS OR INVALID ARGUMENTS, USE FUNCTIONS PROVIDED IN "db_utils_user.php"!!!
 * 
 * @package Database\Database
 */

// assign namespace
namespace db;

// import required files
require_once("db_adapter_interface.php");
require_once(dirname(__FILE__) . "/../representatives/db_representatives_user.php");

// define aliases
use mysqli;

class adapterUser implements AdapterInterface
{
    /**
     * Note: the ability to search users by passwords is used for legacy reasons (don't use it in normal circumstances)
     * 
     * @param ?int $user_id Id of the users to search for
     * @param ?string $name Name of the user to search for
     * @param ?string $password Password of the user
     */
    public static function search(mysqli $db, ?int $user_id = null, ?string $name = null, ?string $password = null): array
    {
        // empty return
        $return = [];

        // Put search filters and corresponding parameters in an array
        $filter = [];
        $parameters = [];

        // check if filters need to be set
        if (($user_id != null)) { // also true if empty array
            $filter[] = db_kwd::USER_ID . "=?";
            $parameters[]  = strval($user_id);
        }
        if ($name != null) {
            $filter[] = db_kwd::USER_NAME . "=?";
            $parameters[] = $name;
        }
        if ($password != null) {
            $filter[] = db_kwd::USER_PASSWORD . "=?";
            $parameters[] = $password;
        }

        // Make $filter (a) string again!
        if ($filter != null)
            $filter = "WHERE " . implode(" AND ", $filter); // "Decode" filter array to useful string
        else
            $filter = "WHERE 1"; // Add behavior to list all entries if no filter is applied

        // Create SQL query
        $statement = $db->prepare("SELECT " . implode(", ", [
            db_kwd::USER_ID,
            db_kwd::USER_NAME,
            db_kwd::USER_PASSWORD,
            db_kwd::USER_ROLE
        ]) .
            " FROM " . db_config::TABLE_USER . " $filter;");

        /**
         * awful but gets a beautiful replacement with php >8.1
         * Replacement: remove all bind_param() and replace $statement->execute() with $statement->execute($parameters)
         */
        /*switch (count($parameters)) {
            case 1:
                $statement->bind_param("s", $parameters[0]);
                break;

            case 2:
                $statement->bind_param("ss", $parameters[0], $parameters[1]);
                break;

            case 2:
                $statement->bind_param("sss", $parameters[0], $parameters[1], $parameters[2]);
                break;

            default:
                break;
        }*/

        // execute statement
        $statement->execute($parameters);

        // bind result values to statement
        $statement->bind_result($_1, $_2, $_3, $_4);

        // iterate over (msql) results
        while ($statement->fetch()) {
            $entry = new user();
            $entry->parse($_1, $_2, $_3, $_4, $db);

            // append to list
            $return[] = $entry;
        }

        // return array of results
        return $return;
    }

    // explained in the interface
    public static function add(mysqli $db, array $users): array
    {
        // empty return array
        $return = [];

        // use prepared statement to prevent SQL injections
        $statement = $db->prepare("INSERT INTO " . db_config::TABLE_USER . " (" .
            implode(", ", [
                db_kwd::USER_NAME,
                db_kwd::USER_PASSWORD,
                db_kwd::USER_ROLE
            ])
            . ") VALUES (?, ?, ?);");

        // bind parameters to statement
        $statement->bind_param(
            "ssi",
            $user_name,
            $user_password,
            $user_role
        );

        // iterate through array of users and add to database
        foreach ($users as &$user) {
            $user_name = $user->{user::KEY_NAME};
            $user_password = $user->{user::KEY_PASSWORD};
            $user_role = $user->{user::KEY_ROLE};

            if (!$statement->execute()) {
                error_log("error while writing user to database");

                // prevent rest of the loop from being executed
                continue;
            }

            // update id in user and add it to the return statement
            $return[] = $user->updateId($db->insert_id);
        }

        return $return;
    }

    // explained in the interface
    public static function edit(mysqli $db, array $users): void
    {
        // use prepared statement to prevent SQL injections
        $statement = $db->prepare("UPDATE " . db_config::TABLE_USER . " SET " .
            implode(", ", [
                db_kwd::USER_NAME . "=? ",
                db_kwd::USER_PASSWORD . "=? ",
                db_kwd::USER_ROLE . "=? "
            ])
            . " WHERE " . db_kwd::USER_ID . "=?");

        // bind parameters to statement
        $statement->bind_param(
            "ssii",
            $user_name,
            $user_password,
            $user_role,
            $user_id
        );

        // iterate through array of users and add to database
        foreach ($users as &$user) {
            $user_name = $user->{user::KEY_NAME};
            $user_password = $user->{user::KEY_PASSWORD};
            $user_role = $user->{user::KEY_ROLE};
            $user_id = $user->{user::KEY_ID};

            if (!$statement->execute()) {
                error_log("error while writing user to database");
            }
        }
    }

    // explained in the interface
    public static function remove(mysqli $db, array $users): void
    {
        // prepare statement
        $statement = $db->prepare("DELETE FROM " . db_config::TABLE_USER . " WHERE " . db_kwd::USER_ID . "=?");
        $statement->bind_param("i", $ID);

        // iterate through array and execute statement for different ids
        foreach ($users as &$user) {
            $ID = $user->{user::KEY_ID};
            $statement->execute();
        }
    }
}
