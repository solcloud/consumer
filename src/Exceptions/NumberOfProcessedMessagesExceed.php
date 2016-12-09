<?php

namespace Solcloud\Consumer\Exceptions;

use Exception;

class NumberOfProcessedMessagesExceed extends Exception {

    public function __construct($message = "Hit upper limit for maximum number of message that script is allowed to process. #harakiri", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
