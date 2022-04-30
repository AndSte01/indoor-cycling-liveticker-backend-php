<?php

/**
 * @package Database\Representatives
 */

// assign namespace
namespace db;

// import trait and interface
require_once("db_representatives_interface_trait.php");

// define aliases
use DateTime;
use JsonSerializable;
use mysqli;

/** 
 * The class used to describe a discipline.
 */
class discipline implements JsonSerializable, RepresentativeChildInterface
{
    // use trait for basic functionality
    use RepresentativeTrait;

    // Keys used in data array
    /** @var string id of the discipline (unique identifier in database) */
    public const KEY_ID = "id";
    /** @var string timestamp of the discipline (timestamp the discipline was last modified in the database) */
    public const KEY_TIMESTAMP = "timestamp";
    /** @var string id of the competition the discipline is assigned to */
    public const KEY_COMPETITION_ID = "competition";
    /** @var string type of the discipline
     * 
     * If you can't set the type (e. g. lack of support in application set it to -1)
     * 
     * | ...         | `000`      | `0`    | `000` |
     * | ----------- | ---------- | ------ | ----- |
     * | reserved    | Discipline | gender | age   |
     * 
     * |       | Discipline                     |   |     | gender     |    |       | age         |
     * | ----- | ------------------------------ |   | --- | ---------- |    | ----- | ----------- |
     * | `000` | Single artistic cycling        |   | `0` | male, open |    | `000` | reserved    |
     * | `001` | Pair artistic cycling          |   | `1` | female     |    | `001` | Pupils  U11 |
     * | `010` | Artistic Cycling Team 4 (ACT4) |                           | `010` | Pupils  U13 |
     * | `011` | Artistic Cycling Team 6 (ACT6) |                           | `011` | Pupils  U15 |
     * | `110` | Unicycle Team 4                |                           | `100` | Juniors U19 |
     * | `111` | Unicycle Team 6                |                           | `101` | Elite       |
     */
    public const KEY_TYPE = "type";
    /** @var string fallback name of the discipline set by provider if type < 0 */
    public const KEY_FALLBACK_NAME = "fallback_name";
    /** @var string the round of the competition the discipline is located in */
    public const KEY_ROUND = "round";
    /** @var string wether the discipline is finished or not */
    public const KEY_FINISHED = "finished";

    /** @var array data stored in the discipline */
    protected $data = [
        self::KEY_ID => 0,
        self::KEY_TIMESTAMP => 0,
        self::KEY_COMPETITION_ID => 0,
        self::KEY_TYPE => 0,
        self::KEY_FALLBACK_NAME => "",
        self::KEY_ROUND => 0,
        self::KEY_FINISHED => false
    ];

    // Errors
    /** @var int Error while parsing the id */
    const ERROR_ID = 1;
    /** @var int Error while parsing date */
    const ERROR_TIMESTAMP = 2;
    /** @var int Error while parsing competition id */
    const ERROR_COMPETITION_ID = 4;
    /** @var int Error while parsing type */
    const ERROR_TYPE = 8;
    /** @var int Error while parsing fallback name (or if it contained invalid characters and they were removed) */
    const ERROR_FALLBACK_NAME = 16;
    /** @var int Error while parsing the round */
    const ERROR_ROUND = 32;
    /** @var int Error while setting discipline as finished (or not) */
    const ERROR_FINISHED = 64;

    /**
     * Constructor 
     * 
     * @param int $ID Id of the discipline
     * @param DateTime $timestamp The last time the discipline was modified in the database
     * @param int $competition_id ID of the competition the discipline is assigned to
     * @param int $type Type of the discipline (see documentation of const KEY_TYPE to get more information about the values)
     * @param string $fallback_name Name used in case $type can'T be decoded
     * @param int $round The round of the competition the discipline is located in
     * @param int $finished Wether the discipline is finished or not
     */
    function __construct(
        int $ID = null,
        DateTime $timestamp = null,
        int $competition_id = null,
        int $type = null,
        string $fallback_name = null,
        int $round = null,
        bool $finished = null
    ) {
        // this strange way of setting the defaults is used so one can just null all unused fields during construction
        // not relay performant but makes debugging a bit easier
        $this->data[self::KEY_ID]             = $ID             ?? 0;
        $this->data[self::KEY_TIMESTAMP]      = $timestamp      ?? new DateTime();
        $this->data[self::KEY_COMPETITION_ID] = $competition_id ?? 0;
        $this->data[self::KEY_TYPE]           = $type           ?? 0;
        $this->data[self::KEY_FALLBACK_NAME]  = $fallback_name  ?? "";
        $this->data[self::KEY_ROUND]          = $round          ?? 0;
        $this->data[self::KEY_FINISHED]       = $finished       ?? false;
    }

    /** @var bool Stores if timestamp was parsed successfully (might be required for cases in which an accurate timestamp of modifications in the database is mandatory) */
    protected bool $successfullyParsedTimestamp = false;

    /**
     * Returns wether the timestamp was parsed successfully or not
     * 
     * @return bool wether the timestamp was parsed successfully or not
     */
    public function successfullyParsedTimestamp(): bool
    {
        return $this->successfullyParsedTimestamp;
    }

    // explained in RepresentativeInterface
    public function updateId(int $ID): self
    {
        $this->data[self::KEY_ID] = $ID;
        return $this;
    }

    /**
     * Updates the competition id
     */
    public function updateParentId(int $ID): void
    {
        $this->data[self::KEY_COMPETITION_ID] = $ID;
    }

    // explained in RepresentativeInterface
    public function makeDbReady(mysqli $db): int
    {
        // variable for error messages
        $error = 0;

        // timestamp won't be checked because it's never written to database (only relevant when getting a discipline form it)

        // check if invalid characters are present in string, if so remove them and add error
        if (strcmp($this->{self::KEY_FALLBACK_NAME}, $db->real_escape_string($this->{self::KEY_FALLBACK_NAME})) != 0) {
            $this->date[self::KEY_FALLBACK_NAME] = $db->real_escape_string($this->{self::KEY_FALLBACK_NAME});
            $error |= self::ERROR_FALLBACK_NAME;
        }


        // check if integers are within their correct range, if not make them 0 and add error
        // won't check id, it isn't used when writing to db and if reading from db and id is out of range nothing happens
        // competition id can't be smaller than 1 (max. value is due to db limitations)
        if ($this->{self::KEY_COMPETITION_ID} < 1 || $this->{self::KEY_COMPETITION_ID} > 2147483647) {
            $this->data[self::KEY_COMPETITION_ID] = 0; // marks discipline as obviously wrong in database
            $error |= self::ERROR_COMPETITION_ID;
        }

        // make Type valid with build in function
        $error |= $this->makeTypeValid();

        // round is greater or equal 0 by definition
        if ($this->{self::KEY_ROUND} < 0) {
            $this->data[self::KEY_ROUND] = 0;
            $error |= self::ERROR_ROUND;
        }
        if ($this->{self::KEY_ROUND} > 255) {
            $this->data[self::KEY_ROUND] = 255;
            $error |= self::ERROR_ROUND;
        }

        // finished is a boolean and never null, so it already is ok

        // mark discipline as ready for database
        $this->isDbReady = true;

        // return errors
        return $error;
    }

    /**
     * Makes the type of the discipline valid
     * 
     * @return int 0 if type was valid already, 1 if type was invalid and is -1 now
     */
    public function makeTypeValid(): int
    {
        if (discipline_type::validateType($this->{self::KEY_TYPE}))
            return 0;

        // if check was unsuccessful set type to -1 and return error;
        $this->data[self::KEY_TYPE] = -1;
        return self::ERROR_TYPE;
    }

    /**
     * Parse strings into the discipline.
     * NO CHECKS ARE DONE WETHER THE VALUES ARE USEFUL OR NOT, JUST TYPE-SAFETY.
     * 
     * @param string $ID Id of the discipline
     * @param string $timestamp the timestamp of the last modification of the discipline in the database
     * @param string $competition_id Id of the competition the discipline is assigned to
     * @param string $type The type of the discipline (see const KEY_TYPE or documentation od api)
     * @param string $fallback_name Used in case $type isn't valid or the fronted doesn't support it
     * @param string $round the round of the competition the discipline is located in
     * @param string $finished wether a discipline is finished or not
     * @param mysqli $db Database to make compatible with
     * 
     * @return int the errors occurred during parsing
     */
    public function parse(
        string $ID = "",
        string $timestamp = "",
        string $competition_id = "",
        string $type = "",
        string $fallback_name = "",
        string $round = "",
        string $finished = "",
        mysqli $db = null
    ): int {
        // after parsing no discipline isDbReady
        $this->isDbReady = false;

        // variable for error
        $error = 0;

        // try to generate DateTime from string, if it fails, log error and set date to current date
        try {
            $this->data[self::KEY_TIMESTAMP] = new DateTime($timestamp);
            $this->successfullyParsedTimestamp = true; // mark timestamp as successfully parsed
        } catch (\Exception $e) {
            error_log($e);
            $this->data[self::KEY_TIMESTAMP] = new DateTime(); // used for fallback reasons (sets timestamp to current time)
            $this->successfullyParsedTimestamp = false; // mark timestamp as NOT successfully parsed
            $error |= self::ERROR_TIMESTAMP;
        }

        // write fallback_name
        $this->data[self::KEY_FALLBACK_NAME] = $fallback_name;

        // parsing integers
        $this->data[self::KEY_ID] = intval($ID);
        $this->data[self::KEY_COMPETITION_ID] = intval($competition_id);
        $this->data[self::KEY_TYPE] = intval($type);
        $this->data[self::KEY_ROUND] = intval($round);

        // mark discipline as finished or not (ongoing)
        $this->data[self::KEY_FINISHED] = filter_var($finished, FILTER_VALIDATE_BOOLEAN);;

        // if a $db is passed also run makeDbReady()
        if ($db != null)
            $error = $error | $this->makeDbReady($db); // add errors together

        // return errors
        return $error;
    }

    /**
     * Returns values to serialize
     * 
     * @return AssociatedArray Array to serialize
     */
    public function jsonSerialize(): array
    {
        return [
            self::KEY_ID => $this->{self::KEY_ID},
            // self::KEY_TIMESTAMP => $this->{self::KEY_TIMESTAMP}->getTimestamp(),
            self::KEY_COMPETITION_ID => $this->{self::KEY_COMPETITION_ID},
            self::KEY_TYPE => $this->{self::KEY_TYPE},
            self::KEY_FALLBACK_NAME => $this->{self::KEY_FALLBACK_NAME},
            self::KEY_ROUND => $this->{self::KEY_ROUND},
            self::KEY_FINISHED => $this->{self::KEY_FINISHED}
        ];
    }
}

/**
 * Class used to make working with discipline types a bit more easy and separate the specialized code
 */
class discipline_type
{
    // Keys for accessing assoc arrays mor easily
    // disciplines
    const KEY_DISCIPLINE_SINGE_ARTISTIC_CYCLING = "discipline_single_artistic_cycling";
    const KEY_DISCIPLINE_PAIR_ARTISTIC_CYCLING = "discipline_pair_artistic_cycling";
    const KEY_DISCIPLINE_TEAM_4_ARTISTIC_CYCLING = "discipline_team_4_artistic_cycling";
    const KEY_DISCIPLINE_TEAM_6_ARTISTIC_CYCLING = "discipline_team_6_artistic_cycling";
    const KEY_DISCIPLINE_TEAM_4_UNICYCLE = "discipline_team_4_unicycle";
    const KEY_DISCIPLINE_TEAM_6_UNICYCLE = "discipline_team_6_unicycle";

    // genders
    const KEY_GENDER_MALE_OPEN = "gender_male_open";
    const KEY_GENDER_FEMALE = "gender_female";

    // age-groups
    const KEY_AGE_U11 = "age_u11";
    const KEY_AGE_U13 = "age_u13";
    const KEY_AGE_U15 = "age_u15";
    const KEY_AGE_U19 = "age_u19";
    const KEY_AGE_ELITE = "age_elite";

    /** @var array Valid disciplines used to validate a type (be aware the four trailing zeros are removed) */
    public const VALID_DISCIPLINES = [
        self::KEY_DISCIPLINE_SINGE_ARTISTIC_CYCLING  => 0b000, // Single artistic cycling
        self::KEY_DISCIPLINE_PAIR_ARTISTIC_CYCLING   => 0b001, // Pair artistic cycling
        self::KEY_DISCIPLINE_TEAM_4_ARTISTIC_CYCLING => 0b010, // Artistic Cycling Team 4 (ACT4)
        self::KEY_DISCIPLINE_TEAM_6_ARTISTIC_CYCLING => 0b011, // Artistic Cycling Team 6 (ACT6)
        self::KEY_DISCIPLINE_TEAM_4_UNICYCLE         => 0b110, // Unicycle Team 4
        self::KEY_DISCIPLINE_TEAM_6_UNICYCLE         => 0b111  // Unicycle Team 6
    ];

    /** @var array Valid genders used to validate a type (be aware the three trailing zeros were removed) (also might be a bit unnecessary) */
    public const VALID_GENDERS = [
        self::KEY_GENDER_MALE_OPEN => 0b0, // male, open
        self::KEY_GENDER_FEMALE    => 0b1  // female
    ];

    /** @var array Valid ages used to validate a type */
    public const VALID_AGES = [
        self::KEY_AGE_U11   => 0b001, // Pupils U11
        self::KEY_AGE_U13   => 0b010, // Pupils U13
        self::KEY_AGE_U15   => 0b011, // Pupils U15
        self::KEY_AGE_U19   => 0b100, // Juniors U19
        self::KEY_AGE_ELITE => 0b101  // Elite
    ];

    /**
     * Checks wether the Type provided as input is valid
     * 
     * @param int $type the value to check
     * 
     * @return bool Wether $type was valid or not
     */
    public static function validateType(int $type): bool
    {
        /**
         * check if type has bits in it that aren't used and therefore must be 0
         * 
         * move type 7 bits to the right 
         * all relevant bits that might not be 0 (in a valid type) are now shifted off
         * Then check wether value is 0 (right shifts do preserve the sign!)
         */
        if (($type >> 7) != 0)
            return false;

        // at this point we know that only the 7 least significant bits aren't 0
        // now slice the type into it's different meaningful parts
        // create discipline slice
        $discipline = $type >> 4;
        // create gender slice (be aware here we must filter for the right bits because the discipline bits aren't shifted out)
        $gender = ($type & 0b1000) >> 3;
        // create age-group slice
        $age_group = $type & 0b111;

        // check wether discipline is in list of valid disciplines
        if (!in_array($discipline, self::VALID_DISCIPLINES))
            return false;

        // gender is always correct (no check required) (a single bit can either be 0 or 1 :)

        // check wether the age group is in the list of valid age groups
        if (!in_array($age_group, self::VALID_AGES))
            return false;

        // all test were passed (elsewise the return statement would have been called earlier)
        return true;
    }
}
