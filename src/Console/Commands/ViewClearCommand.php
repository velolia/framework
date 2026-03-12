<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ViewClearCommand extends Command
{
    protected string $signature = 'view:clear';
    protected string $description = 'Clear all compiled view cache files';

    public function handle(array $args): int
    {
        $cachePath = $this->app->basePath('storage/framework/views');

        if (!is_dir($cachePath)) {
            $this->info('Compiled views cache is already empty!');
            return 0;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                unlink($file->getPathname());
                $count++;
            }
        }

        $this->info("Cleared {$count} compiled views successfully!");
        return 0;
    }
}
