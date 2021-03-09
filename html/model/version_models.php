<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/../data/utils.php');

/**
 * @OA\Schema(
 *   schema="version_get",
 *   description="The details of a version/edition of a book"
 * )
 */
class Version implements JsonSerializable
{
    /**
     * The version's unique ID
     * @var int
     * @OA\Property()
     */
    public $versionId;
    /**
     * The ID of the owning book
     * @var int
     * @OA\Property()
     */
    public $bookId;
    /**
     * The book's edition
     * @var int
     * @OA\Property(default=1)
     */
    public $edition;
    /**
     * The book's publication year
     * @var int
     * @OA\Property()
    */
    public $year;
    /**
     * The book's publisher
     * @var string
     * @OA\Property(default=null)
     */
    public $publisher;
    /**
     * The book's publication location
     * @var string
     * @OA\Property(default=null)
     */
    public $location;
    /**
     * The book's ISBN 10 identification
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn10;
    /**
     * The book's ISBN 13 identification
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn13;
    /**
     * The book's list price/cost to replace
     * @var float
     * @OA\Property()
     */
    public $price;
    /**
     * The number of copies in stock
     * @var int
     * @OA\Property(default=1)
     */
    public $quantity;
    /**
     * A URL where the book is sold
     * @var string
     * @OA\Property(default=null)
     */
    public $url;

    /**
     * Whether the version is hardcover
     * @var bool
     * @OA\Property(default=true)
     */
    public $isHardcover;
    /**
     * Whether the version is paperback
     * @var bool
     * @OA\Property(default=false)
     */
    public $isPaperback;
    /**
     * Whether the version is mass-market paperback
     * @var bool
     * @OA\Property(default=false)
     */
    public $isMMPaperback;
    /**
     * Whether the version is an ebook
     * @var bool
     * @OA\Property(default=false)
     */
    public $isEbook;
    /**
     * Whether the version is leather-bound
     * @var bool
     * @OA\Property(default=false)
     */
    public $isLeatherbound;

    /**
     * Whether the book is a textbook (meant for classroon instruction)
     * @var bool
     * @OA\Property(default=false)
     */
    public $isTextbook;
    /**
     * Whether the version is hand-bound
     * @var bool
     * @OA\Property(default=false)
     */
    public $isHandBound;

    /**
     * Whether details here come from multiple versions
     * @var bool
     * @OA\Property(default=false)
     */
    public $detailsMixed;

    /**
     * Any general comments about the version
     * @var string
     * @OA\Property(default=null)
     */
    public $notes;

    public function __construct($data) {
        $this->versionId = (int)$data['VersionId'];
        $this->bookId = (int)$data['BookId'];
        $this->edition = (int)$data['Edition'];
        $this->year = (int)$data['Year'];
        $this->publisher = $data['Publisher'];
        $this->location = $data['Location'];
        $this->isbn10 = $data['Isbn10'];
        $this->isbn13 = $data['Isbn13'];
        $this->price = (float)$data['Price'];
        $this->quantity = (int)$data['Quantity'];
        $this->url = $data['Url'];

        $this->isHardcover = (bool)$data['IsHardcover'];
        $this->isPaperback = (bool)$data['IsPaperback'];
        $this->isMMPaperback = (bool)$data['IsMMPaperback'];
        $this->isEbook = (bool)$data['IsEbook'];
        $this->isLeatherbound = (bool)$data['IsLeatherbound'];

        $this->isTextbook = (bool)$data['IsTextbook'];
        $this->isHandBound = (bool)$data['IsHandBound'];

        $this->detailsMixed = (bool)$data['DetailsMixed'];

        $this->notes = $data['Notes'];
    }

    public function jsonSerialize() {

        return [
            'versionId' => $this->versionId,
            'bookId' => $this->bookId,
            'edition' => $this->edition,
            'year' => $this->year,
            'publisher' => $this->publisher,
            'location' => $this->location,
            'isbn10' => $this->isbn10,
            'isbn13' => $this->isbn13,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'url' => $this->url,
            'isHardcover' => $this->isHardcover,
            'isPaperback' => $this->isPaperback,
            'isMMPaperback' => $this->isMMPaperback,
            'isLeatherbound' => $this->isLeatherbound,
            'isEbook' => $this->isEbook,
            'isHandBound' => $this->isHandBound,
            'isTextbook' => $this->isTextbook,
            'detailsMixed' => $this->detailsMixed
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="version_post",
 *   description="The details of a version/edition of a book"
 * )
 */
class VersionCreate
{
    public $db;
    public $isValid = true;

    /**
     * The ID of the owning book
     * @var int
     * @OA\Property()
     */
    public $bookId;

    /**
     * The book's edition
     * @var int
     * @OA\Property(default=1)
     */
    public $edition;
    /**
     * The book's publication year
     * @var int
     * @OA\Property()
    */
    public $year;
    /**
     * The book's publisher
     * @var string
     * @OA\Property(default=null)
     */
    public $publisher;
    /**
     * The book's publication location
     * @var string
     * @OA\Property(default=null)
     */
    public $location;
    /**
     * The book's ISBN 10 identification
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn10;
    /**
     * The book's ISBN 13 identification
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn13;
    /**
     * The book's list price/cost to replace
     * @var float
     * @OA\Property()
     */
    public $price;
    /**
     * The number of copies in stock
     * @var int
     * @OA\Property(default=1)
     */
    public $quantity;
    /**
     * A URL where the book is sold
     * @var string
     * @OA\Property(default=null)
     */
    public $url;

    /**
     * Whether the version is hardcover
     * @var bool
     * @OA\Property(default=true)
     */
    public $isHardcover;
    /**
     * Whether the version is paperback
     * @var bool
     * @OA\Property(default=false)
     */
    public $isPaperback;
    /**
     * Whether the version is mass-market paperback
     * @var bool
     * @OA\Property(default=false)
     */
    public $isMMPaperback;
    /**
     * Whether the version is an ebook
     * @var bool
     * @OA\Property(default=false)
     */
    public $isEbook;
    /**
     * Whether the version is leather-bound
     * @var bool
     * @OA\Property(default=false)
     */
    public $isLeatherbound;

    /**
     * Whether the book is a textbook (meant for classroon instruction)
     * @var bool
     * @OA\Property(default=false)
     */
    public $isTextbook;
    /**
     * Whether the version is hand-bound
     * @var bool
     * @OA\Property(default=false)
     */
    public $isHandBound;
    /**
     * Whether details here come from multiple versions
     * @var bool
     * @OA\Property(default=false)
     */
    public $detailsMixed;

    /**
     * Any general comments about the version
     * @var string
     * @OA\Property(default=null)
     */
    public $notes;

    function __construct(mysqli $db, $data) {
        $this->db = $db;

        if (isset($data['bookId']) && is_int($data['bookId'])) {
            $this->bookId = $data['bookId'];
        }
        else {
            $this->isValid = false;
        }

        $this->edition = (int)$data['edition'] ?? 1;
        $this->year = (int)$data['year'] ?? null;
        $this->publisher = $data['publisher'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->isbn10 = $data['isbn10'] ?? null;
        $this->isbn13 = $data['isbn13'] ?? null;

        if (isset($data['price'])) {
            $this->price = (float)$data['price'];
        }
        else {
            $this->price = 0.0;
        }
        $this->quantity = (int)$data['quantity'] ?? 1;
        $this->url = $data['url'] ?? null;

        $this->isHardcover = (bool)$data['isHardcover'];
        $this->isPaperback = (bool)$data['isPaperback'];
        $this->isMMPaperback = (bool)$data['isMMPaperback'];
        $this->isEbook = (bool)$data['isEbook'];
        $this->isLeatherbound = (bool)$data['isLeatherbound'];

        $this->isHandBound = (bool)$data['isHandBound'];
        $this->isTextbook = (bool)$data['isTextbook'];

        $this->detailsMixed = (bool)$data['detailsMixed'];

        storeValueOrNull($data, 'notes', $this->notes);
    }

    public function insertIntoDatabase() {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }
        $query = 'INSERT INTO Versions
                  (BookId, Edition, Year,
                   Publisher, Location, Isbn10, Isbn13,
                   Price, Quantity, Url,
                   IsHardcover, IsPaperback, IsMMPaperback, IsEbook,
                   IsLeatherbound, IsHandBound, IsTextbook, DetailsMixed, Notes)
                   VALUES
                   (?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('iiissssdisiiiiiiiis',
                          $this->bookId, $this->edition, $this->year,
                          $this->publisher, $this->location, $this->isbn10, $this->isbn13,
                          $this->price, $this->quantity, $this->url,
                          $this->isHardcover, $this->isPaperback, $this->isMMPaperback, $this->isEbook,
                          $this->isLeatherbound, $this->isHandBound, $this->isTextbook,
                          $this->detailsMixed, $this->notes);
        $result = $stmt->execute();
        if ($result === false) {
            //$stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in VersionCreate->insertIntoDatabase");
        }
        $stmt->close();
    }
}
