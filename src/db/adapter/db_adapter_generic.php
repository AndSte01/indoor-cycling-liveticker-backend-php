<?php

/**
 * Methods used for interaction with database
 * 
 * This script is used for interaction with the database configured in "db_config.php", 
 * please do not invoke the methods directly, instead consider using the tools provided in "db_utils.php"
 * 
 * FUNCTIONS IN THIS SCRIPT DO NOT CHECK FOR ERRORS OR INVALID ARGUMENTS, USE FUNCTIONS PROVIDED IN "db_utils.php"!!!
 * 
 * 
 * Layout of db_config::TABLE_USER
 * 
 * | ID                                  | name | password | role     |
 * | ----------------------------------- | ---- | -------- | -------- |
 * | INT                                 | text | text     | INT      |
 * | NOT NULL AUTO_INCREMENT PRIMARY KEY |      |          | NOT NULL |
 * 
 * 
 * Layout of db_config::TABLE_COMPETITION
 * 
 * | ID                                  | date                              | name | location | user     | areas      | feature_set | live       |
 * | ----------------------------------- | --------------------------------- | ---- | -------- | -------- | ---------- | ----------- | ---------- |
 * | INT                                 | date                              | text | text     | int      | TINYINT(1) | TINYINT(1)  | TINYINT(1) |
 * | NOT NULL AUTO_INCREMENT PRIMARY KEY | NOT NULL DEFAULT (CURRENT_DATE()) |      |          | NOT NULL |           NOT NULL DEFAULT 0          |
 * 
 * 
 * Layout of db_config::TABLE_DISCIPLINE
 * 
 * | ID                                  | timestamp                                                          | competition | type       | fallback_name | round      | finished   |
 * | ----------------------------------- | ------------------------------------------------------------------ | ----------- | ---------- | ------------- | ---------- | ---------- |
 * | INT                                 | TIMESTAMP                                                          | integer     | TINYINT(1) | text          | TINYINT(1) | TINYINT(1) |
 * | NOT NULL AUTO_INCREMENT PRIMARY KEY | NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() |         NOT NULL         |               | UNSIGNED   |            |
 * |                                     |                                                                    |                          |               |         NOT NULL        |
 * 
 * explanation of type (used in discipline):
 * | `0`         | `000`      | `0`    | `000` |
 * | ----------- | ---------- | ------ | ----- |
 * | error, sign | Discipline | gender | age   |
 * 
 * |       | Discipline                     |   |     | gender     |    |       | age         |
 * | ----- | ------------------------------ |   | --- | ---------- |    | ----- | ----------- |
 * | `000` | Single artistic cycling        |   | `0` | male, open |    | `000` | reserved    |
 * | `001` | Pair artistic cycling          |   | `1` | female     |    | `001` | Pupils  U11 |
 * | `010` | Artistic Cycling Team 4 (ACT4) |                           | `010` | Pupils  U13 |
 * | `011` | Artistic Cycling Team 6 (ACT6) |                           | `011` | Pupils  U15 |
 * | `110` | Unicycle Team 4                |                           | `100` | Juniors U19 |
 * | `111` | Unicycle Team 6                |                           | `101` | Elite   O18 |
 * 
 * If the client doesn't support discipline by type, type should be set to (10000001 or. -1). Then the fallback_name should be set with a meaningful string.
 * 
 * 
 * Layout of db_config::TABLE_RESULT
 * 
 * | ID                                  | timestamp                                                          | discipline | start_number | name | club | score_submitted | score_accomplished | time     | finished   |
 * | ----------------------------------- | ------------------------------------------------------------------ | ---------- | ------------ | ---- | ---- | --------------- | ------------------ | -------- | ---------- |
 * | INT                                 | TIMESTAMP                                                          | integer    | SMALLINT     | text | text | float           | float              | SMALLINT | TINYINT(1) |
 * | NOT NULL AUTO_INCREMENT PRIMARY KEY | NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() | NOT NULL   | UNSIGNED     |      |      |                 |                    | UNSIGNED | NOT NULL   |
 * 
 * 
 * @package Database\Database
 * 
 * @todo make ID unsigned (some time in the future)
 * @todo consider renaming live in competition to finished (or not finished)
 */

// assign namespace
namespace db;

// define aliases
use DateTime;
use mysqli;

// Global database definitions
require_once(dirname(__FILE__) . "/../db_config.php");

/**
 * A collection of generic functions used to interact with the database 
 */
class adapterGeneric
{

    /**
     * Connect to database configured in db_config.php
     * 
     * @return mysqli database the function connected to
     */
    public static function connect(): mysqli
    {
        $db = mysqli_connect(db_config::HOST, db_config::USER, db_config::PASSWORD, db_config::NAME) or die(mysqli_connect_errno());
        return $db;
    }

    /**
     * Disconnect form database passed as parameter
     * 
     * @param mysqli $db database to disconnect
     * 
     * @deprecated use $db->close() directly
     */
    public static function disconnect(mysqli $db): void
    {
        $db->close();

        error_log("don't use disconnect() use \$db->close() instead;");
    }


    /**
     * Sets up the tables of the database
     * 
     * @param mysqli $db Database in which tables are created
     * @return string|null error message
     * 
     * @todo php 8: make use of union type for return
     */
    public static function createTables(mysqli $db): string
    {
        // --- User Table ---

        // make query for TABLE_USER
        $query  = "create table IF NOT EXISTS " . db_config::TABLE_USER . " ( ";
        $query .= db_kwd::USER_ID .          " INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";  // Id of the user
        $query .= db_kwd::USER_NAME .        " text NOT NULL, ";                            // Username
        $query .= db_kwd::USER_PASSWORD .    " text NOT NULL, ";                            // Password
        $query .= db_kwd::USER_ROLE .        " INT NOT NULL)";                              // Role

        // execute query and do error handling
        if ($db->query($query) != true) {
            return "couldn't create table '" . db_config::TABLE_USER . "': " . $db->error;
        }


        // --- Competition Table ---

        // make query for TABLE_COMPETITION
        $query  = "create table IF NOT EXISTS " . db_config::TABLE_COMPETITION . " ( ";
        $query .= db_kwd::COMPETITION_ID .          " INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";  // Id of Competition
        $query .= db_kwd::COMPETITION_DATE .        " date NOT NULL DEFAULT (CURRENT_DATE()), ";   // Date of competition  // WARN requires MySQL >8.0.13
        $query .= db_kwd::COMPETITION_NAME .        " text, ";
        $query .= db_kwd::COMPETITION_LOCATION .    " text, ";
        $query .= db_kwd::COMPETITION_USER .        " int NOT NULL, ";
        $query .= db_kwd::COMPETITION_AREAS .       " TINYINT(1) NOT NULL DEFAULT 0, ";
        $query .= db_kwd::COMPETITION_FEATURE_SET . " TINYINT(1) NOT NULL DEFAULT 0, ";
        $query .= db_kwd::COMPETITION_LIVE .        " TINYINT(1) NOT NULL DEFAULT 0)";             // 0 isn't Live, 1 is Live

        // execute query and do error handling
        if ($db->query($query) != true) {
            return "couldn't create table '" . db_config::TABLE_COMPETITION . "': " . $db->error;
        }


        // --- Discipline Table ---

        // make query for TABLE_DISCIPLINE
        $query  = "create table  IF NOT EXISTS " . db_config::TABLE_DISCIPLINE . " ( ";
        $query .= db_kwd::DISCIPLINE_ID .            " INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";                                        // Id of discipline
        $query .= db_kwd::DISCIPLINE_TIMESTAMP .     " TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), ";   // timestamp for calculating deltas
        $query .= db_kwd::DISCIPLINE_COMPETITION .   " integer NOT NULL, ";                                                               // competition id
        $query .= db_kwd::DISCIPLINE_TYPE .          " TINYINT(1) NOT NULL, ";                                                            // type of the category 
        $query .= db_kwd::DISCIPLINE_FALLBACK_NAME . " text, ";                                                                           // fallback name, used in case of negative type
        $query .= db_kwd::DISCIPLINE_ROUND .         " TINYINT(1) UNSIGNED NOT NULL, ";                                                   // round of the discipline inside of the competition (e.g. preliminary and final round)
        $query .= db_kwd::DISCIPLINE_FINISHED .      " TINYINT(1) NOT NULL )";                                                            // 0 ongoing, 1 done

        // execute query and do error handling
        if ($db->query($query) != true) {
            return "couldn't create table '" . db_config::TABLE_DISCIPLINE . "': " . $db->error;
        }


        // --- Results Table ---

        // make query for TABLE_RESULT
        $query  = "create table IF NOT EXISTS " . db_config::TABLE_RESULT . " ( ";
        $query .= db_kwd::RESULT_ID .                   " INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";                                         // Id of result (INT is enough)
        $query .= db_kwd::RESULT_TIMESTAMP .            " TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), ";    // timestamp for calculating deltas
        $query .= db_kwd::RESULT_DISCIPLINE .           " integer NOT NULL, ";                                                                // id of the discipline
        $query .= db_kwd::RESULT_START_NUMBER .         " SMALLINT UNSIGNED, ";                                                               // 0 in case of Twitter
        $query .= db_kwd::RESULT_NAME .                 " text, ";
        $query .= db_kwd::RESULT_CLUB .                 " text, ";                                                                            // empty in case of Twitter
        $query .= db_kwd::RESULT_SCORE_SUBMITTED .      " float, ";                                                                           // -1 in case of Twitter
        $query .= db_kwd::RESULT_SCORE_ACCOMPLISHED .   " float, ";
        $query .= db_kwd::RESULT_TIME .                 " SMALLINT UNSIGNED, ";                                                               // time in seconds, -99 if finished
        $query .= db_kwd::RESULT_FINISHED .             " TINYINT(1) NOT NULL )";                                                             // 0 ongoing, 1 done

        // execute query and do error handling
        if ($db->query($query) != true) {
            return "couldn't create table '" . db_config::TABLE_RESULT . "': " . $db->error;
        }

        return "";
    }

    /**
     * Returns the current time of the database (might be different if MySQL server and php server aren't the same device).
     * The way an unsuccessful query is handled might be irritating (returning the php servers timer) but makes sense because, it helps code relying
     * on this functions not to break, furthermore if the MySQL server can't return it's current time it probably has an error preventing it from handling all
     * query relying on an accurate timestamp.
     * 
     * @param mysqli $db Database in which tables are created
     * @return DateTime time of MySQL server (in case of error the time of the php server is returned)
     */
    public static function getCurrentTime(mysqli $db): DateTime
    {
        // prepare statement to request current timestamp
        $statement = $db->prepare("select now()");

        // execute statement and check if it was executed successfully
        if ($statement->execute() == false) {
            // return the current time of the server
            return new DateTime();
        }

        // bind variable to result
        $statement->bind_result($time);

        // no while required because only one result will be sent
        $statement->fetch();

        // try to generate DateTime from result, if it fails, log error and set time to current server time
        try {
            $time = new DateTime($time);
        } catch (\Exception $e) {
            error_log($e);
            return new DateTime();
        }

        // return the mysql server time as DateTime object
        return $time;
    }

    /**
     * Cleans database by searching for elements that lack a parent and removing it.
     * 
     * In the best case no elements are removed (given $strict = false),
     * this means that the code works fine and the cleaning tasks in the adapters do their job correctly.
     * Please note the action is quiet intense for the database, so only run it if it is required.
     * 
     * @param mysqli $db The database to clean
     * @param bool $strict true: remove competitions whose users got deleted (and also delete all competitions with user=0)
     *                     false: assign user 0 to all competitions where the user got deleted
     * 
     * @return array The deleted elements (only id's)
     * 
     * @todo implement
     */
    /*public static function cleanDatabase(mysqli $db, bool $strict = false): array
    {
        // don't store any id's in this script do it entirely with query's (if the database get's big memory problems might occur)

        // do optimization
        https://forums.mysql.com/read.php?28,247289,249191#msg-249191
        
        // do deletion with join
        https://dev.mysql.com/doc/refman/8.0/en/delete.html
        https://stackoverflow.com/questions/17083862/mysql-delete-row-where-parent-does-not-exist

        return [];
    }*/
}
