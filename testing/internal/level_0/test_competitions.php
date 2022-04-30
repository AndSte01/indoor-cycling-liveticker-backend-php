<?php

/**
 * Script used for internal testing of competitions
 * 
 * @package testing\internal\level_0
 */

// temp namespace
namespace db;

// Error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// embed required files
require_once("db/adapter/db_adapter_competition.php");
require_once("db/adapter/db_adapter_generic.php");

// connect to database
$db = adapterGeneric::connect();

// setting correct header
header("Content-Type: application/json");

// --- ADD USERS TO DATABASE ---
// $user1 = new user(null, "andreas", "steger");
// $result = adapterUser::add($db, [$user1]);


// --- EDIT USERS IN DATABASE ---
// $user2 = new user(null, "lea", "steger");
// $user2->updateId($result[0]->{user::KEY_ID});
// adapterUser::edit($db, [$user2]);


// --- SEARCH FOR USERS ---
// search for all users
// echo json_encode(adapterUser::search($db));

// search for user by id
echo json_encode(adapterCompetition::search($db, true, 2));

// search for user by name
// echo json_encode(adapterUser::search($db));




// disconnect form database
$db->close();
