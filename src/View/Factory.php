<?php

declare(strict_types=1);

namespace Velolia\View;

use Velolia\Core\Application;
use Velolia\View\Loop;
use Exception;

class Factory
{
    protected string $viewPath;
    protected string $cachePath;
    protected array $shared = [];
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $activeLayout = null;
    protected array $loops = [];
    protected array $extensions = ['.php', '.blade.php'];

    public function __construct(protected Application $app, protected \Velolia\View\AST\ASTCompiler $compiler)
    {
        $this->viewPath = $app->basePath('resources/views');
        $this->cachePath = $app->basePath('storage/framework/views');
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function make(string $view, array $data = []): View
    {
        $path = $this->findView($view);
        return new View($this, $path, array_merge($this->shared, $data));
    }

    public function render(string $path, array $data = []): string
    {
        $compiledPath = $this->getCompiledPath($path);

        if ($this->isExpired($path, $compiledPath)) {
            $content = file_get_contents($path);
            $compiled = $this->compiler->compile($content);
            file_put_contents($compiledPath, $compiled);
        }

        $previousLayout = $this->activeLayout;
        $this->activeLayout = null;

        $output = $this->evaluatePath($compiledPath, $data);

        $layoutToApply = $this->activeLayout;
        $this->activeLayout = $previousLayout;

        if ($layoutToApply) {
            $layoutPath = $this->findView($layoutToApply);
            return $this->render($layoutPath, $data);
        }

        return $output;
    }

    public function extend(string $layout): void
    {
        $this->activeLayout = $layout;
    }

    protected function evaluatePath(string $path, array $data): string
    {
        ob_start();

        extract($data);

        $factory = $this;
        
        try {
            include $path;
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    protected function findView(string $view): string
    {
        $view = str_replace('.', '/', $view);
        $path = $this->viewPath . '/' . $view . $this->extensions[0];

        if (!file_exists($path)) {
            $path = $this->viewPath . '/' . $view . $this->extensions[1];
        }

        if (!file_exists($path)) {
            throw new Exception("View [$view] not found.");
        }

        return $path;
    }

    protected function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . sha1($path) . '.php';
    }

    protected function isExpired(string $path, string $compiledPath): bool
    {
        if (!file_exists($compiledPath)) {
            return true;
        }

        return filemtime($path) > filemtime($compiledPath);
    }

    public function startSection(string $name): void
    {
        ob_start();
        $this->sectionStack[] = $name;
    }

    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new Exception("Cannot end section when none is active.");
        }

        $activeSection = array_pop($this->sectionStack);
        $this->sections[$activeSection] = ob_get_clean();
    }

    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function addLoop(mixed $items): void
    {
        $count = is_countable($items) ? count($items) : 0;
        $parent = end($this->loops) ?: null;
        $this->loops[] = new Loop($count, $parent === false ? null : $parent);
    }

    public function popLoop(): void
    {
        array_pop($this->loops);
    }

    public function getLastLoop(): ?Loop
    {
        return end($this->loops) ?: null;
    }

    public function inlineSection(string $name, string $value): void
    {
        $this->sections[$name] = $value;
    }

    // -----------------------------------------------------------------------
    // Component / Slot Support
    // -----------------------------------------------------------------------

    /** @var array<int, array{view: string, props: array}> */
    protected array $componentStack = [];

    /**
     * Begin a component render. Starts an output buffer to capture slot content.
     *
     * @param string $view  e.g. 'components.card'
     * @param array  $props Additional props passed as attributes on the x-tag
     */
    public function startComponent(string $view, array $props = []): void
    {
        $this->componentStack[] = ['view' => $view, 'props' => $props];
        ob_start();
    }

    /**
     * End a component: collect the buffered slot content, render the component
     * view with props + $slot merged in, and return the result.
     */
    public function endComponent(): \Velolia\View\HtmlString
    {
        $slotHtml = ob_get_clean();
        $frame    = array_pop($this->componentStack);

        $data = array_merge($frame['props'], [
            'slot' => new \Velolia\View\HtmlString((string) $slotHtml),
        ]);

        $viewPath = $this->findView($frame['view']);
        $html     = $this->render($viewPath, $data);

        return new \Velolia\View\HtmlString($html);
    }

    /**
     * Render a self-closing component (no slot content).
     */
    public function renderComponentInline(string $view, array $props = []): \Velolia\View\HtmlString
    {
        $data     = array_merge($props, ['slot' => new \Velolia\View\HtmlString('')]);
        $viewPath = $this->findView($view);
        $html     = $this->render($viewPath, $data);

        return new \Velolia\View\HtmlString($html);
    }
}