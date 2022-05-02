<?php

/**
 * @package Database\Representatives
 */

// assign namespace
namespace db;

// import trait and interface
require_once("db_representatives_interface_trait.php");

// define aliases
use JsonSerializable;
use mysqli;

/** 
 * The class used to describe a user.
 */
class user implements JsonSerializable, RepresentativeInterface
{
    // use trait for basic functionality
    use RepresentativeTrait;

    // Keys used in data array
    /** @var string id of the user (unique identifier in database) */
    public const KEY_ID = "id";
    /** @var string name of the user */
    public const KEY_NAME = "name";
    /** @var string password of the user */
    public const KEY_PASSWORD = "password";
    /** @var string role of the user */
    public const KEY_ROLE = "role";


    /** @var array data stored in the user */
    protected $data = [
        self::KEY_ID => 0,
        self::KEY_NAME => "",
        self::KEY_PASSWORD => "",
        self::KEY_ROLE => 0
    ];

    // Errors
    /** @var int Error while parsing the id */
    const ERROR_ID = 1;
    /** @var int Error while parsing the username */
    const ERROR_NAME = 2;
    /** @var int Error while parsing the password */
    const ERROR_PASSWORD = 4;
    /** @var int Error while parsing the password */
    const ERROR_ROLE = 8;

    /**
     * Constructor 
     * 
     * @param int $ID Id of the user
     * @param string $name The username
     * @param string $password The password
     */
    function __construct(
        int $ID = null,
        string $name = null,
        string $password = null
    ) {
        // this strange way of setting the defaults is used so one can just null all unused fields during construction
        // not relay performant but makes debugging a bit easier
        $this->data[self::KEY_ID]       = $ID       ?? 0;
        $this->data[self::KEY_NAME]     = $name     ?? "";
        $this->data[self::KEY_PASSWORD] = $password ?? "";
    }

    // explained in RepresentativeInterface
    public function updateId(int $ID): self
    {
        $this->data[self::KEY_ID] = $ID;
        return $this;
    }

    // explained in RepresentativeInterface
    public function makeDbReady(mysqli $db): int
    {
        // variable for error messages
        $error = 0;

        // check if invalid characters are present in string, if so remove them and add error
        if (strcmp($this->{self::KEY_NAME}, $db->real_escape_string($this->{self::KEY_NAME})) != 0) {
            $this->date[self::KEY_NAME] = $db->real_escape_string($this->{self::KEY_NAME});
            $error |= self::ERROR_NAME;
        }
        if (strcmp($this->{self::KEY_PASSWORD}, $db->real_escape_string($this->{self::KEY_PASSWORD})) != 0) {
            $this->date[self::KEY_PASSWORD] = $db->real_escape_string($this->{self::KEY_PASSWORD});
            $error |= self::ERROR_PASSWORD;
        }

        // won't check id, it isn't used when writing to db and if reading from db and id is out of range nothing happens

        // role is >= 0 b< design
        if ($this->{self::KEY_ROLE} < 0) {
            $data[self::KEY_ROLE] = 0;
            $error |= self::ERROR_ROLE;
        }

        // mark user as ready for database
        $this->isDbReady = true;

        // return errors
        return $error;
    }

    /**
     * Parse strings into the user.
     * NO CHECKS ARE DONE WETHER THE VALUES ARE USEFUL OR NOT, JUST TYPE-SAFETY.
     * 
     * @param ?string $ID Id of the user
     * @param ?string $name The name of the user
     * @param ?string $password The password of the user
     * @param ?mysqli $db Database to make compatible with
     * 
     * @return int the errors occurred during parsing
     */
    public function parse(
        ?string $ID = "",
        ?string $name = "",
        ?string $password = "",
        ?string $role = "",
        ?mysqli $db = null
    ): int {
        // after parsing no user isDbReady
        $this->isDbReady = false;

        // variable for error
        $error = 0;

        // write string
        $this->data[self::KEY_NAME] = strval($name);
        $this->data[self::KEY_PASSWORD] = strval($password);

        // parsing integers
        $this->data[self::KEY_ID] = intval($ID);
        $this->data[self::KEY_ROLE] = intval($role);

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
            self::KEY_NAME => $this->{self::KEY_NAME},
            self::KEY_PASSWORD => $this->{self::KEY_PASSWORD},
            self::KEY_ROLE => $this->{self::KEY_ROLE}
        ];
    }

    /**
     * Checks wether the user passed as an argument is equal to this user
     * for more details see static usercmp();
     * 
     * @param self $user the user to check against
     * 
     * @return int 0 if they don't match, 1 if nam and password match, 2 if name, password and id match.
     * 
     * @see self::usercmp
     */
    public function isEqual(self $user): int
    {
        return self::usercmp($user, $this);
    }

    /**
     * Compares two users and decides wether they are identical or not.
     * If they aren't identic 0 will be returned.
     * If they match in name and password 1 will be returned.
     * If they match in every element 2 will be returned.
     * 
     * @param self $user1 The first user
     * @param self $user2 The second user
     * 
     * @return int 0 if they don't match, 1 if nam and password match, 2 if name, password and id match.
     */
    public static function usercmp(self $user1, self $user2): int
    {
        // check if name and password match (if they don't the user isn't equal and 0 is returned)
        if ($user1->{self::KEY_NAME} != $user2->{self::KEY_NAME} || $user1->{self::KEY_PASSWORD} != $user2->{self::KEY_PASSWORD})
            return 0;

        // now check if id does match (if it does return 2 else proceed and return 1)
        if ($user1->{self::KEY_ID} == $user2->{self::KEY_ID})
            return 2;

        // at this point name and password match but id doesn't so return 1
        return 1;
    }
}
