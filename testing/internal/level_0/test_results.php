<?php

/**
 * Script used for internal testing of results
 * 
 * @package testing\internal\level_0
 */

// temp namespace
namespace db;

use DateTime;

// Error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// embed required files
require_once(dirname(__FILE__) . "/../../../db/adapter/db_adapter_result.php");
require_once(dirname(__FILE__) . "/../../../db/adapter/db_adapter_generic.php");

// connect to database
$db = connect();

// set correct content type
header("Content-Type: application/json");

// --- SEARCH FOR RESULTS ---
// search for all results
die(json_encode(adapterResult::search($db)));

// search for result by id
// die(json_encode(adapterResult::search($db, 1)));

// search for result by discipline
// die(json_encode(adapterResult::search($db, null, [1])));
// die(json_encode(adapterResult::search($db, null, [1, 2])));

// search for result by timestamp
// $timestamp = (new DateTime())->modify("-1 day");
// die(json_encode(adapterResult::search($db, null, null, $timestamp)));

// --- ADD RESULTS TO DATABASE ---
// $result1 = new result(null, null, 3, 103, "akjsdf", "sdflker", 64.45, 43.86, 0, 0);
// $result = adapterResult::add($db, [$result1]);
// die(json_encode($result));

// --- EDIT RESULT IN DATABASE ---
// $result2 = new result(null, null, 3, 103, "akjsdf (update)", "sdflker (update)", 64.45, 43.86, 0, 0); //new discipline(null, null, 1, -1, "Test Disziplin 3", 0, true);
// $result2->updateId($result[0]->{result::KEY_ID});
// adapterResult::edit($db, [$result2]);

// disconnect form database
$db->close();
