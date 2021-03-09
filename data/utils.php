<?php
/**
 * General utility functions for various common tasks.
 */

/**
 * Return the full URL (including protocol and requests) of the current page.
 *
 * @return string The URL of the current page.
 */
function getFullUrl() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $url = 'https://';
    }
    else {
        $url = 'http://';
    }

    $url .= $_SERVER['HTTP_HOST'];
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
}


/**
 * Free database resources
 */
function freeResources(mysqli $db=null, mysqli_stmt $stmt=null, mysqli_result $result=null) {
    if ($result != null) {
        $result->free_result();
    }
    if ($stmt != null) {
        $stmt->close();
    }
    if ($db != null) {
        $db->close();
    }
}


/**
 * Read the contents of the PHP input stream as JSON data and return an object.
 *
 * @return Object The contents of the input stream, assuming it was JSON-encoded.
 */
function readBodyJson() {
    $fileContents = file_get_contents('php://input');
    return json_decode($fileContents, true);
}


/**
 * Set the header to indicate JSON data and output the data.
 *
 * @param Object $data A json-serializable object.
 */
function echoJson($data) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
}

/**
 * Set the value of a field to a key from an array or null if empty or blank.
 * @param dictionary $dict The dictionary from which to pull a value.
 * @param string $key The array key to use to retreive the value.
 * @param reference &$field The field into which the value should be stored.
 */
function storeValueOrNull($dict, string $key, &$field) {
    if (isset($dict[$key])) {
        $value = $dict[$key];
        if (is_string($value)) {
            $value = trim($value);
            if ($value) {
                $field = $dict[$key];
            }
            else {
                $field = null;
            }
        }
        else {
            $field = $value;
        }
    }
}