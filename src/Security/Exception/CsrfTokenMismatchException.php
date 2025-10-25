<?php

namespace Velolia\Security\Exception;

use Velolia\ErrorHandler\HttpException;

class CsrfTokenMismatchException extends HttpException
{
    public function __construct(string $message = 'Page Expired')
    {
        parent::__construct(419, $message);
    }
}