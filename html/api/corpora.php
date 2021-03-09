<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/model/corpus_models.php');

/**
 * @OA\Get(
 *   path="/corpora",
 *   tags={"Corpora"},
 *   summary="Lists all authors' bibliographies",
 *   @OA\Response(response=200, description="Summary of all authors' bibliographies",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/corpus")))
 * )
 */
function getCorpora() {
    $db = connectDb();
    $query = 'SELECT P.PersonId, B.BookId, G.GenreId, T.TagId, SortableName, FullTitle, SortableTitle, G.Name as GenreName, T.Name AS TagName,
        PersonDisplayName(Titles, FirstAndMiddleNames, LastName, Credentials) AS DisplayName
        FROM Persons AS P, Books AS B, Genres AS G, BookAuthors AS BA, BookGenres AS BG, Tags AS T
        WHERE P.PersonId = BA.PersonId AND B.BookId = BA.BookId
        AND B.BookId = BG.BookId AND BG.GenreId = G.GenreId AND BG.TagId = T.TagId
        ORDER BY P.SortableName, B.BookId';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        $stmt->close();
        throw new UnknownDatabaseException($db);
    }

    $corpora = array();
    $booksFound = array();

    while($data = $result->fetch_assoc()) {
        $bookId = $data['BookId'];
        $personId = $data['PersonId'];

        if (array_key_exists($bookId, $booksFound)) {
            if (!in_array($personId, $booksFound[$bookId])) {
                $booksFound[$bookId][] = $personId;
            }
        }
        else {
            $booksFound[$bookId] = array();
            $booksFound[$bookId][] = $personId;
        }

        if (array_key_exists($personId, $corpora)) {
            $corpora[$personId]->addData($data);
        }
        else {
            $corpora[$personId] = new Corpus($data);
        }
    }

    foreach ($booksFound as $bookId => $authorIds) {
        foreach ($authorIds as $authorId1) {
            foreach($authorIds as $authorId2) {
                $authorName2 = $corpora[$authorId2]->name;
                $corpora[$authorId1]->addAuthorToBook($bookId, $authorId2, $authorName2);
            }

        }
    }

    freeResources($db, $stmt, $result);

    http_response_code(200); // OK
    echoJson(array_values($corpora));
}


/**
 * @OA\Get(
 *   path="/corpora/{id}",
 *   tags={"Corpora"},
 *   summary="Lists an author's bibliographic summary",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested author"),
 *   @OA\Response(response=200, description="Bibliography of the requested author",
 *                @OA\JsonContent(ref="#/components/schemas/corpus")),
 *   @OA\Response(response=404, description="No known author has that ID")
 * )
 *
 * @var int $id The ID of the author to retrieve.
 */
function getCorpus(int $id) {
    $db = connectDb();

    $query = 'SELECT P.PersonId, B.BookId, G.GenreId, T.TagId, P.SortableName,
        B.FullTitle, B.SortableTitle, G.Name AS GenreName, T.Name AS TagName,
        PersonDisplayName(Titles, FirstAndMiddleNames, LastName, Credentials) AS DisplayName
        FROM Persons AS P, Books AS B, Genres AS G, BookAuthors AS BA, BookGenres AS BG, Tags AS T
        WHERE P.PersonId = BA.PersonId AND B.BookId = BA.BookId
        AND B.BookId = BG.BookId AND BG.GenreId = G.GenreId AND BG.TagId = T.TagId
        AND P.PersonId = ?
        ORDER BY B.BookId';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $corpus = null;

    while ($data = $result->fetch_assoc()) {
        if ($corpus == null) {
            $corpus = new Corpus($data);
        }
        else {
            $corpus->addData($data);
        }
    }

    freeResources($db, $stmt, $result);

    if ($corpus == null) {
        http_response_code(404); // Not Found
        exit();
    }
    http_response_code(200); // OK
    echoJson($corpus);
}