<?php
// require_once($_SERVER['DOCUMENT_ROOT'] . '/../lib/authcom.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/../lib/libraryicom.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/tag_models.php');

/**
 * @OA\Get(
 *   path="/tags",
 *   tags={"Tags"},
 *   summary="Lists all tags with details",
 *   @OA\Response(response=200, description="Details for all known tags",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/tag_get")))
 * )
 */
function getTags() {
    $db = connectDb();

    $query = 'SELECT * FROM Tags ORDER BY GenreId, Name';
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $tags = array();

    while ($data = $result->fetch_assoc()) {
        $t = new Tag($data);
        $tags[] = $t;
    }

    freeResources($db, $stmt, $result);

    http_response_code(200);
    echoJson($tags);

}

/**
 * @OA\Get(
 *   path="/tags/{id}",
 *   tags={"Tags"},
 *   summary="Lists tag details",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested tag"),
 *   @OA\Response(response=200, description="Details of the requested tag",
 *                @OA\JsonContent(ref="#/components/schemas/tag_get")),
 *   @OA\Response(response=404, description="No known tag has that ID")
 * )
 *
 * @var int $id The ID of the tag to retrieve.
 */
function getTag(int $id) {
    $db = connectDb();

    $query = 'SELECT * FROM Tags WHERE TagId = ?';
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $tagData = $result->fetch_assoc();
        $t = new Tag($tagData);
    }

    freeResources($db, $stmt, $result);

    http_response_code(200);
    echoJson($t);
}

/**
 * @OA\Post(
 *   path="/tags",
 *   tags={"Tags"},
 *   summary="Adds a new tag",
 *   description="Requires user authentication.",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Tag details",
 *     @OA\JsonContent(ref="#/components/schemas/tag_post")),
 *   @OA\Response(response=200, description="Details of new tag",
 *                @OA\JsonContent(ref="#/components/schemas/tag_get")),
 *   @OA\Response(response=400, description="Malformed tag object"),
 *   security={{"jwt_key":{}}}
 * )
 */
function postTag() {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }

    $db = connectDb();

    $body = readBodyJson();

    $newId = 0;
    $tc = new TagCreate($db, $body);
    $tc->insertIntoDatabase($newId);

    $db->close();

    if ($newId) {
        getTag($newId);
    }
}

/**
 * @OA\Put(
 *   path="/tags/{id}",
 *   tags={"Tags"},
 *   summary="Updates a tag",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the tag to update", required=true),
 *   @OA\RequestBody(
 *     required=true,
 *     description="Tag details",
 *     @OA\JsonContent(ref="#/components/schemas/tag_put")),
 *   @OA\Response(response=200, description="Updated details of tag",
 *                @OA\JsonContent(ref="#/components/schemas/tag_get")),
 *   @OA\Response(response=400, description="Malformed tag object"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the tag to modify
 */
function putTag(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }

    $db = connectDb();

    $body = readBodyJson();

    $tu = new TagUpdate($db, $body, $id);
    $tu->updateDatabase();

    $db->close();

    getTag($id);
}

/**
 * @OA\Delete(
 *   path="/tags/{id}",
 *   tags={"Tags"},
 *   summary="Deletes a tag",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the tag to delete", required=true),
 *   @OA\Response(response=204, description="Success"),
 *   @OA\Response(response=404, description="Tag not found"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the tag to delete
 */
function deleteTag(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401); // Unauthorized
        return;
    }
    $db = connectDb();

    $query = 'DELETE FROM Tags WHERE TagId = ?';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new DatabasePrepareException($query, $db);
    }
    $stmt->bind_param('i', $id);
    $result = $stmt->execute();

    if ($result) {
        http_response_code(204); // No content
    }
    else {
        http_response_code(404); // Not found
    }

    freeResources($db, $stmt, null);
}
