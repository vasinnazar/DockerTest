<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class DebtorException extends Exception
{
    public $errorId;
    public $errorMessage;
    public $errorCode;

    public function __construct(string $errorName, $errorMessage = null, array $dataToLog = null)
    {
        $error = config('errors.' . $errorName);

        $this->errorId = $error['id'];
        $this->errorMessage = $errorMessage ?? $error['message'];
        $this->errorCode = $error['code'];
        $dataToLog = array_merge($dataToLog ?? [], ['file' => $this->getFile(), 'line' => $this->getLine()]);
        Log::error("$errorName:", ['id' => $this->errorId, 'message' => $this->errorMessage, 'data' => $dataToLog ]);
        parent::__construct($errorMessage, $this->errorCode, null);
    }
}
