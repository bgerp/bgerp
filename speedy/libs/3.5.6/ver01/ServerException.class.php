<?php
/**
 * Server Exception is thrown in case communication with server failed
 */
class ServerException extends Exception {

    /**
     * Constructs new instance of exception
     * @param Exception $previous
     */
    public function __construct($previous) {
        parent::__construct($previous->getMessage());
    }
}
?>