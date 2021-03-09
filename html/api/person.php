<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/person_models.php');

function personRead(mysqli $db, int $id = null) {

    if ($id) {
    }
    else {
    }
}

/**
 * @OA\Get(
 *   path="/persons",
 *   tags={"Contributors"},
 *   summary="Lists all people",
 *   @OA\Response(response=200, description="Details for all known contributors to books",
 *                @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/person_get")))
 * )
 */
function getPersons() {
    $db = connectDb();
    $query = 'SELECT * FROM Persons ORDER BY SortableName';
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $persons = array();

    while ($data = $result->fetch_assoc()) {
        $p = new Person($data);
        $persons[] = $p;
    }

    $result->free_result();
    $stmt->close();

    http_response_code(200); // OK
    echoJson($persons);
}

/**
 * @OA\Get(
 *   path="/persons/{id}",
 *   tags={"Contributors"},
 *   summary="Lists person details",
 *   @OA\Parameter(name="id", in="path", description="ID of the requested person"),
 *   @OA\Response(response=200, description="Details of the requested person",
 *                @OA\JsonContent(ref="#/components/schemas/person_get")),
 *   @OA\Response(response=404, description="No known person has that ID")
 * )
 *
 * @var int $id The ID of the person to retrieve.
 */
function getPerson(int $id) {
    $db = connectDb();
    $query = 'SELECT * FROM Persons WHERE PersonId = ?';
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $data = $result->fetch_assoc();
        $p = new Person($data);
    }
    else {
        $p = null;
    }

    freeResources($db, $stmt, $result);

    if ($p == null) {
        http_response_code(404); // Not Found
    }
    else {
        http_response_code(200);
        echoJson($p);
    }
}

/**
 * @OA\Post(
 *   path="/persons",
 *   tags={"Contributors"},
 *   summary="Adds a new person",
 *   description="Requires user authentication.",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Person or organization details",
 *     @OA\JsonContent(ref="#/components/schemas/person_post")),
 *   @OA\Response(response=200, description="Details of new person",
 *                @OA\JsonContent(ref="#/components/schemas/person_get")),
 *   @OA\Response(response=400, description="Malformed person object"),
 *   security={{"jwt_key":{}}}
 * )
 */
function postPerson() {
    if (!isUserAuthenticated()) {
        http_response_code(401);
        return;
    }
    $db = connectDb();
    $body = readBodyJson();
    $p = new PersonCreate($db, $body);
    $newId = 0;
    $p->insertIntoDatabase($newId);
    $db->close();
    if ($newId) {
        getPerson($newId);
    }
}

/**
 * @OA\Put(
 *   path="/persons/{id}",
 *   tags={"Contributors"},
 *   summary="Updates a person",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the person to update", required=true),
 *   @OA\RequestBody(
 *     required=true,
 *     description="Person or organization details",
 *     @OA\JsonContent(ref="#/components/schemas/person_put")),
 *   @OA\Response(response=200, description="Updated details of person",
 *                @OA\JsonContent(ref="#/components/schemas/person_get")),
 *   @OA\Response(response=400, description="Malformed person object"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the person to modify
 */
function putPerson(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401);
        return;
    }
    $db = connectDb();
    $body = readBodyJson();
    $p = new PersonUpdate($db, $body, $id);
    $p->updateDatabase();
    $db->close();
    getPerson($id);
}

/**
 * @OA\Delete(
 *   path="/persons/{id}",
 *   tags={"Contributors"},
 *   summary="Deletes a person",
 *   description="Requires user authentication.",
 *   @OA\Parameter(name="id", in="path", description="ID of the person to delete", required=true),
 *   @OA\Response(response=204, description="Success"),
 *   @OA\Response(response=404, description="Person not found"),
 *   security={{"jwt_key":{}}}
 * )
 *
 * @var int $id The ID of the person to delete
 */
function deletePerson(int $id) {
    if (!isUserAuthenticated()) {
        http_response_code(401);
        return;
    }
    $db = connectDb();

    $query = 'DELETE FROM Persons WHERE PersonId = ?';
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