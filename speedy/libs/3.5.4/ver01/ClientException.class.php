<?php
/**
 * Client Exception is thrown in case client state is invalid
 */
class ClientException extends Exception {

    /**
     * Constructs new instance of exception
     * @param string $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }
}
?>