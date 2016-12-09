<?php

namespace Solcloud\Consumer\Exceptions;

use Exception;

class MessageCannotBeParsed extends Exception {

    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
