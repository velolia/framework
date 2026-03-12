<?php

declare(strict_types=1);

namespace Velolia\Exceptions;

use Throwable;
use Velolia\Core\Application;
use Velolia\Http\Response;
use Velolia\Http\Request;

class Handler
{
    public function __construct(protected Application $app) {}

    public function report(Throwable $e): void
    {
        \Velolia\Support\Facades\Log::error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public function render(Request $request, Throwable $e): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($e instanceof \Velolia\Auth\Access\AuthorizationException) {
            $httpException = new HttpException(403, $e->getMessage(), $e);
            return $this->renderHttpExceptionPage($httpException);
        }

        if ($e instanceof HttpException) {
            return $this->renderHttpExceptionPage($e);
        }

        $debug = $this->app->make('config')->get('app.debug') ?? false;

        if ($debug) {
            return $this->renderDebugPage($e);
        }

        return $this->renderUserFriendlyPage($e);
    }

    protected function renderHttpExceptionPage(HttpException $e): Response
    {
        $status = $e->getStatusCode();
        $message = $e->getMessage() ?: $this->getDefaultStatusText($status);

        $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$status} | {$message}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Inter, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #0f172a; color: #e2e8f0; }
        .container { text-align: center; }
        .message { font-size: 1.25rem; color: #94a3b8; font-weight: 400; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class='container'>
        <p class='message'>{$status} | {$message}</p>
    </div>
</body>
</html>";

        return new Response($html, $status);
    }

    protected function getDefaultStatusText(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    protected function renderDebugPage(Throwable $e): Response
    {
        $source = $this->renderSourceCode($e->getFile(), $e->getLine());
        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Error: {$e->getMessage()}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0c0c0c; color: #e0e0e0; margin: 0; padding: 40px; line-height: 1.6; }
                .container { max-width: 1200px; margin: auto; background: #181818; padding: 40px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); border: 1px solid #2a2a2a; }
                .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                h1 { color: #ff5555; margin: 10px 0; font-size: 28px; letter-spacing: -0.5px; }
                .message { font-size: 20px; color: #fff; margin: 20px 0; font-weight: 500; }
                .file-info { background: #222; padding: 15px 20px; border-radius: 8px; font-family: 'Fira Code', 'Consolas', monospace; color: #ffb86c; margin-bottom: 25px; border-left: 4px solid #ff5555; overflow-wrap: break-word; }
                .file-info strong { color: #8be9fd; }
                
                /* Source Code Styles */
                .code-container { background: #1e1e1e; border-radius: 10px; border: 1px solid #333; overflow: hidden; margin: 30px 0; }
                .code-header { background: #252525; padding: 10px 20px; font-size: 13px; color: #888; border-bottom: 1px solid #333; font-family: monospace; }
                .code-body { padding: 10px 0; font-family: 'Fira Code', 'Consolas', monospace; font-size: 14px; overflow-x: auto; background: #1a1a1b; }
                .code-line { display: flex; align-items: flex-start; }
                .line-num { width: 50px; text-align: right; padding-right: 20px; color: #444; user-select: none; flex-shrink: 0; border-right: 1px solid #2a2a2a; margin-right: 15px; }
                .line-content { white-space: pre; color: #ccc; }
                .line-error { background: rgba(255, 85, 85, 0.15); border-left: 0; }
                .line-error .line-num { color: #ff5555; background: rgba(255, 85, 85, 0.1); }
                .line-error .line-content { color: #fff; font-weight: bold; }

                .trace-container { margin-top: 40px; }
                h2 { color: #8be9fd; font-size: 20px; margin-bottom: 15px; }
                .trace { background: #111; padding: 25px; border-radius: 10px; font-family: 'Consolas', monospace; font-size: 13px; color: #999; border: 1px solid #222; overflow-x: auto; }
                .badge { display: inline-block; padding: 5px 12px; border-radius: 6px; background: #ff5555; color: white; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='badge'>EXCEPTION</div>
                    <h1>" . get_class($e) . "</h1>
                    <div class='message'>{$e->getMessage()}</div>
                </div>

                <div class='file-info'>
                    Occurred in <strong>{$e->getFile()}</strong> on line <strong>{$e->getLine()}</strong>
                </div>

                <div class='code-container'>
                    <div class='code-header'>{$e->getFile()}</div>
                    <div class='code-body'>
                        {$source}
                    </div>
                </div>

                <div class='trace-container'>
                    <h2>Stack Trace</h2>
                    <div class='trace'>
                        " . nl2br(htmlspecialchars($e->getTraceAsString())) . "
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        return new Response($html, 500);
    }

    protected function renderSourceCode(string $file, int $line): string
    {
        if (!file_exists($file)) return "<div class='code-line'><span class='line-content'>File not found.</span></div>";

        $content = file($file);
        $start = max(0, $line - 6);
        $end = min(count($content), $line + 5);
        $output = "";

        for ($i = $start; $i < $end; $i++) {
            $num = $i + 1;
            $currentLine = htmlspecialchars($content[$i]);
            $isError = ($num === $line);
            $class = $isError ? "code-line line-error" : "code-line";
            
            $output .= "<div class='{$class}'>
                <span class='line-num'>{$num}</span>
                <span class='line-content'>{$currentLine}</span>
            </div>";
        }

        return $output;
    }

    protected function renderUserFriendlyPage(Throwable $e): Response
    {
        $status = 500;
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        } elseif (method_exists($e, 'getStatusCode')) {
            /** @var mixed $e */
            $status = $e->getStatusCode();
        } elseif ($e->getCode() >= 100 && $e->getCode() < 600) {
            $status = $e->getCode();
        }
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>Something went wrong</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8fafc; color: #64748b; margin: 0; }
        .content { text-align: center; }
        h1 { font-size: 4rem; margin: 0; color: #1e293b; }
        p { font-size: 1.25rem; }
    </style>
</head>
<body>
    <div class='content'>
        <h1>{$status}</h1>
        <p>Maaf, terjadi kesalahan pada server kami.</p>
    </div>
</body>
</html>";
        
        return new Response($html, $status);
    }
}