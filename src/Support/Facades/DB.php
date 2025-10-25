<?php

namespace Velolia\Support\Facades;

class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}