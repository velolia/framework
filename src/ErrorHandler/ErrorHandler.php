<?php

declare(strict_types=1);

namespace Velolia\ErrorHandler;

use ErrorException;
use Throwable;
use Velolia\Http\Response;
use Velolia\Logger\Log;

class ErrorHandler
{
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(Throwable $e): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        app(Log::class)->error(
            $e->getMessage(),
            [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        );

        $status = ($e instanceof HttpException)
            ? $e->getStatusCode()
            : 500;

        if ($e instanceof HttpException) {
            $content = $this->renderSimpleException($status, $e->getMessage());
            return new Response($content, $status);
        }

        if (config('app.debug')) {
            $template = __DIR__ . '/Renderers/development.php';
            if (file_exists($template)) {
                ob_start();
                $this->renderTemplate($template, ['e' => $e, 'status' => $status]);
                $content = ob_get_clean();
            } else {
                $content = $e->getMessage();
            }
        } else {
            $content = $this->renderSimpleException($status, "Server Error");
        }

        return new Response($content, $status);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            http_response_code(500);

            $e = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            $this->handleException($e)->send();
        }
    }

    protected function renderSimpleException(int $status, string $text): string
    {
        $customView = $this->getCustomErrorView($status);
        if ($customView && file_exists($customView)) {
            ob_start();
            include $customView;
            return ob_get_clean();
        }

        return "{$status} | {$text}";
    }

    protected function getCustomErrorView(int $status): ?string
    {
        $viewsPath = __DIR__ . '/Errors';
        return "{$viewsPath}/{$status}.php";
    }

    protected function renderTemplate(string $file, array $data = []): void
    {
        extract($data);
        include $file;
    }
}