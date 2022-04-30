<?php

/**
 * Adapter to deal with disciplines in the database
 * 
 * this file contains a set of methods and constants to work with disciplines in the database, in an complicated not easy way to use.
 * please do not invoke the methods directly, instead consider using the tools provided in "db_utils_discipline.php"
 * 
 * FUNCTIONS IN THIS SCRIPT DOES NOT CHECK FOR ERRORS OR INVALID ARGUMENTS, USE FUNCTIONS PROVIDED IN "db_utils_discipline.php"!!!
 * 
 * @package Database\Database
 */

// assign namespace
namespace db;

// import required files
require_once("db_adapter_interface.php");
require_once(dirname(__FILE__) . "/../representatives/db_representatives_discipline.php");

// define aliases

use DateTime;
use mysqli;

class adapterDiscipline implements adapterInterface
{
    /**
     * @param ?int $discipline_id Id of the discipline to search for
     * @param ?int $competition_id Id of the competition whose disciplines should be returned
     * @param ?DateTime $modified_since Get disciplines that were modified after the time passed
     * @param ?string $fallback_name The fallback name of the discipline
     */
    public static function search(
        mysqli $db,
        ?int $discipline_id = null,
        ?int $competition_id = null,
        ?DateTime $modified_since = null,
        ?string $fallback_name = null,
        ?int $type = null,
        ?int $round = null
    ): array {
        // empty return
        $return = [];

        // Put search filters and corresponding parameters in an array
        $filter = [];
        $parameters = [];

        // check if filters need to be set
        if (($discipline_id != null)) { // also true if empty array
            $filter[] = db_kwd::DISCIPLINE_ID . "=?";
            $parameters[]  = strval($discipline_id);
        }
        if ($competition_id != null) {
            $filter[] = db_kwd::DISCIPLINE_COMPETITION . "=?";
            $parameters[] = strval($competition_id);
        }
        if ($modified_since != null) {
            // greater or equal is required so no disciplines with "bad timing" are missed,
            // with this implementation in the worst case the client gets an discipline that wasn't relay updated
            $filter[] = db_kwd::DISCIPLINE_TIMESTAMP . ">=?";
            $parameters[] = $modified_since->format('Y-m-d H:i:s');
        }
        if ($fallback_name != null) {
            $filter[] = db_kwd::DISCIPLINE_FALLBACK_NAME . "=?";
            $parameters[] = $fallback_name;
        }
        if ($type != null) {
            $filter[] = db_kwd::DISCIPLINE_TYPE . "=?";
            $parameters[] = $type;
        }
        if ($round != null) {
            $filter[] = db_kwd::DISCIPLINE_ROUND . "=?";
            $parameters[] = $round;
        }

        // Make $filter (a) string again!
        if ($filter != null)
            $filter = "WHERE " . implode(" AND ", $filter); // "Decode" filter array to useful string
        else
            $filter = "WHERE 1"; // Add behavior to list all disciplines if no filter is applied

        // Create SQL query
        $statement = $db->prepare("SELECT " . implode(", ", [
            db_kwd::DISCIPLINE_ID,
            db_kwd::DISCIPLINE_TIMESTAMP,
            db_kwd::DISCIPLINE_COMPETITION,
            db_kwd::DISCIPLINE_TYPE,
            db_kwd::DISCIPLINE_FALLBACK_NAME,
            db_kwd::DISCIPLINE_ROUND,
            db_kwd::DISCIPLINE_FINISHED
        ]) .
            " FROM " . db_config::TABLE_DISCIPLINE . " $filter;");

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

            case 3:
                $statement->bind_param("sss", $parameters[0], $parameters[1], $parameters[2]);
                break;

            case 4:
                $statement->bind_param("ssss", $parameters[0], $parameters[1], $parameters[2], $parameters[3]);
                break;

            default:
                break;
        }*/

        // execute statement
        $statement->execute($parameters);

        // bind result values to statement
        $statement->bind_result($_1, $_2, $_3, $_4, $_5, $_6, $_7);

        // iterate over results
        while ($statement->fetch()) {
            $entry = new discipline();
            $entry->parse($_1, $_2, $_3, $_4, $_5, $_6, $_7, $db);

            // append to list
            $return[] = $entry;
        }

        // return array of disciplines
        return $return;
    }

    /**
     * Note the timestamp won't be updated on returned disciplines, use search with discipline_id for that
     */
    public static function add(mysqli $db, array $disciplines): array
    {
        // empty return array
        $return = [];

        // use prepared statement to prevent SQL injections
        $statement = $db->prepare("INSERT INTO " . db_config::TABLE_DISCIPLINE . " (" .
            implode(", ", [
                db_kwd::DISCIPLINE_COMPETITION,
                db_kwd::DISCIPLINE_TYPE,
                db_kwd::DISCIPLINE_FALLBACK_NAME,
                db_kwd::DISCIPLINE_ROUND,
                db_kwd::DISCIPLINE_FINISHED
            ])
            . ") VALUES (?, ?, ?, ?, ?);");

        // bind parameters to statement
        $statement->bind_param(
            "iisii",
            $discipline_competition_id,
            $discipline_type,
            $discipline_fallback_name,
            $discipline_round,
            $discipline_finished
        );

        // iterate through array of disciplines and add to database
        foreach ($disciplines as &$discipline) {
            $discipline_competition_id = $discipline->{discipline::KEY_COMPETITION_ID};
            $discipline_type = $discipline->{discipline::KEY_TYPE};
            $discipline_fallback_name = $discipline->{discipline::KEY_FALLBACK_NAME};
            $discipline_round = $discipline->{discipline::KEY_ROUND};
            $discipline_finished = (int) $discipline->{discipline::KEY_FINISHED};

            if (!$statement->execute()) {
                error_log("error while writing discipline to database");

                // prevent rest of the loop from being executed
                continue;
            }

            // update id in discipline and add it to the return statement
            $return[] = $discipline->updateId($db->insert_id);
        }

        return $return;
    }

    // explained in the interface
    public static function edit(mysqli $db, array $disciplines): void
    {
        // use prepared statement to prevent SQL injections
        $statement = $db->prepare("UPDATE " . db_config::TABLE_DISCIPLINE . " SET " .
            implode(", ", [
                db_kwd::DISCIPLINE_COMPETITION . "=? ",
                db_kwd::DISCIPLINE_TYPE . "=? ",
                db_kwd::DISCIPLINE_FALLBACK_NAME . "=? ",
                db_kwd::DISCIPLINE_ROUND . "=? ",
                db_kwd::DISCIPLINE_FINISHED . "=? "
            ])
            . " WHERE " . db_kwd::DISCIPLINE_ID . "=?");

        // bind parameters to statement
        $statement->bind_param(
            "iisiii",
            $discipline_competition_id,
            $discipline_type,
            $discipline_fallback_name,
            $discipline_round,
            $discipline_finished,
            $discipline_id
        );

        // iterate through array of disciplines and add to database
        foreach ($disciplines as &$discipline) {
            $discipline_competition_id = $discipline->{discipline::KEY_COMPETITION_ID};
            $discipline_type = $discipline->{discipline::KEY_TYPE};
            $discipline_fallback_name = $discipline->{discipline::KEY_FALLBACK_NAME};
            $discipline_round = $discipline->{discipline::KEY_ROUND};
            $discipline_finished = (int) $discipline->{discipline::KEY_FINISHED};
            $discipline_id = $discipline->{discipline::KEY_ID};

            if (!$statement->execute()) {
                error_log("error while writing discipline to database");
            }
        }
    }

    // explained in the interface
    /**
     * @todo implement garbage collection (remove all child's assigned to this representative)
     */
    public static function remove(mysqli $db, array $disciplines): void
    {
        // prepare statement
        $statement = $db->prepare("DELETE FROM " . db_config::TABLE_DISCIPLINE . " WHERE " . db_kwd::DISCIPLINE_ID . " = ?");
        $statement->bind_param("i", $ID);

        // iterate through array and execute statement for different ids
        foreach ($disciplines as &$discipline) {
            $ID = $discipline->{discipline::KEY_ID};
            $statement->execute();
        }
    }
}
