<?php

declare(strict_types=1);

namespace Velolia\Routing\Exception;

use Velolia\ErrorHandler\HttpException;

class RouteNotFoundException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(404, $message);
    }
}