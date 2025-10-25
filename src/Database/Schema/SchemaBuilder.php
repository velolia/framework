<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

use Closure;
use Velolia\Database\DatabaseManager;

class SchemaBuilder
{
    protected DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->toSql();
        $this->db->statement($sql);
    }

    public function dropIfExists(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`;";
        $this->db->statement($sql);
    }
}