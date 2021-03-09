<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/../data/utils.php');

/**
 * @OA\Schema(
 *   schema="person_get",
 *   description="The details of a person"
 * )
 */
class Person implements JsonSerializable
{
    /**
     * The person's ID
     * @var int
     * @OA\Property()
     */
    public $personId;
    /**
     * The person's last name or the organization's name
     * @var string
     * @OA\Property()
     */
    public $lastName;
    /**
     * The person's first and middle names, if applicable
     * @var string
     * @OA\Property()
     */
    public $firstAndMiddleNames;
    /**
     * The person's titles, if applicable
     * @var string
     * @OA\Property()
     */
    public $titles;
    /**
     * The person's credentials, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    public $credentials;
    /**
     * Whether the entity is an organization rather than an individual
     * @var bool
     * @OA\Property(default=false)
     */
    public $isOrganization;
    /**
     * The person's name formatted for conventional sorting
     * @var string
     * @OA\Property()
     */
    public $sortableName;
    /**
     * The person's name formatted for display
     * @var string
     * @OA\Property()
     */
    public $displayName;
    /**
     * The person's name formatted for abbreviated display
     * @var string
     * @OA\Property()
     */
    public $displayNameShort;
    /**
     * Any comments about the person or organation (e.g. for disambiguating people with the same name)
     * @var string
     * @OA\Property()
     */
    public $notes;

    public function __construct($data) {
        $this->personId = $data['PersonId'];
        $this->lastName = $data['LastName'];
        $this->firstAndMiddleNames = $data['FirstAndMiddleNames'];
        $this->titles = $data['Titles'];
        $this->credentials = $data['Credentials'];
        $this->isOrganization = (bool)$data['IsOrganization'];
        $this->sortableName = $data['SortableName'];
        $this->notes = $data['Notes'];

        if ($data['Titles']) {
            $this->displayName = $data['Titles'] . ' ';
        }
        else {
            $this->displayName = '';
        }

        if ($data['FirstAndMiddleNames']) {
            $this->displayName .= $data['FirstAndMiddleNames'] . ' ' . $data['LastName'];
            $this->displayNameShort = substr($data['FirstAndMiddleNames'], 0, 1) . '. ' . $data['LastName'];
        }
        else {
            $this->displayName = $data['LastName'];
            $this->displayNameShort = $data['LastName'];
        }

        if ($data['Credentials']) {
            $this->displayName .= ', ' . $data['Credentials'];
        }
    }

    public function jsonSerialize() {
        return [
            'personId' => $this->personId,
            'lastName' => $this->lastName,
            'firstAndMiddleNames' => $this->firstAndMiddleNames,
            'titles' => $this->titles,
            'credentials' => $this->credentials,
            'isOrganization' => $this->isOrganization,
            'sortableName' => $this->sortableName,
            'displayName' => $this->displayName,
            'displayNameShort' => $this->displayNameShort,
            'notes' => $this->notes
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="person_post",
 *   description="The details of a person"
 * )
 */
class PersonCreate {

    private $db;

    private $isValid = true;

    /**
     * The person's last name or the organization's name
     * @var string
     * @OA\Property()
     */
    private $lastName;
    /**
     * The person's first and middle names, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $firstAndMiddleNames;
    /**
     * The person's titles, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $titles;
    /**
     * The person's credentials, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $credentials;
    /**
     * Whether the person is an organization
     * @var boolean
     * @OA\Property(default=false)
     */
    private $isOrganization;
    /**
     * Any comments about the person or organization (e.g. for disambiguating people with the same name)
     * @var string
     * @OA\Property(default=null)
     */
    private $notes;

    /**
     * Create a new person for adding to the database.
     *
     * @param mysqli $db The database into which the object will be inserted.
     * @param array $post The contents of the $_POST variable to fill
     *                    in the person's details.
     */
    public function __construct($db, $post){
        $this->db = $db;
        if (isset($post['lastName']) && strlen($post['lastName']) > 0) {
            $this->lastName = $post['lastName'];
        }
        else {
            $this->isValid = false;
        }
        $this->firstAndMiddleNames = $post['firstAndMiddleNames'] ?? null;
        $this->titles = $post['titles'] ?? null;
        $this->credentials = $post['credentials'] ?? null;
        if (isset($post['isOrganization'])) {
            $this->isOrganization = (bool)$post['isOrganization'];
        }
        else {
            $this->isOrganization = false;
        }
        storeValueOrNull($post, 'notes', $this->notes);
    }

    /**
     * Attempt to insert the person into the database.
     *
     * @return int Appropriate HTTP response code.
     */
    public function insertIntoDatabase(&$newId) {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }

        $query = "INSERT INTO Persons " .
                    "(LastName, FirstAndMiddleNames, Titles, Credentials, IsOrganization, Notes) " .
                    "VALUES " .
                    "(?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssssis', $this->lastName, $this->firstAndMiddleNames,
            $this->titles, $this->credentials, $this->isOrganization, $this->notes);
        $success = $stmt->execute();

        $stmt->close();

        if ($success) {
            $newId = $this->db->insert_id;
        }
        else {
            throw new UnknownDatabaseException($this->db, "Error in PersonCreate->insertIntoDatabase");
        }

    }
}


/**
 * @OA\Schema(
 *   schema="person_put",
 *   description="The details of a person"
 * )
 */
class PersonUpdate {

    private $db;

    private $isValid = true;

    /**
     * The person's ID
     * @var int
     * @OA\Property()
     */
    private $personId;
    /**
     * The person's last or only name, or the organization's name
     * @var string
     * @OA\Property()
     */
    private $lastName;
    /**
     * The person's first and middle names, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $firstAndMiddleNames;
    /**
     * The person's titles, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $titles;
    /**
     * The person's credentials, if applicable
     * @var string
     * @OA\Property(default=null)
     */
    private $credentials;
    /**
     * Whether the entity is an organization rather than an individual
     * @var bool
     * @OA\Property(default=false)
     */
    private $isOrganization;
    /**
     * Any comments about the person or organization (e.g. for disambiguating people with the same name)
     * @var string
     * @OA\Property(default=null)
     */
    private $notes;

    /**
     * Create a new person for updating the database.
     */
    public function __construct($db, $put, $personId) {
        $this->db = $db;

        if (isset($put['personId']) && $put['personId'] == $personId && ((int)$personId) > 0) {
            $this->personId = $personId;
        }
        else {
            $this->isValid = false;
        }

        if (isset($put['lastName']) && strlen($put['lastName']) > 0) {
            $this->lastName = $put['lastName'];
        }
        else {
            $this->isValid = false;
        }
        $this->firstAndMiddleNames = $put['firstAndMiddleNames'] ?? null;
        $this->titles = $put['titles'] ?? null;
        $this->credentials = $put['credentials'] ?? null;
        if (isset($put['isOrganization'])) {
            $this->isOrganization = (bool)$put['isOrganization'];
        }
        else {
            $this->isOrganization = false;
        }
        storeValueOrNull($put, 'notes', $this->notes);
    }

    /**
     * Attempt to insert the person into the database.
     *
     * @return int Appropriate HTTP response code..
     */
    public function updateDatabase() {
        if (!$this->isValid) {
            http_response_code(400); // Bad request
            return;
        }

        $query = 'UPDATE Persons ' .
                    'SET ' .
                    'LastName = ?, FirstAndMiddleNames = ?, ' .
                    'Titles = ?, Credentials = ?, IsOrganization = ?, Notes = ? ' .
                    'WHERE PersonId = ?';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssssisi', $this->lastName,
            $this->firstAndMiddleNames, $this->titles,
            $this->credentials, $this->isOrganization, $this->notes, $this->personId);
        $success = $stmt->execute();

        if (!$success) {
            http_response_code(500); // Internal server error
            $stmt->close();
            throw new UnknownDatabaseException($this->db, "Error in PersonUpdate->updateDatabase");
        }
    }
}
