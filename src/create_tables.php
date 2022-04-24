<?php

/**
 * Enables one to create the required tables from remote
 * 
 * @package IO
 */

// define aliases
use function db\connect;
use function db\createTables;

// add required database tools
require_once("db/adapter/db_adapter_generic.php");

// connect to database and create tables
createTables($db = connect());

// disconnect form database
$db->close();
