<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent\Exception;

use RuntimeException;

class ModelNotFoundException extends RuntimeException
{
    protected string $model;
    protected array|int|string $ids = [];

    public function setModel(string $model, array|int|string $ids = []): static
    {
        $this->model = $model;
        $this->ids = $ids;
        
        $this->message = "No query results for model [{$model}]";
        
        if (!empty($ids)) {
            $this->message .= ' ' . implode(', ', (array) $ids);
        }

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getIds(): array|int|string
    {
        return $this->ids;
    }
}