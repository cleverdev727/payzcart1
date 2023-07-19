<?php


namespace App\Exceptions;


use Throwable;

class UnAuthorizedRequest extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function toJsonResponse() {
        return response()->json([
            'status' => false,
            'message' => $this->message
        ])->setStatusCode(401);
    }
}
