<?php

require_once('genre_models.php');
require_once('series_models.php');

/**
 * @OA\Schema(
 *   schema="book_stub",
 *   description="Brief summary of book details"
 * )
 */
class BookStub implements JsonSerializable
{
    /**
     * A model for book stubs.
     */
    public $fullText;

    /**
     * The book's unique ID
     * @var int
     * @OA\Property()
     */
    public $bookId;
    /**
     * The book's title (with subtitle, if present)
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The book's authors
     * @var string
     * @OA\Property()
     */
    public $authors;
    public $authorList = array();
    /**
     * The book's genres (and tags)
     * @var Genre[]
     * @OA\Property()
     */
    public $genres = array();
    /**
     * The book's list price
     * @var float
     * @OA\Property()
     */
    public $price;
    /**
     * The series to which the book belongs
     * @var SeriesStub[]
     * @OA\Property()
     */
    public $series = array();

    function __construct($data, $fullText = false) {
        $this->fullText = $fullText;

        $this->bookId = $data['BookId'];
        $this->title = $data['FullTitle'];

        if (isset($data['PersonId'])) {
            $personId = $data['PersonId'];
            $this->authorList[$personId] = $this->_formatAuthor(
                $data['LastName'], $data['FirstAndMiddleNames'],
                $data['Titles'], $data['Credentials']);
        }

        if (isset($data['GenreId'])) {
            $genreId = $data['GenreId'];
            $this->genres[$genreId] = new Genre([
                'GenreId' => $genreId,
                'Name' => $data['GenreName'],
                'ShortName' => $data['GenreShortName']
            ]);
            if (isset($data['TagId'])) {
                $this->genres[$genreId]->addRow($data);
            }
        }

        if (isset($data['SeriesId'])) {
            $seriesId = $data['SeriesId'];
            $this->series[$seriesId] = new SeriesStub($data);
        }
        $this->price = (float)$data['Price'];
    }

    function addRow($data) {
        if (isset($data['PersonId'])) {
            $personId = $data['PersonId'];
            if (!array_key_exists($personId, $this->authorList)) {
                $this->authors[$personId] = $this->_formatAuthor(
                    $data['LastName'], $data['FirstAndMiddleNames'],
                    $data['Titles'], $data['Credentials']);
            }
        }

        if (isset($data['GenreId'])) {
            $genreId = $data['GenreId'];
            if (!array_key_exists($genreId, $this->genres)) {
                $this->genres[$genreId] = new Genre([
                    'GenreId' => $genreId,
                    'Name' => $data['GenreName'],
                    'ShortName' => $data['GenreShortName']
                ]);
            }
            if (isset($data['TagId'])) {
                $tagId = $data['TagId'];
                if (!array_key_exists($tagId, $this->genres[$genreId]->tags)) {
                    $this->genres[$genreId]->addRow($data);
                }
            }
        }

        if (isset($data['SeriesId'])) {
            $seriesId = $data['SeriesId'];
            if (!array_key_exists($seriesId, $this->series)) {
                $this->series[$seriesId] = new SeriesStub($data);
            }
        }
    }

    /**
     * Combine parts of the authors name, abbreviating if requested.
     *
     * @param string $lastName The author's last name (or the organization's full name)
     * @param string $firstName The author's first name, if given.
     *
     * @return string The author's abbreviated name.
     */
    private function _formatAuthor($lastName, $firstName=null,
            $titles=null, $credentials=null) {
        if ($firstName) {
            $firstName = !$this->fullText ? substr($firstName, 0, 1) . '.' : $firstName;
            $mainName = "$firstName $lastName";
            if ($this->fullText) {
                if ($titles) {
                    $mainName = "$titles $mainName";
                }
                if ($credentials) {
                    $mainName = "$mainName, $credentials";
                }
            }
            return $mainName;
        }
        return $lastName;
    }

    /**
     * Join an array together into a comma-separated list with a conjunction if appropriate.
     *
     * @param string[] $items The array of items to be joined.
     *
     * @return string The string formed from the elements.
     */
    private function _generateList($items) {
        $count = count($items);
        if ($count == 0) {
            return '';
        }
        if ($count == 1) {
            return $items[0];
        }
        if ($count == 2) {
            return $items[0] . ' and ' . $items[1];
        }
        $result = '';
        for($i = 0; $i < $count - 1; $i++) {
            $result .= $items[$i] . ", ";
        }
        return $result . 'and ' . $items[$count - 1];
    }

    function jsonSerialize() {

        return [
            'bookId' => $this->bookId,
            'title' => $this->title,
            'authors' => $this->_generateList(array_values($this->authorList)),
            'genres' => array_values($this->genres),
            'price' => $this->price,
            'series' => array_values($this->series)
        ];

    }
}

/**
 * @OA\Schema(
 *   schema="series_stub",
 *   description="A summary of a series"
 * )
 */
class SeriesStub implements JsonSerializable {
    /**
     * The series's ID
     * @var int
     * @OA\Property()
     */
    public $seriesId;
    /**
     * The series's title
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * The volume of a book in the series
     * @var int
     * @OA\Property()
     */
    public $volume;

    public function __construct($data) {
        $this->seriesId = (int)($data['SeriesId']);
        $this->title = $data['SeriesTitle'];
        $this->volume = (int)$data['SeriesVolume'];
    }

    function jsonSerialize() {
        return [
            'seriesId' => $this->seriesId,
            'title' => $this->title,
            'volume' => $this->volume
        ];
    }
}
