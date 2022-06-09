<?php

/**
 * Interface for database adapters
 * 
 * this file contains the interface after which database adapters are formed.
 * 
 * @package Database\Database
 */

// assign namespace
namespace db;

// define aliases
use db\RepresentativeInterface;
use mysqli;

/**
 * Interface used to describe an database adapter.
 */
interface AdapterInterface
{
    /**
     * Searches for representatives in the database
     * 
     * A general purpose function to search for representatives in the database.
     * More specific (and probably faster) search functions might be implemented, those shall be named searchByX where X is the more specific search term.
     * 
     * @param mysqli $db Database to work with
     * 
     * @return RepresentativeInterface[] array of representatives found in the database
     */
    public static function search(mysqli $db): array;

    /**
     * Adds an array of representatives to the database
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface[] $representatives Array of representatives to add to database
     * 
     * @return RepresentativeInterface[] Array of the written representatives with updated ids
     */
    public static function add(mysqli $db, array $representatives): array;

    /**
     * Edits representatives passed in the arrays identified by their ID variables.
     * All fields (except auto generated ones such as id or timestamp) specified in $fields
     * are overwritten by the values stored in the corresponding representative object.
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface $representative Representative to edit in the database
     * @param array $keys The fields to update makes partial updates possible, use fields defined in Representative (not those in db_config.php)
     */
    // object is used instead of RepresentativeInterface so classes implementing a representative can be used as types for the implementing adapter
    public static function edit(mysqli $db, RepresentativeInterface $representative, array $keys): void;

    /**
     * Removes representatives form teh database
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface[] $representatives Representatives to delete form database (only the id really matters)
     */
    public static function remove(mysqli $db, array $representatives): void;

    /**
     * Makes representatives ready for the database
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface &$representative Reference to representative that will be made ready for the database
     * 
     * @return int Array of the errors that happened during the process (Note: array doesn't contain the updated representatives) 
     */
    public static function makeRepresentativeDbReady(mysqli $db, RepresentativeInterface &$representative): int;
}
