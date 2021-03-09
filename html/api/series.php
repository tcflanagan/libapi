<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/libapi_com.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/libapi_authcom.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/series_models.php');


/**
 * @OA\Get(
 *   path="/series",
 *   tags={"Series"},
 *   summary="Lists all series",
 *   @OA\Response(response=200, description="Details for all known series",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/series_get")))
 * )
 */
function getSeriesPlural() {
    $db = connectDb();

    $query = 'SELECT B.*,
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
            FROM Series AS S
            LEFT JOIN BookSeries AS BS ON S.SeriesId = BS.SeriesId
            LEFT JOIN Books AS B ON BS.BookId = B.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookEditors AS BE ON B.BookId = BE.BookId
            LEFT JOIN Persons AS E ON BE.PersonId = E.PersonId
            LEFT JOIN BookTranslators AS BT ON B.BookId = BT.BookId
            LEFT JOIN Persons AS TR ON BT.PersonId = TR.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags as T ON BG.TagId = T.TagId
            ORDER BY S.SortableTitle, S.SeriesId, BS.Volume, BA.PersonOrder, BE.PersonOrder, BT.PersonOrder';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->execute();

    $result = $stmt->get_result();

    $series = array();

    while ($data = $result->fetch_assoc()) {
        if (array_key_exists($data['SeriesId'], $series)) {
            $series[$data['SeriesId']]->addRow($data);
        }
        else {
            $series[$data['SeriesId']] = new Series($data);
        }
    }

    freeResources($db, $stmt, $result);
    http_response_code(200); // OK
    echoJson(array_values($series));
}

/**
 * @OA\Get(
 *   path="/series/{id}",
 *   tags={"Series"},
 *   summary="Lists series details",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested series"),
 *   @OA\Response(response=200, description="Details of the requested series",
 *                @OA\JsonContent(ref="#/components/schemas/series_get")),
 *   @OA\Response(response=404, description="No known series has that ID")
 * )
 *
 * @var int $id The ID of the series to retrieve.
 */
function getSeries(int $id) {
    $db = connectDb();

    $query = 'SELECT B.*,
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
            FROM Series AS S
            LEFT JOIN BookSeries AS BS ON S.SeriesId = BS.SeriesId
            LEFT JOIN Books AS B ON BS.BookId = B.BookId
            LEFT JOIN BookAuthors AS BA ON B.BookId = BA.BookId
            LEFT JOIN Persons AS A ON BA.PersonId = A.PersonId
            LEFT JOIN BookEditors AS BE ON B.BookId = BE.BookId
            LEFT JOIN Persons AS E ON BE.PersonId = E.PersonId
            LEFT JOIN BookTranslators AS BT ON B.BookId = BT.BookId
            LEFT JOIN Persons AS TR ON BT.PersonId = TR.PersonId
            LEFT JOIN BookGenres AS BG ON B.BookId = BG.BookId
            LEFT JOIN Genres AS G ON BG.GenreId = G.GenreId
            LEFT JOIN Tags as T ON BG.TagId = T.TagId
            WHERE S.SeriesId = ?
            ORDER BY BS.Volume, BA.PersonOrder, BE.PersonOrder, BT.PersonOrder';

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();

    $series = null;

    while ($data = $result->fetch_assoc()) {
        if ($series == null) {
            $series = new Series($data);
        }
        else {
            $series->addRow($data);
        }
    }

    freeResources($db, $stmt, $result);

    if ($series == null) {
        http_response_code(404); // Not found
        return;
    }
    http_response_code(200); // OK
    echoJson($series);
}

/**
 * @OA\Post(
 *   path="/series",
 *   tags={"Series"},
 *   summary="Adds a new series",
 *   description="Requires user authentication.",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Series details",
 *     @OA\JsonContent(ref="#/components/schemas/series_post")),
 *   @OA\Response(response=200, description="Details of new series",
 *                @OA\JsonContent(ref="#/components/schemas/series_get")),
 *   @OA\Response(response=400, description="Malformed series object"),
 *   security={{"jwt_key":{}}}
 * )
 */
function postSeries() {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $sc = new SeriesCreate($db, $body);
    $newId = 0;
    $sc->insertIntoDatabase($newId);
    $db->close();
    if ($newId) {
        http_response_code(201); // Created
        getSeries($newId);
    }
    else {
        http_response_code(500);
    }
}

/**
 * @OA\Put(
 *   path="/series/{id}",
 *   tags={"Series"},
 *   summary="Updates a series",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the series to update", required=true),
 *   @OA\RequestBody(
 *     required=true,
 *     description="Series details",
 *     @OA\JsonContent(ref="#/components/schemas/series_put")),
 *   @OA\Response(response=200, description="Updated details of series",
 *                @OA\JsonContent(ref="#/components/schemas/series_get")),
 *   @OA\Response(response=400, description="Malformed series object"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the series to modify
 */
function putSeries(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $su = new SeriesUpdate($db, $body, $id);
    $su->updateDatabase();
    $db->close();
    getSeries($id);
}

/**
 * @OA\Delete(
 *   path="/series/{id}",
 *   tags={"Series"},
 *   summary="Deletes a series",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the series to delete", required=true),
 *   @OA\Response(response=204, description="Success"),
 *   @OA\Response(response=404, description="Series not found"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the series to delete
 */
function deleteSeries(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Not Authorized
        return;
    }
    $db = connectDb();

    $query = 'DELETE FROM Series WHERE SeriesId = ?';
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
