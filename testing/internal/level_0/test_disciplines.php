<?php

/**
 * Script used for internal testing of disciplines
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
require_once(dirname(__FILE__) . "/../../../db/adapter/db_adapter_discipline.php");
require_once(dirname(__FILE__) . "/../../../db/adapter/db_adapter_generic.php");

// connect to database
$db = adapterGeneric::connect();

// set correct content type
header("Content-Type: application/json");

// --- SEARCH FOR DISCIPLINES ---
// search for all disciplines
// die(json_encode(adapterDiscipline::search($db)));

// search for discipline by id
// die(json_encode(adapterDiscipline::search($db, 1)));

// search for discipline by competition
// die(json_encode(adapterDiscipline::search($db, null, 1)));

// search for discipline by timestamp
// $timestamp = (new DateTime())->modify("-1 day");
// die(json_encode(adapterDiscipline::search($db, null, null, $timestamp)));

// --- ADD DISCIPLINES TO DATABASE ---
// $discipline1 = new discipline(null, null, 1, -1, "Test Disziplin 2", 0, true);
// $result = adapterDiscipline::add($db, [$discipline1]);

// --- EDIT DISCIPLINES IN DATABASE ---
// $discipline2 = new discipline(null, null, 1, -1, "Test Disziplin 3", 0, true);
// $discipline2->updateId($result[0]->{discipline::KEY_ID});
// adapterDiscipline::edit($db, [$discipline2]);

// disconnect form database
$db->close();
