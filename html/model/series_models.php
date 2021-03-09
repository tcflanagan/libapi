<?php
require_once('book_models.php');

/**
 * @OA\Schema(
 *   schema="series_wob_get",
 *   description="The details of a series to which a book belongs"
 * )
 */
class SeriesWithoutBooks implements JsonSerializable {

    /**
     * The series's unique ID
     * @var int
     * @OA\Property()
     */
    public $seriesId;
    /**
     * The title of the series
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The subtitle of the series
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * The series's price (if it is sold as a set)
     * @var float
     * @OA\Property(default=null)
     */
    public $price;
    /**
     * The series's ISBN 13 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn13;
    /**
     * The series's ISBN 10 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn10;
    /**
     * The series's purchase URL (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $amazonUrl;
    /**
     * A book's volume number within a series
     * @var string
     * @OA\Property(default=1)
     */
    public $volume;

    public function __construct($data) {
        $this->seriesId = $data['SeriesId'];
        $this->title = $data['SeriesTitle'];
        $this->subtitle = $data['SeriesSubtitle'] ?? null;
        $this->price = (float)$data['SeriesPrice'] ?? 0.0;
        $this->isbn13 = $data['SeriesIsbn13'] ?? null;
        $this->isbn10 = $data['SeriesIsbn10'] ?? null;
        $this->amazonUrl = $data['SeriesAmazonUrl'] ?? null;
        $this->volume = $data['Volume'] ?? null;
    }

    public function addRow($data) {}

    public function jsonSerialize() {
        return [
            'seriesId' => $this->seriesId,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'price' => $this->price,
            'isbn13' => $this->isbn13,
            'isbn10' => $this->isbn10,
            'amazonUrl' => $this->amazonUrl,
            'volume' => $this->volume
        ];
    }
}


/**
 * @OA\Schema(
 *   schema="series_get",
 *   description="The details of a series"
 * )
 */
class Series extends SeriesWithoutBooks implements JsonSerializable {
    /**
     * The books in the series
     * @var BookWithoutSeries[]
     * @OA\Property()
     */
    public $books = array();

    public function __construct($data) {
        parent::__construct($data);
        $this->addRow($data);
    }

    public function addRow($data) {
        $bookId = $data['BookId'] ?? null;
        if ($bookId) {
            if (array_key_exists($bookId, $this->books)) {
                $this->books[$bookId]->addRow($data);
            }
            else {
                $this->books[$bookId] = new BookWithoutSeries($data);
            }
        }
    }

    public function jsonSerialize() {
        $output = parent::jsonSerialize();
        $output['books'] = array_values($this->books);
        return $output;
    }
}

/**
 * @OA\Schema(
 *   schema="series_post",
 *   description="The details of a series to create"
 * )
 */
class SeriesCreate
{
    public $db;
    public $isValid = true;

    /**
     * The title of the series
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The subtitle of the series
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * The series's list price (if it is sold as a set)
     * @var float
     * @OA\Property(default=null)
     */
    public $price;
    /**
     * The series's ISBN 10 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn10;
    /**
     * The series's ISBN 13 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn13;
    /**
     * The series's purchase URL (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $amazonUrl;

    /**
     * The books in the series
     * @var BookInSeries[]
     * @OA\Property()
     */
    public $books;

    public function __construct(mysqli $db, $data) {
        $this->db = $db;

        if (isset($data['title']) && strlen($data['title'])> 0) {
            $this->title = $data['title'];
        }
        else {
            $this->isValid = false;
        }
        $this->subtitle = $data['subtitle'] ?? null;
        if (isset($data['price'])) {
            $this->price = (float)$data['price'];
        }
        else {
            $this->price = 0.0;
        }
        $this->isbn10 = $data['isbn10'] ?? null;
        $this->isbn13 = $data['isbn13'] ?? null;
        $this->amazonUrl = $data['amazonUrl'] ?? null;
        $this->books = $data['books'] ?? null;
    }

    public function insertIntoDatabase(&$newId) {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }
        $query = "INSERT INTO Series
                  (Title, Subtitle, Price, Isbn10, Isbn13, AmazonUrl)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssdsss', $this->title, $this->subtitle, $this->price,
                          $this->isbn10, $this->isbn13, $this->amazonUrl);
        $result = $stmt->execute();
        if ($result === false) {
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in SeriesCreate->insertIntoDatabase");
        }
        $stmt->close();

        $newId = $this->db->insert_id;
        if ($this->books != null) {
            foreach ($this->books as $item) {
                $bookId = $item['bookId'];
                $volume = $item['volume'];
                $query = "INSERT INTO BookSeries VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $seriesId, $volume);
                $stmt->close();
            }
        }
    }
}

/**
 * @OA\Schema(
 *   schema="series_put",
 *   description="The details of a series to update"
 * )
 */
class SeriesUpdate
{
    public $db;
    public $isValid = true;

    /**
     * The unique ID of the series
     */
    public $seriesId;
    /**
     * The title of the series
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The subtitle of the series
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * The series's list price (if it is sold as a set)
     * @var float
     * @OA\Property(default=null)
     */
    public $price;
    /**
     * The series's ISBN 10 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn10;
    /**
     * The series's ISBN 13 (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $isbn13;
    /**
     * The series's purchase URL (if it is sold as a set)
     * @var string
     * @OA\Property(default=null)
     */
    public $amazonUrl;

    /**
     * The books in the series
     * @var BookInSeries[]
     * @OA\Property()
     */
    public $books;

    public function __construct(mysqli $db, $data, $seriesId) {
        $this->db = $db;

        if (isset($data['seriesId']) && $seriesId == $data['seriesId'] && ((int)$seriesId) > 0) {
            $this->seriesId = $seriesId;
        }
        else {
            $this->isValid = false;
        }

        if (isset($data['title']) && strlen($data['title'])> 0) {
            $this->title = $data['title'];
        }
        else {
            $this->isValid = false;
        }
        $this->subtitle = $data['subtitle'] ?? null;
        if (isset($data['price'])) {
            $this->price = (float)$data['price'];
        }
        else {
            $this->price = 0.0;
        }
        $this->isbn10 = $data['isbn10'] ?? null;
        $this->isbn13 = $data['isbn13'] ?? null;
        $this->amazonUrl = $data['amazonUrl'] ?? null;
        $this->books = $data['books'] ?? null;
    }

    public function updateDatabase() {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }

        $query = "UPDATE Series SET
                  Title = ?, Subtitle = ?, Price = ?,
                  Isbn10 = ?, Isbn13 = ?, AmazonUrl = ?
                  WHERE SeriesId = ?";
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            http_response_code(500);
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssdsssi', $this->title, $this->subtitle, $this->price,
                          $this->isbn10, $this->isbn13, $this->amazonUrl,
                          $this->seriesId);
        if ($this->db->errno) {
            http_response_code(500);
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in SeriesUpdate->updateDatabase: binding parameters for series update");
        }
        $result = $stmt->execute();
        if ($result === false) {
            http_response_code(500);
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in SeriesUpdate->updateDatabase: execution fail.");
        }

        $seriesId = $this->seriesId;
        if ($this->books != null) {
            $query = "DELETE FROM BookSeries WHERE SeriesId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $seriesId);
            $stmt->execute();
            $stmt->close();
            foreach ($this->books as $item) {
                $bookId = $item['bookId'];
                $volume = $item['volume'];
                $query = "INSERT INTO BookSeries VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                if ($stmt === false) {
                    http_response_code(500);
                    throw new DatabasePrepareException($query, $this->db,
                        "Error in SeriesUpdate->updateDatabase: binding params BookSeries");

                }
                $result = $stmt->bind_param('iii', $bookId, $seriesId, $volume);
                if (!$result) {
                    http_response_code(500);
                    $stmt->close();
                    throw new UnknownDatabaseException($this->db,
                        "Error in SeriesUpdate->updateDatabase: binding params BookSeries");
                }
                $result = $stmt->execute();
                if (!$result) {
                    http_response_code(500);
                    $stmt->close();
                    echo sprintf("INSERT INTO BookSeries VALUES (%d, %d, %d)", $seriesId, $bookId, $volume);
                    throw new UnknownDatabaseException($this->db,
                        "Error in SeriesUpdate->updateDatabase: inserting BookSeries");
                }
                $stmt->close();
            }
        }
    }
}

/**
 * @OA\Schema(
 *   schema="book_in_series",
 *   description="Keys for a book within a series"
 * )
 */
class BookInSeries
{
    /**
     * The ID of the book in the series
     * @var int
     * @OA\Property()
     */
    public $bookId;
    /**
     * The volume of the book within the series
     * @var int
     * @OA\Property()
     */
    public $volume;
}