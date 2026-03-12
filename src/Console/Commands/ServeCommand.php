<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class ServeCommand extends Command
{
    protected string $signature = 'serve [--host=localhost] [--port=8000] [--daemon]';
    protected string $description = 'Start the development server (ultra fast)';

    public function handle(array $args): int
    {
        $host = 'localhost';
        $port = 8000;
        $daemon = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--host=')) {
                $host = substr($arg, 7);
            }
            if (str_starts_with($arg, '--port=')) {
                $port = substr($arg, 7);
            }
            if ($arg === '--daemon' || $arg === '-d') {
                $daemon = true;
            }
        }

        $publicPath = $this->app->basePath() . '/public';

        if ($daemon) {
            $this->info("⚡ Ultra-Fast Server starting in background at http://{$host}:{$port}");
            $this->comment("Run 'php ultra serve:stop --port={$port}' to stop it.");
            
            $command = "php -S {$host}:{$port} -t \"{$publicPath}\"";
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B {$command} > NUL 2>&1", "r"));
            } else {
                exec("{$command} > /dev/null 2>&1 &");
            }
            
            return 0;
        }

        $this->info("⚡ Ultra-Fast Server started at http://{$host}:{$port}");
        $this->comment("Press Ctrl+C to stop the server.");
        
        passthru("php -S {$host}:{$port} -t \"{$publicPath}\"");

        return 0;
    }
}