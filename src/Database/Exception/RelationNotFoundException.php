<?php

declare(strict_types=1);

namespace Velolia\Database\Exception;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    public function __construct(string $relation, string $model)
    {
        parent::__construct("Relation [$relation] on model [$model] returned null. Ensure the foreign key is set and related record exists.");
    }
}
