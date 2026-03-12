<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeModelCommand extends Command
{
    protected string $signature = 'make:model {name} [-m] [-c] [-r]';
    protected string $description = 'Create a new Eloquent model class';

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Model name is required.');
            return 1;
        }

        $makeMigration = false;
        $makeController = false;
        $isResource = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '-')) {
                if (str_contains($arg, 'm')) $makeMigration = true;
                if (str_contains($arg, 'c')) $makeController = true;
                if (str_contains($arg, 'r')) {
                    $makeController = true;
                    $isResource = true;
                }
            }
        }

        $path = $this->app->basePath() . '/app/Models/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Model [{$name}] already exists.");
        } else {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

            $content = "<?php

namespace App\Models;

use Velolia\Database\Model;

class {$name} extends Model
{
    protected string \$table = '{$table}';
    
    protected array \$fillable = [];
}
";
            file_put_contents($path, $content);
            $this->success("Model [{$name}] created successfully.");
        }

        if ($makeMigration) {
            $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
            $migrationName = "create_{$tableName}_table";
            $command = new MakeMigrationCommand($this->app);
            $command->handle([$migrationName]);
        }

        if ($makeController) {
            $controllerName = $name . 'Controller';
            $command = new MakeControllerCommand($this->app);
            $ctrlArgs = [$controllerName];
            if ($isResource) {
                $ctrlArgs[] = '--resource';
            }
            $command->handle($ctrlArgs);
        }

        return 0;
    }
}
