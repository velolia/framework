<?php

declare(strict_types=1);

namespace Velolia\Auth\Access;

trait AuthorizesRequests
{
    public function authorize(string $ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        $response = app(Gate::class)->inspect($ability, $arguments);

        if ($response->denied()) {
            throw new AuthorizationException($response->message() ?? "This action is unauthorized.");
        }

        return true;
    }

    public function authorizeResource(string $model, ?string $parameter = null, array $options = []): void
    {
        $parameter = $parameter ?: strtolower(class_basename($model));

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $modelArg = in_array($method, $this->resourceMissingAbilityMap()) ? $model : $parameter . ',' . $model;
            $this->middleware("can:{$ability},{$modelArg}", $options)->only($method);
        }
    }

    protected function resourceAbilityMap(): array
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    protected function resourceMissingAbilityMap(): array
    {
        return ['index', 'create', 'store'];
    }

    protected function parseAbilityAndArguments(string $ability, $arguments): array
    {
        if (is_string($ability) && !str_contains($ability, '\\')) {
            return [$ability, $arguments];
        }

        return [$ability, $arguments];
    }
}
