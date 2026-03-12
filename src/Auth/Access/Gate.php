<?php

declare(strict_types=1);

namespace Velolia\Auth\Access;

use Velolia\DI\Container;

class Gate
{
    protected Container $container;
    protected array $policies = [];
    protected array $abilities = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function policy(string $modelClass, string $policyClass): self
    {
        $this->policies[$modelClass] = $policyClass;
        return $this;
    }

    public function define(string $ability, callable|string $callback): self
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    public function allows(string $ability, $arguments = []): bool
    {
        return $this->inspect($ability, $arguments)->allowed();
    }

    public function denies(string $ability, $arguments = []): bool
    {
        return $this->inspect($ability, $arguments)->denied();
    }

    public function check(string $ability, $arguments = []): bool
    {
        return $this->allows($ability, $arguments);
    }

    public function inspect(string $ability, $arguments = []): AccessResponse
    {
        $arguments = is_array($arguments) ? $arguments : [$arguments];
        $user = auth()->user();

        if (!$user) {
            return AccessResponse::deny();
        }

        if (empty($arguments)) {
            $result = $this->callAbilityCallback($user, $ability, $arguments);
            return $this->rawResponse($result);
        }

        $model = $arguments[0];
        $modelClass = is_object($model) ? get_class($model) : $model;

        if (isset($this->policies[$modelClass])) {
            $policyClass = $this->policies[$modelClass];
            $policy = $this->container->make($policyClass);

            if (method_exists($policy, $ability)) {
                $result = $policy->$ability($user, ...$arguments);
                return $this->rawResponse($result);
            }
        }

        $result = $this->callAbilityCallback($user, $ability, $arguments);
        return $this->rawResponse($result);
    }

    protected function rawResponse($result): AccessResponse
    {
        if ($result instanceof AccessResponse) {
            return $result;
        }

        return $result ? AccessResponse::allow() : AccessResponse::deny();
    }

    protected function callAbilityCallback($user, string $ability, array $arguments): mixed
    {
        if (isset($this->abilities[$ability])) {
            $callback = $this->abilities[$ability];

            if (is_callable($callback)) {
                return $callback($user, ...$arguments);
            }

            if (is_string($callback) && str_contains($callback, '@')) {
                [$class, $method] = explode('@', $callback);
                $instance = $this->container->make($class);
                return $instance->$method($user, ...$arguments);
            }
        }

        return false;
    }
}
