<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../data/exceptions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../data/utils.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/model/auth_models.php');
session_start();

use \Firebase\JWT\JWT;
use \Laminas\Config\Config;

$config = new Config(include($_SERVER['DOCUMENT_ROOT'] . '/../data/libapi_conf.php'));


/**
 * Connect to the identity database and return the mysqli object.
 *
 * @return mysqli
 */
function connectAuthDb() {
    global $config;
    $db = mysqli_connect(
        $config->connections->identity->host,
        $config->connections->identity->username,
        $config->connections->identity->password,
        $config->connections->identity->dbname);

    if ($db->connect_error) {
        throw new DatabaseConnectionException("Identity database connection failed: " .
            $db->connect_error);
    }
    return $db;
}

/**
 * Helper function to interpret a password hash.
 */
function reaNetworkByteOrder($buffer, $offset) {
    return ((int)($buffer[$offset + 0]) << 24)
            | ((int)($buffer[$offset + 1]) << 16)
            | ((int)($buffer[$offset + 2]) << 8)
            | ((int)($buffer[$offset + 3]));
}

/**
 * Return whether the given password matches the hashed password.
 *
 * @param string $hashedPassword The hashed password, probably from
 *                               the identity database.
 * @param string $givenPassword The password that the user supplied.
 *
 * @return bool Whether the password and hash match.
 */
function isPasswordCorrect(string $hashedPassword, string $givenPassword): bool {

    $algo = 'sha256';
    $headLength = 13;

    $decoded = base64_decode($hashedPassword);

    $converted = array();
    foreach(str_split($decoded) as $char) {
        $converted[] = sprintf("%u", ord($char));
    }
    $convertedLength = count($converted);

    $iterCount = (int)reaNetworkByteOrder($converted, 5);
    $saltLength = (int)reaNetworkByteOrder($converted, 9);

    $salt = array();
    for ($i = 0; $i < $saltLength; $i++) {
        $salt[] = $converted[$i + $headLength];
    }

    $subkeyLength = $convertedLength - $headLength - $saltLength;
    $expectedSubkey = array();
    for ($i = 0; $i < $subkeyLength; $i++) {
        $expectedSubkey[] = $converted[$i + $headLength + $saltLength];
    }
    $expectedSubkey = implode('', array_map("chr", $expectedSubkey));

    $userSubkey = hash_pbkdf2($algo, $givenPassword,
        implode('', array_map("chr", $salt)), $iterCount, $subkeyLength, true);

    return (bool)hash_equals($expectedSubkey, $userSubkey);
}

/**
 * Return the roles corresponding to the user.
 *
 * @param string $email The email address of the user trying to log in.
 * @param string $password The password of the user trying to log in.
 * @param string &$userName A reference to fill in the username, if desired.
 * @param string &$userId A reference to fill in the user ID, if desired.
 *
 * @return string[] The names of the roles the user belongs to (currently one
 *                  or both of 'ADMINS' and 'USERS').
 *
 * @throws LoginException An exception is thrown if the email does not exist in the
 *                        database or if the password is incorrect.
 */
function getRoles(string $email, string $password, string &$userName = null,
                  string &$userId = null) {
    $db = connectAuthDb();

    $query = 'SELECT u.PasswordHash AS PasswordHash, u.DisplayName as DisplayName, ' .
             'u.Id AS UserId, r.NormalizedName AS RoleName ' .
             'FROM AspNetUsers AS u, AspNetRoles AS r, AspNetUserRoles as ur ' .
             'WHERE u.Id = ur.UserId AND ur.RoleId = r.Id AND u.NormalizedEmail = ?';
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        $db->close();
        throw new DatabasePrepareException($query, $db);
    }
    $email = strtoupper($email);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        freeResources($db, $stmt, $result);
        throw new LoginException('USER');
    }

    $roles = array();
    $detectedUser = null;
    $detectedId = null;
    while ($data = $result->fetch_assoc()) {
        $pwHash = $data['PasswordHash'];
        if (!isPasswordCorrect($pwHash, $password)) {
            freeResources($db, $stmt, $result);
            throw new LoginException('PASSWORD');
        }
        $roles[] = $data['RoleName'];
        $detectedUser = $data['DisplayName'];
        $detectedId = $data['UserId'];
    }

    if ($userName !== null && $detectedUser !== null) {
        $userName = $detectedUser;
        $userId = $detectedId;
    }

    freeResources($db, $stmt, $result);
    return $roles;
}

/**
 * Return a JSON web token for the specified user.
 *
 * @param string $email The email address of the user trying to log in.
 * @param string $password The password with which the user is trying to log in.
 * @param string &$userName A reference to a string variable into which the user
 *                          name will be loaded, if given.
 * @param string &$userId A reference to a string variable into which the user ID will be loaded.
 *
 * @return TokenModel|null An array with the JSON token string and an array of info about the user (or
 *                         null if auth failed).
 */
function getJwtToken(string $email, string $password) {
    global $config;

    $userName = '';
    $userId = '';

    $roles = getRoles($email, $password, $userName, $userId);

    if (count($roles) == 0) {
        return null;
    }

    $expiresIn = 36000;

    $issuedAt = time();
    $notBefore = $issuedAt;
    $expire = $notBefore + $expiresIn; // Valid for 600 minutes
    $serverName = 'https://tcflanagan.com';

    $info = [
        'email' => $email,
        'name' => $userName,
        'id' => $userId,
        'expiresIn' => $expiresIn
    ];

    $access = [
        'edit' => in_array('USERS', $roles),
        'admin' => in_array('ADMINS', $roles)
    ];

    $payload = array(
        'iss' => $serverName,
        'iat' => $issuedAt,
        'nbf' => $notBefore,
        'exp' => $expire,
        'data' => array_merge($info, $access)
    );

    JWT::$leeway = 60;

    $secret = base64_decode($config->jwt_secret);
    $jwt = JWT::encode($payload, $secret);

    $tokenInfo = TokenInfo::fromArray($info);

    return new TokenModel($jwt, $tokenInfo);
}

/**
 * Return whether a valid token is present in the HTTP headers.
 *
 * @return bool Whether a valid auth token is found in the header.
 */
function isTokenValid(string $task = 'edit') {
    global $config;
    // Try to get a token from the authorization header
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        list($jwt) = sscanf($_SERVER['HTTP_AUTHORIZATION'], 'Bearer %s');
        if (!$jwt) {
            return false;
        }
    }
    else {
        return false;
    }

    $secret = base64_decode($config->jwt_secret);

    try {
        $token = JWT::decode($jwt, $secret, array('HS256'));
        $tokenArray = (array) $token;
        $data = (array)$tokenArray['data'];
        if ($data[$task] == true) {
            return true;
        }
    }
    catch (\Firebase\JWT\ExpiredException $e) {
        return false;
    }
    return false;
}


/**
 * Return whether the user is authenticated as a user via token.
 *
 * @return bool Whether the user has been authenticated.
 */
function isUserAuthenticated() {
    $tokenResponse = isTokenValid('edit');
    if ($tokenResponse) {
        return true;
    }
    http_response_code(401);
    return false;
}
?>