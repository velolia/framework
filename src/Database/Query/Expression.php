<?php

declare(strict_types=1);

namespace Velolia\Database\Query;

class Expression
{
    /**
     * The value of the expression.
     *
     * @var string
     */
    protected $value;

    /**
     * Create a new raw query expression.
     *
     * @param  string  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}