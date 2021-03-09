<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/genre_models.php');

/**
 * @OA\Get(
 *   path="/genres",
 *   tags={"Genres"},
 *   summary="Lists all genres",
 *   @OA\Response(response=200, description="Details for all known genres",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/genre_get")))
 * )
 */
function getGenres() {
    $db = connectDb();

    $query = 'SELECT G.GenreId, G.Name, G.ShortName, T.TagId, T.Name AS TagName
                FROM Genres AS G
                LEFT JOIN Tags AS T
                ON G.GenreId = T.GenreId
                ORDER BY G.Name, T.Name';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $genres = array();

    while ($data = $result->fetch_assoc()) {
        $genreId = $data['GenreId'];
        if (array_key_exists($genreId, $genres)) {
            $genres[$genreId]->addRow($data);
        }
        else {
            $genres[$genreId] = new Genre($data);
        }
    }

    freeResources($db, $stmt, $result);

    http_response_code(200); // OK
    echoJson(array_values($genres));
}

/**
 * @OA\Get(
 *   path="/genres/{id}",
 *   tags={"Genres"},
 *   summary="Lists genre details",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested genre"),
 *   @OA\Response(response=200, description="Details of the requested genre",
 *                @OA\JsonContent(ref="#/components/schemas/genre_get")),
 *   @OA\Response(response=404, description="No known genre has that ID")
 * )
 *
 * @var int $id The ID of the genre to retrieve.
 */
function getGenre(int $id) {
    $db = connectDb();

    $query = 'SELECT G.GenreId, G.Name, G.ShortName, T.TagId, T.Name AS TagName
                FROM Genres AS G
                LEFT JOIN Tags AS T
                ON G.GenreId = T.GenreId
                WHERE G.GenreId = ?
                ORDER BY T.Name';

    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();

    $genre = null;

    while ($data = $result->fetch_assoc()) {
        if ($genre == null) {
            $genre = new Genre($data);
        }
        else {
            $genre->addRow($data);
        }
    }

    freeResources($db, $stmt, $result);

    if ($genre != null) {
        http_response_code(200); // OK
        echoJson($genre);
    }
    else {
        http_response_code(404); // Not found
    }
}

/**
 * @OA\Post(
 *   path="/genres",
 *   tags={"Genres"},
 *   summary="Adds a new genre",
 *   description="Requires user authentication.",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Genre details",
 *     @OA\JsonContent(ref="#/components/schemas/genre_post")),
 *   @OA\Response(response=200, description="Details of new genre",
 *                @OA\JsonContent(ref="#/components/schemas/genre_get")),
 *   @OA\Response(response=400, description="Malformed genre object"),
 *   security={{"jwt_key":{}}}
 * )
 */
function postGenre() {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $newId = 0;
    $gc = new GenreCreate($db, $body);
    $gc->insertIntoDatabase($newId);

    $db->close();
    if ($newId) {
        http_response_code(201);
        getGenre($newId);
    }
}

/**
 * @OA\Put(
 *   path="/genres/{id}",
 *   tags={"Genres"},
 *   summary="Updates a genre",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the genre to update", required=true),
 *   @OA\RequestBody(
 *     required=true,
 *     description="Genre details",
 *     @OA\JsonContent(ref="#/components/schemas/genre_put")),
 *   @OA\Response(response=200, description="Updated details of genre",
 *                @OA\JsonContent(ref="#/components/schemas/genre_get")),
 *   @OA\Response(response=400, description="Malformed genre object"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the person to modify
 */
function putGenre(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }
    $db = connectDb();
    $body = readBodyJson();

    $gu = new GenreUpdate($db, $body, $id);
    $gu->updateDatabase();

    $db->close();
    getGenre($id);
}

/**
 * @OA\Delete(
 *   path="/genres/{id}",
 *   tags={"Genres"},
 *   summary="Deletes a genre",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the genre to delete", required=true),
 *   @OA\Response(response=204, description="Success"),
 *   @OA\Response(response=404, description="Genre not found"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the person to delete
 */
function deleteGenre(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }
    $db = connectDb();

    $query = 'DELETE FROM Genres WHERE GenreId = ?';
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
