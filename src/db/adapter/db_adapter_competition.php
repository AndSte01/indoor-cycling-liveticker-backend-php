<?php

/**
 * Adapter to deal with competitions in the database
 * 
 * this file contains a set of methods and constants to work with competitions in the database, in an complicated not easy way to use.
 * please do not invoke the methods directly, instead consider using the tools provided in "db_utils_competition.php"
 * 
 * FUNCTIONS IN THIS SCRIPT DOES NOT CHECK FOR ERRORS OR INVALID ARGUMENTS, USE FUNCTIONS PROVIDED IN "db_utils_competition.php"!!!
 * 
 * @package Database\Database
 */

// assign namespace
namespace db;

// import required files
require_once("db_adapter_interface.php");
require_once(dirname(__FILE__) . "/../representatives/db_representatives_competition.php");

// define aliases
use mysqli;
use DateTime;

class adapterCompetition implements AdapterInterface
{
    /**
     * @param bool $useAND Determents wether statement is combined with 'AND' (true) or 'OR' (false)
     * @param ?int $id ID of competition to search for
     * @param ?DateTime $date Date of competition
     * @param ?string $name Name of competition
     * @param ?string $location Location of competition
     * @param ?int $limit The number of results the query should fetch
     * @param ?DateTime $date_end to search for competition between $date_end and $date ($date > $date_end)
     * 
     * @return competition[] array of competitions, might be empty
     */
    public static function search(
        mysqli $db,
        bool $useAND = true,
        ?int $id = null,
        ?DateTime $date = null,
        ?string $name = null,
        ?string $location = null,
        ?int $limit = null,
        ?DateTime $date_end = null
    ): array {
        // empty return
        $return = [];

        // statement to concatenate filters together
        $concat = ($useAND) ? " AND " : " OR ";

        // Put search filters and corresponding parameters in an array
        $filter = [];
        $parameters = [];

        // check if filters need to be set
        if (($id != null)) { // also true if empty array
            $filter[] = db_kwd::COMPETITION_ID . "=?";
            $parameters[] = strval($id);
        }
        if ($name != null) {
            $filter[] = db_kwd::COMPETITION_NAME . "=?";
            $parameters[] = $name;
        }
        if ($location != null) {
            $filter[] = db_kwd::COMPETITION_LOCATION . "=?";
            $parameters[] = $location;
        }
        if ($date != null) {
            // if competition is searched between two dates a modified query is required
            if ($date_end != null) {
                $filter[] = db_kwd::COMPETITION_DATE . " between ? AND ?";
                $parameters[] = $date_end->format('Y-m-d');
                $parameters[] = $date->format('Y-m-d');
            } else {
                $filter[] = db_kwd::COMPETITION_DATE . "=?";
                $parameters[] = $date->format('Y-m-d');
            }
        }

        // Make $filter (a) string again!
        if ($filter != null)
            $filter = "WHERE " . implode($concat, $filter); // "Decode" filter array to useful string
        else
            $filter = "WHERE 1"; // Add behavior to list all competitions if no filter is applied


        // add the limit parameter (strict comparison required for limit = 0)
        $str_limit = "";
        if ($limit !== null) {
            // clean limit value (no values below 0 are allowed)
            $limit = ($limit < 0) ? 0 : $limit;
            $str_limit = "LIMIT " . strval($limit);
        }

        // Create SQL query
        $statement = $db->prepare("SELECT " . implode(", ", [
            db_kwd::COMPETITION_ID,
            db_kwd::COMPETITION_DATE,
            db_kwd::COMPETITION_NAME,
            db_kwd::COMPETITION_LOCATION,
            db_kwd::COMPETITION_USER,
            db_kwd::COMPETITION_AREAS,
            db_kwd::COMPETITION_FEATURE_SET,
            db_kwd::COMPETITION_LIVE
        ]) .
            " FROM " . db_config::TABLE_COMPETITION . " $filter ORDER by date DESC $str_limit;");

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
        $statement->bind_result($_1, $_2, $_3, $_4, $_5, $_6, $_7, $_8);

        // iterate over results
        while ($statement->fetch()) {
            $entry = new competition();
            $entry->parse($_1, $_2, $_3, $_4, $_5, $_6, $_7, $_8, $db);

            // append to list
            $return[] = $entry;
        }

        // return array of competitions
        return $return;
    }

    // explained in the interface
    public static function add(mysqli $db, array $competitions): array
    {
        // empty return array
        $return = [];

        // use prepared statement to prevent SQL injections
        $statement = $db->prepare("INSERT INTO " . db_config::TABLE_COMPETITION . " (" .
            implode(", ", [
                db_kwd::COMPETITION_DATE,
                db_kwd::COMPETITION_NAME,
                db_kwd::COMPETITION_LOCATION,
                db_kwd::COMPETITION_USER,
                db_kwd::COMPETITION_AREAS,
                db_kwd::COMPETITION_FEATURE_SET,
                db_kwd::COMPETITION_LIVE
            ])
            . ") VALUES (?, ?, ?, ?, ?, ?, ?);");

        // bind parameters to statement
        $statement->bind_param(
            "sssiiii",
            $comp_date,
            $comp_name,
            $comp_location,
            $comp_user,
            $comp_areas,
            $comp_feature_set,
            $comp_live
        );

        // iterate through array of competitions and add to database
        foreach ($competitions as &$competition) {
            $comp_date = $competition->{competition::KEY_DATE}->format('Y-m-d');
            $comp_name = $competition->{competition::KEY_NAME};
            $comp_location = $competition->{competition::KEY_LOCATION};
            $comp_user = $competition->{competition::KEY_USER};
            $comp_areas = $competition->{competition::KEY_AREAS};
            $comp_feature_set = $competition->{competition::KEY_FEATURE_SET};
            $comp_live = $competition->{competition::KEY_LIVE};

            if (!$statement->execute()) {
                error_log("error while writing competition to database");

                // prevent rest of the loop from being executed
                continue;
            }

            // update id in competition and add it to the return statement
            $return[] = $competition->updateId($db->insert_id);
        }

        return $return;
    }

    // explained in the interface
    public static function edit(mysqli $db, array $competitions): void
    {
        $statement = $db->prepare("UPDATE " . db_config::TABLE_COMPETITION . " SET " .
            implode(", ", [
                db_kwd::COMPETITION_DATE . " = ?",
                db_kwd::COMPETITION_NAME . " = ?",
                db_kwd::COMPETITION_LOCATION . " = ?",
                db_kwd::COMPETITION_USER . " = ?",
                db_kwd::COMPETITION_AREAS . " = ?",
                db_kwd::COMPETITION_FEATURE_SET . " = ?",
                db_kwd::COMPETITION_LIVE . " = ?"
            ])
            . " WHERE " . db_kwd::COMPETITION_ID . " = ?");

        // bind parameters to statement
        $statement->bind_param(
            "ssssiiii",
            $comp_date,
            $comp_name,
            $comp_location,
            $comp_user,
            $comp_areas,
            $comp_feature_set,
            $comp_live,
            $comp_id
        );

        // iterate through array of competitions and add to database
        foreach ($competitions as &$competition) {
            $comp_id = $competition->{competition::KEY_ID};
            $comp_date = $competition->{competition::KEY_DATE}->format('Y-m-d');
            $comp_name = $competition->{competition::KEY_NAME};
            $comp_location = $competition->{competition::KEY_LOCATION};
            $comp_user = $competition->{competition::KEY_USER};
            $comp_areas = $competition->{competition::KEY_AREAS};
            $comp_feature_set = $competition->{competition::KEY_FEATURE_SET};
            $comp_live = $competition->{competition::KEY_LIVE};

            if (!$statement->execute()) {
                error_log("error while updating competition in database");
            }
        }
    }

    // explained in the interface
    public static function remove(mysqli $db, array $competitions): void
    {
        // prepare statement
        $statement = $db->prepare("DELETE FROM " . db_config::TABLE_COMPETITION . " WHERE " . db_kwd::COMPETITION_ID . " = ?");
        $statement->bind_param("i", $ID);

        // iterate through array and execute statement for different ids
        foreach ($competitions as &$competition) {
            $ID = $competition->{competition::KEY_ID};
            $statement->execute();
        }
    }
}
