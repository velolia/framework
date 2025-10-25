<?php

declare(strict_types=1);

namespace Velolia\Database\Migration;

abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}