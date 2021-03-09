<?php

/**
 * @OA\Post(
 *   path="/authenticate",
 *   tags={"Authentication"},
 *   summary="Request an authentication token",
 *   @OA\RequestBody(
 *     required=true,
 *     description="Login credentials",
 *     @OA\JsonContent(ref="#/components/schemas/login")),
 *   @OA\Response(response=200, description="Authorization token",
 *                @OA\JsonContent(ref="#/components/schemas/token")),
 *   @OA\Response(response=401, description="Invalid email or password",
 *                @OA\JsonContent(ref="#/components/schemas/token"))
 * )
 */
function authenticate() {
    $post = readBodyJson();

    if (isset($post['email']) && isset($post['password'])) {
        try {
            $tokenData = getJwtToken($post['email'], $post['password']);
        }
        catch (LoginException $ex) {
        }
    }

    if (isset($tokenData)) {
        http_response_code(200);
        $tokenData->success = true;
        echoJson($tokenData);
    }
    else {
        http_response_code(401);
        echoJson(TokenModel::getErrorToken());
    }
}