<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Velolia\Console\Command;

class ViewClearCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'view:clear';
        $this->description = 'Clear the compiled view cache';

        $this->signature = [
            'arguments' => [],
            'options' => []
        ];

    }

    public function handle(): int
    {
        $viewCache = base_path('storage/framework/views');

        if (!is_dir($viewCache)) {
            $this->output->error("View cache directory does not exist.");
            return 1;
        }
        
        $count = $this->deleteFiles($viewCache, ['*.php']);

        if ($count > 0) {
            $this->output->success("View cache cleared.");
            return 0;
        } else {
            $this->output->warning("No compiled view files to clear.");
            return 1;
        }

        return 0;
    }

    protected function deleteFiles(string $directory, array $patterns = ['*']): int
    {
        $count = 0;

        if (!is_dir($directory)) {
            return 0;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                foreach ($patterns as $pattern) {
                    if (fnmatch($pattern, $filename)) {
                        unlink($file->getPathname());
                        $count++;
                        break;
                    }
                }
            }
        }

        return $count;
    }
}