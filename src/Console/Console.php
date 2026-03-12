<?php

declare(strict_types=1);

namespace Velolia\Console;

use Velolia\Core\Application;

class Console
{
    protected array $commands = [
        'serve'            => \Velolia\Console\Commands\ServeCommand::class,
        'storage:link'     => \Velolia\Console\Commands\StorageLinkCommand::class,
        'make:controller'  => \Velolia\Console\Commands\MakeControllerCommand::class,
        'make:model'       => \Velolia\Console\Commands\MakeModelCommand::class,
        'make:migration'   => \Velolia\Console\Commands\MakeMigrationCommand::class,
        'key:generate'     => \Velolia\Console\Commands\KeyGenerateCommand::class,
        'config:cache'     => \Velolia\Console\Commands\ConfigCacheCommand::class,
        'config:clear'     => \Velolia\Console\Commands\ConfigClearCommand::class,
        'view:cache'       => \Velolia\Console\Commands\ViewCacheCommand::class,
        'view:clear'       => \Velolia\Console\Commands\ViewClearCommand::class,
        'route:cache'      => \Velolia\Console\Commands\RouteCacheCommand::class,
        'route:clear'      => \Velolia\Console\Commands\RouteClearCommand::class,
        'optimize:clear'   => \Velolia\Console\Commands\OptimizeClearCommand::class,
        'migrate'          => \Velolia\Console\Commands\MigrateCommand::class,
        'migrate:rollback' => \Velolia\Console\Commands\MigrateRollbackCommand::class,
        'migrate:fresh'    => \Velolia\Console\Commands\MigrateFreshCommand::class,
        'db:seed'          => \Velolia\Console\Commands\DbSeedCommand::class,
    ];

    public function __construct(protected Application $app) {}

    public function handle(array $argv): int
    {
        $commandName = $argv[1] ?? null;

        if (!$commandName) {
            $this->displayHelp();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            echo "\033[31mCommand \"{$commandName}\" is not defined.\033[0m" . PHP_EOL;
            return 1;
        }

        $commandClass = $this->commands[$commandName];
        $command = new $commandClass($this->app);

        $args = array_slice($argv, 2);
        return $command->handle($args);
    }

    protected function displayHelp(): void
    {
        echo "\033[33m⚡ VeloliaCLI v1.0\033[0m" . PHP_EOL;
        echo "Usage: php velo [command] [arguments]" . PHP_EOL . PHP_EOL;
        echo "\033[32mAvailable commands:\033[0m" . PHP_EOL;

        foreach ($this->commands as $name => $class) {
            $command = new $class($this->app);
            printf("  \033[32m%-20s\033[0m %s\n", $name, $command->getDescription());
        }
    }
}
