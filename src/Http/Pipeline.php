<?php

declare(strict_types=1);

namespace Velolia\Http;

use Closure;
use Velolia\Foundation\Application;

class Pipeline
{
    /**
     * The passable entity
     * 
     * @var mixed
    */
    protected mixed $passable;

    /**
     * The pipes
     * 
     * @var array
    */
    protected array $pipes = [];

    /**
     * Create a new pipeline instance.
     * 
     * @param Application $app
    */
    public function __construct(protected Application $app) {}

    /**
     * Send the passable entity to the pipeline.
     * 
     * @param mixed $passable
     * @return self
    */
    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Add a pipe to the pipeline.
     * 
     * @param array $pipes
     * @return self
    */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }
    
    /**
     * Run the pipeline.
     * 
     * @param callable $destination
     * @return mixed
    */
    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );
        
        return $pipeline($this->passable);
    }
    
    /**
     * Carry the pipe.
     * 
     * @return Closure
    */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->app->make($pipe);
                }
                
                return $pipe($passable, $stack);
            };
        };
    }
}