<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class DebtorException extends Exception
{
    public $errorId;
    public $errorName;
    public $errorMessage;
    public $errorCode;

    public function __construct(string $errorName, $errorMessage = null, array $dataToLog = null)
    {
        $this->errorName = $errorName;
        $error = config('errors.' . $errorName);
        $this->errorId = $error['id'];
        $this->errorMessage = $errorMessage ?? $error['message'];
        $this->errorCode = $error['code'];
        parent::__construct($errorMessage, $this->errorCode, null);
    }
}
