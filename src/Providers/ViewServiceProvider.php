<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('view', function ($app) {
            $compiler = new \Velolia\View\AST\ASTCompiler();
            return new \Velolia\View\Factory($app, $compiler);
        });
    }
}