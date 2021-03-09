<?php

/**
 * @OA\Schema(
 *   schema="corpus",
 *   description="The works of an author"
 * )
 */
class Corpus implements JsonSerializable
{
    /**
     * The ID of the author
     * @var int
     * @OA\Property()
     */
    public $personId;
    /**
     * The person's name
     * @var string
     * @OA\Property()
     */
    public $name;
    /**
     * The person's name formatted for easy sorting
     * @var string
     * @OA\Property()
     */
    public $sortableName;

    /**
     * Summary details of the books by the author
     * @var CorpusBookStub[]
     * @OA\Property()
     */
    public $bookStubs = array();

    public function __construct($data) {
        $this->personId = (int)$data['PersonId'];
        $this->name = $data['DisplayName'];
        $this->sortableName = $data['SortableName'];
        $this->bookStubs[$data['BookId']] = new CorpusBookStub($data);
    }

    public function addData($data) {
        $bookId = $data['BookId'];
        if (array_key_exists($bookId, $this->bookStubs)) {
            $this->bookStubs[$bookId]->addData($data);
        }
        else {
            $this->bookStubs[$bookId] = new CorpusBookStub($data);
        }
    }

    public function addAuthorToBook($bookId, $authorId, $authorName) {
        if ($authorId == $this->personId) {
            return;
        }
        if (array_key_exists($bookId, $this->bookStubs)) {
            if (!array_key_exists($authorId, $this->bookStubs[$bookId]->otherAuthors)) {
                $this->bookStubs[$bookId]->otherAuthors[$authorId] = $authorName;
            }
        }
    }

    public function jsonSerialize() {
        return [
            'personId' => $this->personId,
            'name' => $this->name,
            'sortableName' => $this->sortableName,
            'bookStubs' => array_values($this->bookStubs)
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="corpus_book_stub",
 *   description="Details of a book in an author's corpus"
 * )
 */
class CorpusBookStub implements JsonSerializable
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
     * The book's title formatted for easy sorting
     * @var string
     * @OA\Property()
     */
    public $sortableTitle;
    /**
     * The other authors of the book
     * @var string[]
     * @OA\Property()
     */
    public $otherAuthors = array();
    /**
     * The book's genres and tags
     * @var CorpusBookGenre[]
     * @OA\Property()
     */
    public $genres = array();

    public function __construct($data) {
        $this->bookId = $data['BookId'];
        $this->title = $data['FullTitle'];
        $this->sortableTitle = $data['SortableTitle'];
        $this->genres[$data['GenreId']] = new CorpusBookGenre($data);
    }

    public function addData($data) {
        $genreId = $data['GenreId'];
        if (array_key_exists($genreId, $this->genres)) {
            $this->genres[$genreId]->addData($data);
        }
        else {
            $this->genres[$genreId] = new CorpusBookGenre($data);
        }
    }

    public function jsonSerialize() {
        return [
            'bookId' => $this->bookId,
            'title' => $this->title,
            'sortableTitle' => $this->sortableTitle,
            'otherAuthors' => array_values($this->otherAuthors),
            'genres' => array_values($this->genres)
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="corpus_book_genre",
 *   description="A book's genre information in an author's corpus"
 * )
 */
class CorpusBookGenre implements JsonSerializable
{
    /**
     * The unique ID of the genre
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
     * The sub-genre tags for the book
     * @var CorpusBookTag[]
     * @OA\Property()
     */
    public $tags = array();

    public function __construct($data) {
        $this->genreId = $data['GenreId'];
        $this->name = $data['GenreName'];
        $this->addData($data);
    }

    public function addData($data) {
        if ($data['TagId'] != 1) {
            $this->tags[$data['TagId']] = [
                'tagId' => $data['TagId'],
                'name' => $data['TagName']
            ];
        }
    }

    public function jsonSerialize() {
        return [
            'genreId' => $this->genreId,
            'name' => $this->name,
            'tags' => array_values($this->tags)
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="corpus_book_tag",
 *   description="A tag associated with a book associated with an author"
 * )
 */
class CorpusBookTag
{
    /**
     * The tag's unique ID
     * @var int
     * @OA\Property()
     */
    public $tagId;
    /**
     * The tag's name
     * @var int
     * @OA\Property()
     */
    public $name;
}