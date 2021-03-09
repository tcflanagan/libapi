<?php

require_once('person_models.php');
require_once('genre_models.php');
require_once('series_models.php');
require_once('version_models.php');

/**
 * @OA\Schema(
 *   schema="book_wos_get",
 *   description="The details of a book inside a series"
 * )
 */
class BookWithoutSeries implements JsonSerializable
{
    /**
     * The book's unique ID
     * @var int
     * @OA\Property()
     */
    public $bookId;
    /**
     * The book's title
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The book's subtitle
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * An alternate title for the book
     * @var string
     * @OA\Property(default=null)
     */
    public $alternateTitle;
    /**
     * Any comments about the book
     * @var string
     * @OA\Property(default=null)
     */
    public $comments;
    /**
     * The book's authors
     * @var Person[]
     * @OA\Property(default=null)
     */
    public $authors = array();
    /**
     * The book's translators
     * @var Person[]
     * @OA\Property(default=null)
     */
    public $translators = array();
    /**
     * The book's editors
     * @var Person[]
     * @OA\Property(default=null)
     */
    public $editors = array();
    /**
     * The book's genres
     * @var Genre[]
     * @OA\Property(default=null)
     */
    public $genres = array();
    /**
     * The book's full title for display
     * @var string
     * @OA\Property()
     */
    public $fullTitle;
    /**
     * The book's title for easy sorting
     * @var string
     * @OA\Property()
     */
    public $sortableTitle;
    /**
     * The book's volume within a series
     * @var int
     * @OA\Property()
     */
    public $volume;

    /**
     * The available versions/editions of the book
     * @var Version[]
     * @OA\Property()
     */
    public $versions = array();

    public function __construct($data) {
        $this->bookId = $data['BookId'];
        $this->title = $data['Title'];
        $this->subtitle = $data['Subtitle'];
        $this->alternateTitle = $data['AlternateTitle'];
        $this->comments = $data['Comments'];
        $this->fullTitle = $data['FullTitle'];
        $this->sortableTitle = $data['SortableTitle'];
        $this->volume = $data['Volume'] ?? null;

        $this->addRow($data);
    }

    public function addRow($data) {

        if ($data['AuthorId'] && !array_key_exists($data['AuthorId'], $this->authors)) {
            $this->authors[$data['AuthorId']] = new Person([
                'PersonId' => $data['AuthorId'],
                'LastName' => $data['AuthorLastName'],
                'FirstAndMiddleNames' => $data['AuthorFirstAndMiddleNames'],
                'Titles' => $data['AuthorTitles'],
                'Credentials' => $data['AuthorCredentials'],
                'IsOrganization' => $data['AuthorIsOrganization'],
                'SortableName' => $data['AuthorSortableName'],
                'Notes' => $data['AuthorNotes']
            ]);
        }

        if ($data['EditorId'] && !array_key_exists($data['EditorId'], $this->editors)) {
            $this->editors[$data['EditorId']] = new Person([
                'PersonId' => $data['EditorId'],
                'LastName' => $data['EditorLastName'],
                'FirstAndMiddleNames' => $data['EditorFirstAndMiddleNames'],
                'Titles' => $data['EditorTitles'],
                'Credentials' => $data['EditorCredentials'],
                'IsOrganization' => $data['EditorIsOrganization'],
                'SortableName' => $data['EditorSortableName'],
                'Notes' => $data['EditorNotes']
            ]);
        }

        if ($data['TranslatorId'] && !array_key_exists($data['TranslatorId'], $this->translators)) {
            $this->translators[$data['TranslatorId']] = new Person([
                'PersonId' => $data['TranslatorId'],
                'LastName' => $data['TranslatorLastName'],
                'FirstAndMiddleNames' => $data['TranslatorFirstAndMiddleNames'],
                'Titles' => $data['TranslatorTitles'],
                'Credentials' => $data['TranslatorCredentials'],
                'IsOrganization' => $data['TranslatorIsOrganization'],
                'SortableName' => $data['TranslatorSortableName'],
                'Notes' => $data['TranslatorNotes']
            ]);
        }

        if ($data['GenreId']) {
            $genreId = $data['GenreId'];
            if (!array_key_exists($genreId, $this->genres)) {
                $this->genres[$genreId] = new Genre([
                    'GenreId' => $data['GenreId'],
                    'Name' => $data['GenreName'],
                    'ShortName' => $data['GenreShortName']
                ]);
            }
            if ($data['TagId']) {
                $tagId = $data['TagId'];
                if (!array_key_exists($tagId, $this->genres[$genreId]->tags)) {
                    $this->genres[$genreId]->addRow($data);
                }
            }
        }

        if (array_key_exists('VersionId', $data) &&
                !array_key_exists($data['VersionId'], $this->versions)) {
            $this->versions[$data['VersionId']] = new Version($data);
        }
    }

    public function jsonSerialize() {

        return [
            'bookId' => $this->bookId,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'alternateTitle' => $this->alternateTitle,
            'comments' => $this->comments,
            'authors' => array_values($this->authors),
            'editors' => array_values($this->editors),
            'translators' => array_values($this->translators),
            'fullTitle' => $this->fullTitle,
            'sortableTitle' => $this->sortableTitle,
            'genres' => array_values($this->genres),
            'volume' => $this->volume,
            'versions' => array_values($this->versions)
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="book_get",
 *   description="The details of a book"
 * )
 */
class Book extends BookWithoutSeries implements JsonSerializable {
    /**
     * The series to which the book belongs
     * @var SeriesWithoutBooks[]
     * @OA\Property()
     */
    public $series = array();

    public function __construct($data) {
        parent::__construct($data);
        $this->addRow($data);
    }

    public function addRow($data) {
        parent::addRow($data);
        $seriesId = $data['SeriesId'] ?? null;
        if ($seriesId){
            if (array_key_exists($seriesId, $this->series)) {
                $this->series[$seriesId]->addRow($data);
            }
            else {
                $this->series[$seriesId] = new SeriesWithoutBooks($data);
            }
        }
    }

    public function jsonSerialize() {
        $output = parent::jsonSerialize();
        $output['series'] = array_values($this->series);
        return $output;
    }
}

/**
 * @OA\Schema(
 *   schema="book_post",
 *   description="The details of a book to create"
 * )
 */
class BookCreate
{
    public $db;
    public $isValid = true;

    /**
     * The book's title
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The book's subtitle
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * An alternate title for the book
     * @var string
     * @OA\Property(default=null)
     */
    public $alternateTitle;
    /**
     * Any comments about the book
     * @var string
     * @OA\Property(default=null)
     */
    public $comments;
    /**
     * The personId values for the book's authors
     * @var int[]
     * @OA\Property(default=null)
     */
    public $authors = array();
    /**
     * The personId values for the book's editors
     * @var int[]
     * @OA\Property(default=null)
     */
    public $editors = array();
    /**
     * The personId values for the book's translators
     * @var int[]
     * @OA\Property(default=null)
     */
    public $translators = array();
    /**
     * The genres and tags describing the book
     * @var GenreInBook[]
     * @OA\Property(default=null)
     */
    public $genres = array();
    /**
     * The IDs of the series to which the book belongs
     * @var SeriesInBook[]
     * @OA\Property(default=null)
     */
    public $series = array();
    /**
     * The available versions/editions of the book
     * @var VersionCreate[]
     * @OA\Property(default=null)
     */
    public $versions = array();

    function __construct(mysqli $db, $data)
    {
        $this->db = $db;

        if (isset($data['title']) && strlen($data['title']) > 0) {
            $this->title = $data['title'];
        }
        else {
            $this->isValid = false;
        }
        $this->subtitle = $data['subtitle'] ?? null;
        if (isset($data['alternateTitle']) && $data['alternateTitle']) {
            $this->alternateTitle = $data['alternateTitle'];
        }
        else {
            $this->alternateTitle = null;
        }
        if (isset($data['comments']) && $data['comments']) {
            $this->comments = $data['comments'];
        }
        else {
            $this->comments = null;
        }

        $this->authors = $data['authors'] ?? null;
        $this->editors = $data['editors'] ?? null;
        $this->translators = $data['translators'] ?? null;

        $this->series = $data['series'] ?? null;

        $this->genres = $data['genres'] ?? null;
        $this->tags = $data['tags'] ?? null;

        if (isset($data['versions'])) {
            foreach($data['versions'] as $version) {
                $this->versions[] = new VersionCreate($db, $version);
            }
        }
        else {
            $this->versions = null;
        }
    }

    public function insertIntoDatabase(&$newId) {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }
        $query = 'INSERT INTO Books
                  (Title, Subtitle, AlternateTitle, Comments)
                   VALUES (?, ?, ?, ?)';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssss',
                          $this->title, $this->subtitle, $this->alternateTitle, $this->comments);
        $result = $stmt->execute();
        if ($result === false) {
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in BookCreate->insertIntoDatabase");
        }
        $stmt->close();

        $bookId = $this->db->insert_id;
        $newId = $bookId;
        if ($this->authors != null) {
            $i = 0;
            foreach ($this->authors as $item) {
                $query = "INSERT INTO BookAuthors VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->editors != null) {
            $i = 0;
            foreach ($this->editors as $item) {
                $query = "INSERT INTO BookEditors VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->translators != null) {
            $i = 0;
            foreach ($this->translators as $item) {
                $query = "INSERT INTO BookTranslators VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->genres != null) {
            foreach ($this->genres as $item) {
                $genreId = $item['genreId'];
                $tagId = $item['tagId'];
                $query = "INSERT INTO BookGenres " .
                            "(BookId, GenreId, TagId) " .
                            "VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $genreId, $tagId);
                $stmt->execute();
                $stmt->close();
            }
        }
        if ($this->series != null) {
            foreach ($this->series as $item) {
                $seriesId = $item['seriesId'];
                $volume = $item['volume'];
                $query = "INSERT INTO BookSeries VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $seriesId, $volume);
                $stmt->execute();
                $stmt->close();
            }
        }
        if ($this->versions != null) {
            foreach ($this->versions as $version) {
                $version->bookId = $bookId;
                $version->insertIntoDatabase();
            }
        }
    }
}

/**
 * @OA\Schema(
 *   schema="book_put",
 *   description="The new details for a book"
 * )
 */
class BookUpdate
{
    public $db;
    public $isValid = true;

    /**
     * The ID of the book to update
     * @var int
     * @OA\Property()
     */
    public $bookId;
    /**
     * The book's title
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The book's subtitle
     * @var string
     * @OA\Property(default=null)
     */
    public $subtitle;
    /**
     * Any comments about the book
     * @var string
     * @OA\Property(default=null)
     */
    public $alternateTitle;
    /**
     * Any comments about the book
     * @var string
     * @OA\Property(default=null)
     */
    public $comments;
    /**
     * The personId values for the book's authors
     * @var int[]
     * @OA\Property(default=null)
     */
    public $authors = array();
    /**
     * The personId values for the book's editors
     * @var int[]
     * @OA\Property(default=null)
     */
    public $editors = array();
    /**
     * The personId values for the book's translators
     * @var int[]
     * @OA\Property(default=null)
     */
    public $translators = array();
    /**
     * The genres and tags describing the book
     * @var GenreInBook[]
     * @OA\Property(default=null)
     */
    public $genres = array();
    /**
     * The IDs of the series to which the book belongs
     * @var SeriesInBook[]
     * @OA\Property(default=null)
     */
    public $series = array();
    /**
     * The available versions/editions of the book
     * @var VersionCreate[]
     * @OA\Property(default=null)
     */
    public $versions = array();

    function __construct($db, $data, $bookId)
    {
        $this->db = $db;

        if (isset($data['bookId']) && $bookId == $data['bookId'] && ((int)$bookId) > 0) {
            $this->bookId = $bookId;
        }
        else {
            $this->isValid = false;
        }
        if (isset($data['title']) && strlen($data['title'])) {
            $this->title = $data['title'];
        }
        else {
            $this->isValid = false;
        }
        $this->subtitle = $data['subtitle'] ?? null;
        if (isset($data['alternateTitle']) && $data['alternateTitle']) {
            $this->alternateTitle = $data['alternateTitle'];
        }
        else {
            $this->alternateTitle = null;
        }
        if (isset($data['comments']) && $data['comments']) {
            $this->comments = $data['comments'];
        }
        else {
            $this->comments = null;
        }

        $this->authors = $data['authors'] ?? null;
        $this->editors = $data['editors'] ?? null;
        $this->translators = $data['translators'] ?? null;

        $this->genres = $data['genres'] ?? null;
        $this->tags = $data['tags'] ?? null;

        $this->series = $data['series'] ?? null;

        if (isset($data['versions'])) {
            foreach ($data['versions'] as $version) {
                $this->versions[] = new VersionCreate($db, $version);
            }
        }
        else {
            $this->versions = null;
        }
    }

    public function updateDatabase() {
        if (!$this->isValid) {
            http_response_code(400); // Bad Request
            return;
        }
        $query = 'UPDATE Books SET
                  Title = ?, Subtitle = ?, AlternateTitle = ?, Comments = ?
                  WHERE BookId = ?';
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            http_response_code(500);
            throw new DatabasePrepareException($query, $this->db);
        }
        $stmt->bind_param('ssssi', $this->title, $this->subtitle,
                          $this->alternateTitle, $this->comments,
                          $this->bookId);

        if ($this->db->errno) {
            http_response_code(500);
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in BookUpdate->updateDatabase: binding parameters for book insert");
        }

        $result = $stmt->execute();

        if (!$result) {
            http_response_code(500);
            $stmt->close();
            throw new UnknownDatabaseException($this->db,
                "Error in BookUpdate->updateDatabase: book insertion no result");
        }

        $bookId = $this->bookId;

        if ($this->authors != null) {
            $i = 0;
            $query = "DELETE FROM BookAuthors WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            foreach ($this->authors as $item) {
                $query = "INSERT INTO BookAuthors VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->editors != null) {
            $i = 0;
            $query = "DELETE FROM BookEditors WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            foreach ($this->editors as $item) {
                $query = "INSERT INTO BookEditors VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->translators != null) {
            $i = 0;
            $query = "DELETE FROM BookTranslators WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            foreach ($this->translators as $item) {
                $query = "INSERT INTO BookTranslators VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $item, $i);
                $stmt->execute();
                $i++;
                $stmt->close();
            }
        }
        if ($this->genres != null) {
            $query = "DELETE FROM BookGenres WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $stmt->execute();

            if ($this->db->errno) {
                http_response_code(500); // Insernal Server Error
                $stmt->close();
                throw new UnknownDatabaseException($this->db,
                    "Error in BookUpdate->updateDatabase: Deleting preexisting BookGenres failed");
            }
            $stmt->close();

            foreach ($this->genres as $item) {
                $genreId = $item['genreId'];
                $tagId = $item['tagId'] ?? null;
                $query = "INSERT INTO BookGenres (BookId, GenreId, TagId) VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $bookId, $genreId, $tagId);
                $stmt->execute();

                if ($this->db->errno) {
                    http_response_code(500); // Insernal Server Error
                    $stmt->close();
                    throw new UnknownDatabaseException($this->db,
                        "Error in BookUpdate->updateDatabase: inserting BookGenres failed");
                }
                $stmt->close();
            }
        }
        if ($this->series != null) {
            $query = "DELETE FROM BookSeries WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $result = $stmt->execute();

            if ($result === false) {
                $stmt->close();
                throw new UnknownDatabaseException($this->db,
                    'Error in BookUpdate->updateDatabase (removing bookseries)');
            }
            $stmt->close();

            foreach ($this->series as $item) {
                $seriesId = $item['seriesId'];
                $volume = $item['volume'];
                $query = "INSERT INTO BookSeries (SeriesId, BookId, Volume)
                          VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('iii', $seriesId, $bookId, $volume);
                $result = $stmt->execute();

                if ($result === false) {
                    http_response_code(500);
                    $stmt->close();
                    throw new UnknownDatabaseException($this->db,
                        "Error in BookUpdate->updateDatabase: inserting bookseries");
                }
                $stmt->close();
            }
        }
        if ($this->versions != null) {
            $query = "DELETE FROM Versions WHERE BookId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $bookId);
            $result = $stmt->execute();

            if ($result === false) {
                $stmt->close();
                throw new UnknownDatabaseException($this->db,
                    'Error in BookUpdate->updateDatabase (removing versions)');
            }
            $stmt->close();

            foreach ($this->versions as $version) {
                $version->insertIntoDatabase();
            }
        }
    }
}

/**
 * @OA\Schema(
 *   schema="series_in_book",
 *   description="Keys for a series a book belongs to"
 * )
 */
class SeriesInBook
{
    /**
     * The ID of the series to which the book belongs
     * @var int
     * @OA\Property()
     */
    public $seriesId;
    /**
     * The volume of the book within the series
     * @var int
     * @OA\Property()
     */
    public $volume;
}

/**
 * @OA\Schema(
 *   schema="genre_in_book",
 *   description="Keys for a genre/tag describing a book"
 * )
 */
class GenreInBook
{
    /**
     * The ID of a genre
     * @var int
     * @OA\Property()
     */
    public $genreId;
    /**
     * The ID of a tag
     * @var int
     * @OA\Property()
     */
    public $tagId;
}