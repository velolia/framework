<?php

declare(strict_types=1);

namespace Velolia\View;

use Throwable;

class BladeEngine
{
    protected string $viewPath;
    protected string $cachePath;
    protected string $extension = '.blade.php';
    protected BladeCompiler $compiler;

    public function __construct()
    {
        $this->viewPath = base_path(config('view.path'));
        $this->cachePath = base_path(config('view.cache'));

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        $this->compiler = new BladeCompiler($this->cachePath);
    }

    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->getViewPath($view);
        $cachePath = $this->getCachePath($view);

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: [{$view}]");
        }

        if ($this->shouldRecompile($viewPath, $cachePath)) {
            $compiled = $this->compiler->compile(file_get_contents($viewPath));
            file_put_contents($cachePath, $compiled, LOCK_EX);
        }

        $content = $this->evaluate($cachePath, $data);

        $layout = $this->compiler->getLayout();
        if ($layout) {
            $this->compiler->clearLayout();
            return $this->render($layout, $data);
        }

        return $content;
    }

    protected function evaluate(string $cacheFile, array $data = []): string
    {
        $obLevel = ob_get_level();
        $data['__blade'] = $this;
        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $cacheFile;
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ob_get_clean();
    }

    protected function shouldRecompile(string $viewPath, string $cachePath): bool
    {
        if (!file_exists($cachePath)) {
            return true;
        }

        return filemtime($viewPath) > filemtime($cachePath);
    }

    protected function handleViewException(Throwable $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }

    public function exists(string $view): bool
    {
        $viewPath = $this->getViewPath($view);
        return file_exists($viewPath);
    }

    protected function getViewPath(string $view): string
    {
        return $this->viewPath . '/' . str_replace('.', '/', $view) . $this->extension;
    }

    protected function getCachePath(string $view): string
    {
        $hashedName = md5($view) . '.php';
        return $this->cachePath . '/' . $hashedName;
    }

    public function __call($method, $parameters)
    {
        return $this->compiler->$method(...$parameters);
    }
}