<?php

class BadRequestException extends BaseException
{
    #[\JetBrains\PhpStorm\Pure] public function __construct(string $message = "Bad request", int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}