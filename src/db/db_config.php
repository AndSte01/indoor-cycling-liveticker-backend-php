<?php

/**
 * Constants used for interaction with Database
 * 
 * This file contains all necessary constants to interact with the database
 * 
 * @package Database\Config
 */

// assign namespace
namespace db;

/**
 * Global definitions used to configure database
 */
class db_config
{
    /** @var string Name of the database */
    const NAME     = "liveticker";
    /** @var string Name of a user that has read/write access to the database */
    const USER     = 'liveticker';
    /** @var string The password of the user (see USER) */
    const PASSWORD = 'mysqlliveticker';
    /** @var string Hostname of the Database server */
    const HOST     = "localhost";

    // name of the tables
    const TABLE_USER        = "dev_users_liveticker";
    const TABLE_COMPETITION = "dev_competitions_liveticker";
    const TABLE_DISCIPLINE  = "dev_disciplines_liveticker";
    const TABLE_RESULT      = "dev_results_liveticker";
}

/**
 * Definition of constants used to work with the tables in the database
 */
class db_kwd
{
    // for const TABLE_USER
    const USER_ID       = "ID";
    const USER_NAME     = "name";
    const USER_PASSWORD = "password";
    const USER_ROLE     = "role";

    // for const TABLE_COMPETITION
    const COMPETITION_ID          = "ID";
    const COMPETITION_DATE        = "date";
    const COMPETITION_NAME        = "name";
    const COMPETITION_LOCATION    = "location";
    const COMPETITION_USER        = "user";
    const COMPETITION_AREAS       = "areas";
    const COMPETITION_FEATURE_SET = "feature_set";
    const COMPETITION_LIVE        = "live";

    // for const TABLE_DISCIPLINE
    const DISCIPLINE_ID            = "ID";
    const DISCIPLINE_TIMESTAMP     = "timestamp";
    const DISCIPLINE_COMPETITION   = "competition";
    const DISCIPLINE_TYPE          = "type";
    const DISCIPLINE_FALLBACK_NAME = "fallback_name";
    const DISCIPLINE_ROUND         = "round";
    const DISCIPLINE_FINISHED      = "finished";

    // for const TABLE_RESULT
    const RESULT_ID                 = "ID";
    const RESULT_TIMESTAMP          = "timestamp";
    const RESULT_DISCIPLINE         = "discipline";
    const RESULT_START_NUMBER       = "start_number";
    const RESULT_NAME               = "name";
    const RESULT_CLUB               = "club";
    const RESULT_SCORE_SUBMITTED    = "score_submitted";
    const RESULT_SCORE_ACCOMPLISHED = "score_accomplished";
    const RESULT_TIME               = "time";
    const RESULT_FINISHED           = "finished";
}
