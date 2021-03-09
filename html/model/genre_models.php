<?php

include_once('tag_models.php');

/**
 * @OA\Schema(
 *   schema="genre_get",
 *   description="The details of a genre"
 * )
 */
class Genre implements JsonSerializable {

    /**
     * The genre's ID
     * @var int
     * @OA\Property()
     */
    public $genreId;
    /**
     * The genre's name
     * @var string
     * @OA\Property()
     */
    public $name;
    /**
     * A shorter genre name for abbreviated views
     * @var string
     * @OA\Property()
     */
    public $shortName;

    /**
     * List of tags under the genre
     * @var Tag[]
     * @OA\Property()
     */
    public $tags = array();

    public function __construct($data) {
        $this->genreId = $data['GenreId'];
        $this->name = $data['Name'];
        $this->shortName = $data['ShortName'];

        if (isset($data['TagId'])) {
            $tagId = $data['TagId'];
            $this->tags[$tagId] = new Tag([
                'TagId' => $tagId,
                'GenreId' => $data['GenreId'],
                'Name' => $data['TagName']
            ]);
        }
    }

    public function addRow($data) {
        $this->tags[$data['TagId']] = new Tag([
            'TagId' => $data['TagId'],
            'GenreId' => $data['GenreId'],
            'Name' => $data['TagName']
        ]);
    }

    public function jsonSerialize() {
        return [
            'genreId' => $this->genreId,
            'name' => $this->name,
            'shortName' => $this->shortName,
            'tags' => array_values($this->tags)
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="genre_post",
 *   description="The details of the genre to add"
 * )
 */
class GenreCreate
{
    private $db;
    private $isValid = true;

    /**
     * The name of the genre to add
     * @var string
     * @OA\Property()
     */
    private $name;
    /**
     * A shorter genre name for abbreviated views
     * @var string
     * @OA\Property()
     */
    private $shortName;

    /**
     * A list of new tag names to add under the new genre
     * @var string[]
     * @OA\Property(example="[""tag1"", ""tag2"", ""tag3""]")
     */
    // 1-D array, in the pattern [TagName1, TagName2, ...]
    private $tags = array();

    public function __construct($db, $data)
    {
        $this->db = $db;

        if (isset($data['name']) && strlen($data['name']) > 0) {
            $this->name = $data['name'];
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['shortName']) && strlen($data['shortName']) > 0) {
            $this->shortName = $data['shortName'];
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['tags'])) {
            $this->tags = array();
            foreach ($data['tags'] as $tag) {
                $this->tags[] = $tag['name'];
            }
        }
    }

    public function insertIntoDatabase(int &$newId) {
        if (!$this->isValid) {
            http_response_code(400);
            return;
        }

        $query = 'INSERT INTO Genres (Name, ShortName) VALUES (?, ?)';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ss', $this->name, $this->shortName);
        $result = $stmt->execute();

        if (!$result) {
            http_response_code(500);
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in GenreCreate->insertIntoDatabase: Could not create genre.");
        }
        $stmt->close();

        $newId = $this->db->insert_id;

        if ($this->tags && count($this->tags) > 0) {
            $genreId = $this->db->insert_id;
            $tagArray = array();
            $lastTag = $this->tags[count($this->tags) - 1];
            $fmt = '';
            $query = 'INSERT INTO Tags (GenreId, Name) VALUES ';
            foreach ($this->tags as $tag) {
                $tagArray[] = $genreId;
                $tagArray[] = $tag;
                $query .= $tag != $lastTag ? '(?, ?), ' : '(?, ?)';
                $fmt .= 'is';
            }
            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                throw new DatabasePrepareException($query, $this->db);
            }
            $stmt->bind_param($fmt, ...$tagArray);
            $result = $stmt->execute();

            if (!$result) {
                http_response_code(500);
                $stmt->close();
                throw new UnknownDatabaseException($this->db,
                    "Error in CreateGenre->insertIntoDatabase: Could not create tags.");
            }
            $stmt->close();
        }
    }
}

/**
 * @OA\Schema(
 *   schema="genre_put",
 *   description="The details of the genre to update; Adding/removing tags must be done through the Tags api"
 * )
 */
class GenreUpdate
{
    private $db;
    private $isValid = true;

    /**
     * The ID of the genre to update
     * @var int
     * @OA\Property()
     */
    private $genreId;
    /**
     * The new name for the genre
     * @var string
     * @OA\Property()
     */
    private $name;
    /**
     * A shorter genre name for abbreviated views
     * @var string
     * @OA\Property()
     */
    private $shortName;

    public function __construct($db, $data, $genreId)
    {
        $this->db = $db;

        if (isset($data['genreId']) && $data['genreId'] == $genreId && ((int)$genreId) > 0) {
            $this->genreId = (int)$genreId;
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['name']) && strlen($data['name']) > 0) {
            $this->name = $data['name'];
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['shortName']) && strlen($data['shortName']) > 0) {
            $this->shortName = $data['shortName'];
        }
        else {
            $this->isValid = false;
        }
    }

    public function updateDatabase() {
        if (!$this->isValid) {
            http_response_code(400);
            return;
        }

        $query = 'UPDATE Genres SET Name = ?, ShortName = ? WHERE GenreId = ?';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            http_response_code(500);
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssi', $this->name, $this->shortName, $this->genreId);
        $result = $stmt->execute();

        if (!$result) {
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in GenreUpdate->updateDatabase");
        }
        $stmt->close();
    }
}
?>