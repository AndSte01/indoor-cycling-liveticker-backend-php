<?php

/**
 * Legacy Data input for competitions
 * 
 * A webpage to enter competitions in legacy mode
 * 
 * @deprecated use new methods for entering competitions using json
 * 
 * @package legacy
 */

// assign namespace
namespace legacy;

// define aliases
use db\competition;

// enable broad error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// legacy error codes
require_once("legacy/legacy_error_codes.php");

// legacy functions to interact with database
require_once("legacy/legacy_competition.php");

// create variable for error
$error =  legacy_error::SUCCESS;

// get url the file is served at
$url = $_SERVER['PHP_SELF'];

// print out html form
echo "<!DOCTYPE html>";
echo "<form method='POST' action='$url'>";
echo "<input type='text' placeholder='date' name='Datum'\><br>";
echo "<input type='text' placeholder='name' name='Name'\><br>";
echo "<input type='text' placeholder='location' name='Ort'\><br>";
echo "<input type='text' placeholder='credential' name='Benutzer'\><br>";
echo "<input type='text' placeholder='areas' name='areas'\><br>";
echo "<input type='text' placeholder='feature_set' name='feature_set'\><br>";
echo "<input type='text' placeholder='live' name='live'\><br>";
echo "<input type='submit' value='submit'>";
echo "</form>\n";

// check if all the required fields where filled
if (!isset($_POST["Datum"]) || $_POST["Datum"] == "")
  $error |= legacy_error::DATE;
if (!isset($_POST["Name"]) || $_POST["Name"] == "")
  $error |= legacy_error::NAME;
if (!isset($_POST["Ort"]) || $_POST["Ort"] == "")
  $error |= legacy_error::LOCATION;
if (!isset($_POST["Benutzer"]) || $_POST["Benutzer"] == "")
  $error |= legacy_error::CREDENTIAL;

// exit script if $error isn't 0 (legacy_error::SUCCESS), meaning not all fields were filled
if ($error != 0) {
  printf("-%d", $error);
  exit(-1);
}

// map POST parameters to variables
$comp_date = $_POST["Datum"];
$comp_name = $_POST["Name"];
$comp_location = $_POST["Ort"];
$comp_credential = $_POST["Benutzer"];
$comp_areas = (isset($_POST["areas"]) && $_POST["areas"] != "") ? $_POST["areas"] : "0";
$comp_feature_set = (isset($_POST["feature_set"]) && $_POST["feature_set"] != "") ? $_POST["feature_set"] : "0";
$comp_live = (isset($_POST["live"]) && $_POST["live"] != "") ? $_POST["live"] : "0";

// try to add competition to database
$result = competitionCheckInsert($comp_date, $comp_name, $comp_location, $comp_credential, $comp_areas, $comp_feature_set, $comp_live);

if ($result[1] == 0) {
  printf("%d", $result[0]->{competition::KEY_ID});
  exit(0);
} else {
  printf("-%d", $result[1]);
  exit(-1);
}
