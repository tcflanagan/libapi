<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/book_models.php');

/**
 * @OA\Get(
 *   path="/books",
 *   tags={"Books"},
 *   summary="Lists all books",
 *   @OA\Response(response=200, description="Details for all known books",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/book_get")))
 * )
 */
function getBooks() {
    $db = connectDb();

    $query = 'SELECT B.BookId, B.Title, B.Subtitle, B.FullTitle, B.SortableTitle,
            B.AlternateTitle, B.Comments,
            V.VersionId, V.Edition, V.Year, V.Publisher, V.Location,
            V.Isbn10, V.Isbn13, V.Price, V.Quantity, V.Url,
            V.IsHardcover, V.IsPaperback, V.IsMMPaperback, V.IsLeatherbound, V.IsEbook,
            V.IsTextbook, V.IsHandBound, V.DetailsMixed, V.Notes,
            A.PersonId AS AuthorId, A.LastName AS AuthorLastName,
            A.FirstAndMiddleNames AS AuthorFirstAndMiddleNames,
            A.Titles AS AuthorTitles, A.Credentials AS AuthorCredentials,
            A.IsOrganization AS AuthorIsOrganization,
            A.SortableName AS AuthorSortableName, A.Notes AS AuthorNotes,
            E.PersonId AS EditorId, E.LastName AS EditorLastName,
            E.FirstAndMiddleNames AS EditorFirstAndMiddleNames,
            E.Titles AS EditorTitles, E.Credentials AS EditorCredentials,
            E.IsOrganization AS EditorIsOrganization,
            E.SortableName AS EditorSortableName, E.Notes AS EditorNotes,
            TR.PersonId AS TranslatorId, TR.LastName AS TranslatorLastName,
            TR.FirstAndMiddleNames AS TranslatorFirstAndMiddleNames,
            TR.Titles AS TranslatorTitles, TR.Credentials AS TranslatorCredentials,
            TR.IsOrganization AS TranslatorIsOrganization,
            TR.SortableName AS TranslatorSortableName, TR.Notes AS TranslatorNotes,
            G.GenreId AS GenreId, G.Name AS GenreName, G.ShortName AS GenreShortName,
            T.TagId AS TagId, T.Name AS TagName,
            S.SeriesId AS SeriesId, S.Title AS SeriesTitle, S.Subtitle AS SeriesSubtitle,
            S.Price AS SeriesPrice, S.Isbn13 AS SeriesIsbn13, S.Isbn10 AS SeriesIsbn10,
            S.AmazonUrl AS SeriesAmazonUrl, BS.Volume AS Volume
            FROM Books AS B
            LEFT JOIN Versions AS V ON B.BookId = V.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookEditors AS BE ON B.BookId = BE.BookId
            LEFT JOIN Persons AS E ON BE.PersonId = E.PersonId
            LEFT JOIN BookTranslators AS BT ON B.BookId = BT.BookId
            LEFT JOIN Persons AS TR ON BT.PersonId = TR.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags as T ON BG.TagId = T.TagId
            LEFT JOIN BookSeries AS BS ON B.BookId = BS.BookId
            LEFT JOIN Series AS S ON BS.SeriesId = S.SeriesId
            ORDER BY B.SortableTitle, B.BookId, BA.PersonOrder, BE.PersonOrder, BT.PersonOrder';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->execute();

    $result = $stmt->get_result();

    $books = array();

    while ($data = $result->fetch_assoc()) {
        if (array_key_exists($data['BookId'], $books)) {
            $books[$data['BookId']]->addRow($data);
        }
        else {
            $books[$data['BookId']] = new Book($data);
        }
    }

    freeResources($db, $stmt, $result);
    http_response_code(200); // OK
    echoJson(array_values($books));
}

/**
 * @OA\Get(
 *   path="/books/{id}",
 *   tags={"Books"},
 *   summary="Lists book details",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested book"),
 *   @OA\Response(response=200, description="Details of the requested book",
 *                @OA\JsonContent(ref="#/components/schemas/book_get")),
 *   @OA\Response(response=404, description="No known book has that ID")
 * )
 *
 * @var int $id The ID of the book to retrieve.
 */
function getBook(int $id) {
    $db = connectDb();

    $query = 'SELECT B.BookId, B.Title, B.Subtitle, B.FullTitle, B.SortableTitle,
            B.AlternateTitle, B.Comments,
            V.VersionId, V.Edition, V.Year, V.Publisher, V.Location,
            V.Isbn10, V.Isbn13, V.Price, V.Quantity, V.Url,
            V.IsHardcover, V.IsPaperback, V.IsMMPaperback, V.IsLeatherbound, V.IsEbook,
            V.IsTextbook, V.IsHandBound, V.DetailsMixed, V.Notes,
            A.PersonId AS AuthorId, A.LastName AS AuthorLastName,
            A.FirstAndMiddleNames AS AuthorFirstAndMiddleNames,
            A.Titles AS AuthorTitles, A.Credentials AS AuthorCredentials,
            A.IsOrganization AS AuthorIsOrganization,
            A.SortableName AS AuthorSortableName, A.Notes AS AuthorNotes,
            E.PersonId AS EditorId, E.LastName AS EditorLastName,
            E.FirstAndMiddleNames AS EditorFirstAndMiddleNames,
            E.Titles AS EditorTitles, E.Credentials AS EditorCredentials,
            E.IsOrganization AS EditorIsOrganization,
            E.SortableName AS EditorSortableName, E.Notes AS EditorNotes,
            TR.PersonId AS TranslatorId, TR.LastName AS TranslatorLastName,
            TR.FirstAndMiddleNames AS TranslatorFirstAndMiddleNames,
            TR.Titles AS TranslatorTitles, TR.Credentials AS TranslatorCredentials,
            TR.IsOrganization AS TranslatorIsOrganization,
            TR.SortableName AS TranslatorSortableName, TR.Notes AS TranslatorNotes,
            G.GenreId AS GenreId, G.Name AS GenreName, G.ShortName AS GenreShortName,
            T.TagId AS TagId, T.Name AS TagName,
            S.SeriesId AS SeriesId, S.Title AS SeriesTitle, S.Subtitle AS SeriesSubtitle,
            S.Price AS SeriesPrice, S.Isbn13 AS SeriesIsbn13, S.Isbn10 AS SeriesIsbn10,
            S.AmazonUrl AS SeriesAmazonUrl, BS.Volume AS Volume
            FROM Books AS B
            LEFT JOIN Versions AS V ON B.BookId = V.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookEditors AS BE ON B.BookId = BE.BookId
            LEFT JOIN Persons AS E ON BE.PersonId = E.PersonId
            LEFT JOIN BookTranslators AS BT ON B.BookId = BT.BookId
            LEFT JOIN Persons AS TR ON BT.PersonId = TR.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags as T ON BG.TagId = T.TagId
            LEFT JOIN BookSeries AS BS ON B.BookId = BS.BookId
            LEFT JOIN Series AS S ON BS.SeriesId = S.SeriesId
            WHERE B.BookId = ?
            ORDER BY BA.PersonOrder, BE.PersonOrder, BT.PersonOrder';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();

    $book = null;

    while ($data = $result->fetch_assoc()) {
        if ($book == null) {
            $book = new Book($data);
        }
        else {
            $book->addRow($data);
        }
    }

    freeResources($db, $stmt, $result);

    if ($book == null) {
        http_response_code(404); // Not found
        return;
    }
    http_response_code(200); // OK
    echoJson($book);
}

/**
 * @OA\Post(
 *   path="/books",
 *   tags={"Books"},
 *   summary="Adds a new book",
 *   description="Requires user authentication.",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Book details",
 *     @OA\JsonContent(ref="#/components/schemas/book_post")),
 *   @OA\Response(response=200, description="Details of new book",
 *                @OA\JsonContent(ref="#/components/schemas/book_get")),
 *   @OA\Response(response=400, description="Malformed book object"),
 *   security={{"jwt_key":{}}}
 * )
 */
function postBook() {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $bc = new BookCreate($db, $body);
    $newId = 0;
    $bc->insertIntoDatabase($newId);
    $db->close();
    if ($newId) {
        http_response_code(201); // Created
        getBook($newId);
    }
    else {
        http_response_code(500);
    }
}

/**
 * @OA\Put(
 *   path="/books/{id}",
 *   tags={"Books"},
 *   summary="Updates a book",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the book to update", required=true),
 *   @OA\RequestBody(
 *     required=true,
 *     description="Book details",
 *     @OA\JsonContent(ref="#/components/schemas/book_put")),
 *   @OA\Response(response=200, description="Updated details of book",
 *                @OA\JsonContent(ref="#/components/schemas/book_get")),
 *   @OA\Response(response=400, description="Malformed book object"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the book to modify
 */
function putBook(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $bu = new BookUpdate($db, $body, $id);
    $bu->updateDatabase();
    $db->close();
    getBook($id);
}

/**
 * @OA\Delete(
 *   path="/books/{id}",
 *   tags={"Books"},
 *   summary="Deletes a book",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the book to delete", required=true),
 *   @OA\Response(response=204, description="Success"),
 *   @OA\Response(response=404, description="Book not found"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the book to delete
 */
function deleteBook(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();

    $query = 'DELETE FROM Books WHERE BookId = ?';
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $result = $stmt->execute();

    if ($result) {
        http_response_code(204);
    }
    else {
        http_response_code(404); // Not found
    }

    freeResources($db, $stmt, null);
}