<?php

class TokenExpiredException extends Exception {}

class DatabaseConnectionException extends Exception {}

class LoginException extends Exception {
    public $type;

    function __construct($type, $message = null, $code = 0, $previous = null) {
        $this->type = $type;

        parent::__construct($message, $code, $previous);
    }
}

class DatabasePrepareException extends Exception {
    public $query;
    public $errno;
    public $error;

    public function __construct(string $query, mysqli $db,
                                string $message = null, int $code = 0, string $previous = null) {
        $this->query = $query;
        $this->errno = $db->errno;
        $this->error = $db->error;

        $db->close();

        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return "<strong>DatabasePrepareException:</strong> mysqli_errno: $this->errno <br>\n " .
               "mysqli_error: $this->error <br>\n" .
               "Occurred while attempting to execute query: <br>\n$this->query<br>\n";
    }
}

class UnknownDatabaseException extends Exception {
    public $errno;
    public $error;

    public function __construct(mysqli $db, string $message = null, int $code = 0,
                                Exception $previous = null) {
        $this->errno = $db->errno;
        $this->error = $db->error;

        $db->close();

        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return "EXCEPTION: UnknownDatabaseException: \n" .
            "MYSQLI_ERRNO: $this->errno\n" .
            "MYSQLI_ERROR: $this->error\n" .
            "MESSAGE: " . $this->getMessage() . "\n" .
            "STACK TRACE:\n" . $this->getTraceAsString();
    }
}