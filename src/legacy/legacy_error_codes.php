<?php

/**
 * Legacy Error codes
 * 
 * This file contains the legacy error codes
 * 
 * @deprecated please use new error codes defined in "error_codes.php" whenever possible
 * 
 * @package legacy
 */

// assign namespace
namespace legacy;

// Fehler codes 
// ACHTUNG: jeweils ein eigenes BIT !!
$FEHLER_ERFOLG          = 0;     // 0000000000000000
$FEHLER_ID_WETTBEWERB   = 1;     // 0000000000000001
$FEHLER_BENUTZER        = 2;     // 0000000000000010
$FEHLER_ID_BENUTZER     = 4;     // 0000000000000100    // Wettkampf mit anderem Benutzer vorhanden
$FEHLER_STARTNUMMER     = 8;     // 0000000000001000
$FEHLER_STARTER         = 16;    // 0000000000010000
$FEHLER_VEREIN          = 32;    // 0000000000100000
$FEHLER_AUFGESTELLT     = 64;    // 0000000001000000
$FEHLER_AUSGEFAHREN     = 128;   // 0000000010000000
$FEHLER_FAHRZEIT        = 256;   // 0000000100000000
$FEHLER_DISZIPLIN       = 512;   // 0000001000000000
$FEHLER_DATUM           = 1024;  // 0000010000000000
$FEHLER_NAME            = 2048;  // 0000100000000000
$FEHLER_ORT             = 4096;  // 0001000000000000
$FEHLER_DATENBANK       = 8192;  // 0010000000000000
$FEHLER_BEENDET         = 16384; // 0100000000000000

/**
 * Definitions of legacy error codes
 */
class legacy_error
{
    const SUCCESS            = 0;     // 0000000000000000
    const COMPETITION_ID     = 1;     // 0000000000000001
    const CREDENTIAL         = 2;     // 0000000000000010
    const CREDENTIAL_ID      = 4;     // 0000000000000100    // competition with similar credentials already exists
    const START_NUMBER       = 8;     // 0000000000001000
    const START_NAME         = 16;    // 0000000000010000
    const CLUB               = 32;    // 0000000000100000
    const SCORE_SUBMITTED    = 64;    // 0000000001000000
    const SCORE_ACCOMPLISHED = 128;   // 0000000010000000
    const TIME               = 256;   // 0000000100000000
    const DISCIPLINE         = 512;   // 0000001000000000
    const DATE               = 1024;  // 0000010000000000
    const NAME               = 2048;  // 0000100000000000   // of competition
    const LOCATION           = 4096;  // 0001000000000000
    const DATABASE           = 8192;  // 0010000000000000
    const FINISHED           = 16384; // 0100000000000000
}
