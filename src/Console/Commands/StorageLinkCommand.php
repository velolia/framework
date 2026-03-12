<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class StorageLinkCommand extends Command
{
    protected string $signature = 'storage:link';
    protected string $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    public function handle(array $args): int
    {
        $basePath = $this->app->basePath();
        $target = $basePath . '/storage/app/public';
        $link = $basePath . '/public/storage';

        if (file_exists($link)) {
            $this->error('The "public/storage" directory already exists.');
            return 1;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = sprintf('mklink /D "%s" "%s"', str_replace('/', '\\', $link), str_replace('/', '\\', $target));
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $this->success('The [public/storage] symbolic link has been created.');
                return 0;
            } else {
                $this->error('Failed to create symlink. Try running terminal as Administrator.');
                return 1;
            }
        }

        if (symlink($target, $link)) {
            $this->success('The [public/storage] symbolic link has been created.');
            return 0;
        }

        $this->error('Failed to create symbolic link.');
        return 1;
    }
}
