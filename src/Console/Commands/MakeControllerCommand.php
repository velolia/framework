<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller {name} [--resource]';
    protected string $description = 'Create a new controller class';

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Controller name is required.');
            return 1;
        }

        $isResource = in_array('--resource', $args);
        
        $path = $this->app->basePath() . '/app/Http/Controllers/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Controller [{$name}] already exists.");
            return 1;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = $this->getStub($name, $isResource);
        file_put_contents($path, $content);

        $this->success("Controller [{$name}] created successfully.");
        return 0;
    }

    protected function getStub(string $name, bool $isResource): string
    {
        if ($isResource) {
            return "<?php

namespace App\Http\Controllers;

use Velolia\Http\Request;
use Velolia\Http\Response;

class {$name}
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request \$request)
    {
        //
    }

    public function show(\$id)
    {
        //
    }

    public function edit(\$id)
    {
        //
    }

    public function update(Request \$request, \$id)
    {
        //
    }

    public function destroy(\$id)
    {
        //
    }
}
";
        }

        return "<?php

namespace App\Http\Controllers;

use Velolia\Http\Request;

class {$name}
{
    //
}
";
    }
}
