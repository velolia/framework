<?php

declare(strict_types=1);

namespace Velolia\Routing\Exception;

use Velolia\ErrorHandler\HttpException;

class ControllerMethodNotFoundException extends HttpException {}