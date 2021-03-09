<?php

/**
 * @OA\Schema(
 *   schema="tag_get",
 *   description="The details of a tag"
 * )
 */
class Tag implements JsonSerializable
{
    /**
     * The tag's ID
     * @var int
     * @OA\Property()
     */
    public $tagId;
    /**
     * The tag's owning genre's ID
     * @var int
     * @OA\Property()
     */
    public $genreId;
    /**
     * The tag's name
     * @var string
     * @OA\Property()
     */
    public $name;

    public function __construct($data) {
        $this->tagId = $data['TagId'];
        $this->genreId = $data['GenreId'];
        $this->name = $data['Name'];
    }

    public function jsonSerialize() {
        return [
            'tagId' => $this->tagId,
            'genreId' => $this->genreId,
            'name' => $this->name
        ];
    }
}
/**
 * @OA\Schema(
 *   schema="tag_post",
 *   description="The details for a new tag"
 * )
 */
class TagCreate
{
    public $db;
    public $isValid = true;

    /**
     * The tag's owning genre's ID
     * @var int
     * @OA\Property()
     */
    public $genreId;
    /**
     * The new tag's name
     * @var string
     * @OA\Property()
     */
    public $name;

    public function __construct(mysqli $db, $data) {
        $this->db = $db;

        if (isset($data['genreId']) && ((int)$data['genreId']) > 0) {
            $this->genreId = (int)$data['genreId'];
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
    }

    public function insertIntoDatabase(int &$newId) {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }

        $query = 'INSERT INTO Tags (GenreId, Name) VALUES (?, ?)';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('is', $this->genreId, $this->name);
        $response = $stmt->execute();

        if ($response) {
            $newId = $this->db->insert_id;
            $stmt->close();
        }
        else {
            $stmt->close();
            http_response_code(500); // Internal Server Error
            throw new UnknownDatabaseException($this->db, "Error in TagCreate->insertIntoDatabase");
        }
    }
}

/**
 * @OA\Schema(
 *   schema="tag_put",
 *   description="The new details for the tag"
 * )
 */
class TagUpdate
{
    public $db;
    public $isValid = true;

    /**
     * The ID of the tag to update
     * @var int
     * @OA\Property()
     */
    public $tagId;
    /**
     * The ID of the tag's owning genre
     * @var int
     * @OA\Property()
     */
    public $genreId;
    /**
     * The name of the tag
     * @var string
     * @OA\Property()
     */
    public $name;

    public function __construct(mysqli $db, $data, int $tagId) {
        $this->db = $db;

        if (isset($data['tagId']) && $data['tagId'] == $tagId && ((int)$tagId) > 0) {
            $this->tagId = (int)$tagId;
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['genreId']) && ((int)$data['genreId']) > 0) {
            $this->genreId = (int)$data['genreId'];
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
    }

    public function updateDatabase() {
        if (!$this->isValid) {
            http_response_code(400);
            return;
        }

        $query = 'UPDATE Tags SET GenreId = ?, Name = ? WHERE TagId = ?';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('isi', $this->genreId, $this->name, $this->tagId);
        $response = $stmt->execute();

        if (!$response) {
            http_response_code(500); // Internal Service Error
            $stmt->close();
            throw new UnknownDatabaseException($this->db, "Error in TagUpdate->updateDatabase");
        }

        $stmt->close();
    }
}
