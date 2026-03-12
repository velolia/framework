<?php

declare(strict_types=1);

namespace Velolia\Database\Exception;

use RuntimeException;

class RelationNotDefinedException extends RuntimeException
{
    public function __construct(string $relation, string $model)
    {
        parent::__construct("Relation [{$relation}] is not defined on model [{$model}].");
    }
}
