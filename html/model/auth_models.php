<?php

/**
 * @OA\Schema(
 *   schema="login",
 *   description="Credentials for authentication"
 * )
 */
class LoginModel
{
    /**
     * A user's email address
     * @var string
     * @OA\Property()
     */
    public $email;
    /**
     * The user's password
     * @var string
     * @OA\Property()
     */
    public $password;
}

/**
 * @OA\Schema(
 *   schema="tokenInfo",
 *   description="Information about the user associated with the token"
 * )
 */
class TokenInfo implements JsonSerializable
{
    /**
     * The user's email address
     * @var string
     * @OA\Property()
     */
    public $email;
    /**
     * The user's display name
     * @var string
     * @OA\Property()
     */
    public $name;
    /**
     * The user's unique ID
     * @var string
     * @OA\Property()
     */
    public $id;
    /**
     * How long from the issued moment the token is valid (in seconds)
     * @var int
     * @OA\Property()
     */
    public $expiresIn;


    public function __construct($email, $name, $id, $expiresIn) {
        $this->email = $email;
        $this->name = $name;
        $this->id = $id;
        $this->expiresIn = $expiresIn;
    }

    public static function fromArray($data) {
        return new TokenInfo(
            $data['email'],
            $data['name'],
            $data['id'],
            $data['expiresIn']
        );
    }

    public function jsonSerialize() {
        return [
            "email" => $this->email,
            "name" => $this->name,
            "id" => $this->id,
            "expiresIn" => $this->expiresIn
        ];
    }
}

/**
 * @OA\Schema(
 *   schema="token",
 *   description="JWT information"
 * )
 */
class TokenModel implements JsonSerializable
{
    /**
     * An authenticated JSON web token
     * @var string
     * @OA\Property()
     */
    public $token;
    /**
     * Information about the user associated with the token
     * @var TokenInfo
     * @OA\Property()
     */
    public $info;
    /**
     * Whether the authentication was successful
     * @var bool
     * @OA\Property()
     */
    public $success;

    public function __construct($token, $info, $success=true){
        $this->token = $token;
        $this->info = $info;
        $this->success = $success;
    }

    public static function getErrorToken() {
        return new TokenModel(null, null, false);
    }

    public function jsonSerialize() {
        return [
            'token' => $this->token,
            'info' => $this->info,
            'success' => $this->success
        ];
    }
}