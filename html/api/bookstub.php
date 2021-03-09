<?php
/**
 * REST API for retreiving detailed BookStubs.
 *
 * The parameters listed below may be supplied as GET parameters in
 * the URL.
 *
 * @param int $id
 *      The ID of the book to be returned. If omitted, all books will
 *      be returned unless limited by $start and $num (see below). If
 *      an $id is supplied, $start and $num are ignored.
 * @param int $start
 *      The index of the first book (in the alphabetically sorted list)
 *      to be returned. If omitted, books will be included from the first.
 * @param int $num
 *      The (maximum) number of books to be returned. If omitted, all books
 *      after $start will be returned.
 * @param bool $fulltext
 *      If true, authors' first names and genre names will be abbreviated.
 *      If false or omitted, they will be abbreviated.
 * @param bool $asarray
 *      If true, authors and genres will be returned as arrays. Otherwise,
 *      they will be merged into strings.
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/model/bookstub_models.php');


/**
 * @OA\Get(
 *   path="/bookstubs",
 *   tags={"Book Stubs"},
 *   summary="Lists all book stubs",
 *   @OA\Response(response=200, description="Summaries for all known books",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/book_stub")))
 * )
 */
function getBookStubs() {

    $num = $_GET['num'] ?? null;
    $start = $_GET['start'] ?? null;
    $fullText = $_GET['fulltext'] ?? false;

    $db = connectDb();
    $query = 'SELECT B.BookId, B.FullTitle, V.Price,
            A.PersonId AS PersonId, A.LastName AS LastName,
            A.FirstAndMiddleNames AS FirstAndMiddleNames,
            A.Titles AS Titles, A.Credentials AS Credentials,
            G.GenreId AS GenreId, G.Name AS GenreName, G.ShortName AS GenreShortName,
            T.TagId AS TagId, T.Name AS TagName,
            S.FullTitle AS SeriesTitle, S.SeriesId AS SeriesId, BS.Volume AS SeriesVolume
            FROM Books AS B
            LEFT JOIN Versions AS V ON B.BookId = V.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags AS T ON BG.TagId = T.TagId
            LEFT JOIN BookSeries AS BS ON B.BookId = BS.BookId
            LEFT JOIN Series AS S ON BS.SeriesId = S.SeriesId
            ORDER BY B.SortableTitle, B.BookId, BA.PersonOrder ';

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $bookStubs = array();

    $lastId = null;
    $skipped = 0;
    $start = $start ?? 0;
    $num = $num ?? 0;

    while ($data = $result->fetch_assoc()) {
        if ($skipped >= $start) {
            if(count($bookStubs) == $num - 1) {
                break;
            }

            if (array_key_exists($data['BookId'], $bookStubs)) {
                $bookStubs[$data['BookId']]->addRow($data);
            }
            else {
                $bookStubs[$data['BookId']] = new BookStub($data, $fullText);
            }
        }
        else {
            if ($data['BookId'] !== $lastId) {
                $skipped++;
                $lastId = $data['BookId'];
            }
        }
    }

    freeResources($db, $stmt, $result);
    http_response_code(200); // OK
    echoJson(array_values($bookStubs));
}

/**
 * @OA\Get(
 *   path="/bookstubs/{id}",
 *   tags={"Book Stubs"},
 *   summary="Lists book summary",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested book"),
 *   @OA\Response(response=200, description="Summary of the requested book",
 *                @OA\JsonContent(ref="#/components/schemas/book_stub")),
 *   @OA\Response(response=404, description="No known book has that ID")
 * )
 *
 * @var int $id The ID of the book to retrieve.
 */
function getBookStub(int $id) {
    $fullText = $_GET['fulltext'] ?? false;

    $db = connectDb();
    $query = 'SELECT B.*, V.Price,
            A.PersonId AS PersonId, A.LastName AS LastName,
            A.FirstAndMiddleNames AS FirstAndMiddleNames,
            A.Titles AS Titles, A.Credentials AS Credentials,
            G.GenreId AS GenreId, G.Name AS GenreName, G.ShortName AS GenreShortName,
            T.TagId AS TagId, T.Name AS TagName,
            S.FullTitle AS SeriesTitle, S.SeriesId AS SeriesId, BS.Volume AS SeriesVolume
            FROM Books AS B
            LEFT JOIN Versions AS V ON B.BookId = V.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags as T ON BG.TagId = T.TagId
            LEFT JOIN BookSeries AS BS ON B.BookId = BS.BookId
            LEFT JOIN Series AS S ON BS.SeriesId = S.SeriesId
            WHERE B.BookId = ?
            ORDER BY BA.PersonOrder ';

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookStub = null;

    while ($data = $result->fetch_assoc()) {
        if ($bookStub == null) {
            $bookStub = new BookStub($data, $fullText);
        }
        else {
            $bookStub->addRow($data);
        }
    }

    freeResources($db, $stmt, $result);
    if ($bookStub == null) {
        http_response_code(404); // Not Found
    }
    else {
        http_response_code(200); // OK
        echoJson($bookStub);
    }
}
