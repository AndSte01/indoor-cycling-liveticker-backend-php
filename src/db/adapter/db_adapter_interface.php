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
     * All fields (except auto generated ones such as id or timestamp) are overwritten
     * by the values stored in the corresponding representative object
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface[] $representatives Array of representatives to add update in database
     */
    public static function edit(mysqli $db, array $representatives): void;

    /**
     * Removes representatives form teh database
     * 
     * @param mysqli $db Database to work with
     * @param RepresentativeInterface[] $representatives Representatives to delete form database
     */
    public static function remove(mysqli $db, array $representatives): void;
}
