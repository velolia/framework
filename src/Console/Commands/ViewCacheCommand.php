<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Velolia\View\AST\ASTCompiler;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

class ViewCacheCommand extends Command
{
    protected string $signature = 'view:cache';
    protected string $description = 'Compile all of the application\'s Blade templates ahead of time';

    public function handle(array $args): int
    {
        $viewPath = $this->app->basePath('resources/views');
        $cachePath = $this->app->basePath('storage/framework/views');

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $compiler = new ASTCompiler();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewPath));

        $count = 0;

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                if (str_ends_with($filename, '.blade.php') || str_ends_with($filename, '.php')) {
                    $path = $file->getPathname();

                    $relativePath = str_replace($viewPath . DIRECTORY_SEPARATOR, '', $path);
                    $relativePath = str_replace('\\', '/', $relativePath);

                    $exactPath = $viewPath . '/' . $relativePath;

                    try {
                        $content = file_get_contents($exactPath);
                        $compiled = $compiler->compile($content);
                        $compiledPath = $cachePath . '/' . sha1($exactPath) . '.php';

                        file_put_contents($compiledPath, $compiled);
                        $count++;
                    } catch (Exception $e) {
                        $this->error("Failed to compile view: {$relativePath}. Error: " . $e->getMessage());
                        return 1;
                    }
                }
            }
        }

        $this->info("Compiled {$count} views successfully!");
        return 0;
    }
}
