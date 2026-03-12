<?php

declare(strict_types=1);

namespace Velolia\Debug;

use Velolia\Http\Response;

class Toolbar
{
    public static function getLoadTime(): float
    {
        if (defined('VELOLIA_START')) {
            return microtime(true) - VELOLIA_START;
        }

        return 0.0;
    }

    public static function getMemoryUsage(): float
    {
        return memory_get_usage(false) / 1024 / 1024;
    }

    public static function inject(Response $response): void
    {
        $content = $response->getContent();
        
        if (stripos($content, '</body>') === false && stripos($content, '</html>') === false) {
            return;
        }

        $time = number_format(self::getLoadTime() * 1000, 2);
        $memory = number_format(self::getMemoryUsage(), 2);
        
        $queryLog = \Velolia\Database\Connection::getQueryLog();
        $queries = count($queryLog);

        $app = \Velolia\Core\Application::getInstance();
        $providers = $app ? $app->getLoadedProviders() : [];

        $html = self::renderHtml($time, $memory, $queries, $providers, $queryLog);

        if (stripos($content, '</body>') !== false) {
            $content = str_ireplace('</body>', $html . "\n</body>", $content);
        } else {
            $content .= $html;
        }

        $response->setContent($content);
    }

    protected static function renderHtml(string $time, string $memory, int $queries, array $providers = [], array $queryLog = []): string
    {
        $providersCount = count($providers);
        $providersHtml = '';
        foreach ($providers as $provider) {
            $providersHtml .= "<div class=\"vd-dropdown-item\">{$provider}</div>";
        }

        $queriesHtml = '';
        foreach ($queryLog as $q) {
            $sql = htmlspecialchars($q['sql']);
            $ms = number_format($q['time'], 2);
            $queriesHtml .= "<div class=\"vd-dropdown-item\"><i>{$ms}ms</i> - {$sql}</div>";
        }
        if (empty($queryLog)) {
            $queriesHtml = "<div class=\"vd-dropdown-item\">No queries executed.</div>";
        }

        return <<<HTML
<style>
    :root {
        --vd-bg: rgba(15, 23, 42, 0.85);
        --vd-border: rgba(51, 65, 85, 0.5);
        --vd-text: #f8fafc;
        --vd-text-muted: #94a3b8;
        --vd-hover: rgba(30, 41, 59, 0.95);
        --vd-accent-time: #10b981;
        --vd-accent-memory: #f59e0b;
        --vd-accent-query: #ef4444;
        --vd-accent-providers: #8b5cf6;
        --vd-font: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    #velolia-debug-toolbar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--vd-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        color: var(--vd-text);
        font-family: var(--vd-font);
        font-size: 13px;
        padding: 6px 8px 6px 16px;
        display: flex;
        align-items: center;
        gap: 24px;
        z-index: 2147483647;
        border-radius: 50px;
        border: 1px solid var(--vd-border);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }

    #velolia-debug-toolbar * {
        box-sizing: border-box;
    }

    #velolia-debug-toolbar .vd-brand {
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 6px;
        user-select: none;
    }

    #velolia-debug-toolbar .vd-stat {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    #velolia-debug-toolbar .vd-badge {
        background: rgba(255, 255, 255, 0.05);
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 500;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: default;
        border: 1px solid transparent;
        transition: all 0.2s;
        user-select: none;
    }

    #velolia-debug-toolbar .vd-dropdown:hover .vd-badge {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.15);
    }

    #velolia-debug-toolbar i {
        font-style: normal;
        font-weight: 700;
    }

    #velolia-debug-toolbar .vd-time i { color: var(--vd-accent-time); }
    #velolia-debug-toolbar .vd-memory i { color: var(--vd-accent-memory); }
    #velolia-debug-toolbar .vd-query i { color: var(--vd-accent-query); }
    #velolia-debug-toolbar .vd-providers i { color: var(--vd-accent-providers); }

    #velolia-debug-toolbar .vd-dropdown {
        position: relative;
    }

    #velolia-debug-toolbar .vd-dropdown-content {
        visibility: hidden;
        opacity: 0;
        position: absolute;
        bottom: calc(100% + 14px);
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background-color: var(--vd-hover);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        min-width: 320px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
        border: 1px solid var(--vd-border);
        border-radius: 12px;
        padding: 12px;
        z-index: 2147483647;
        max-height: 400px;
        overflow-y: auto;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #velolia-debug-toolbar .vd-dropdown:last-child .vd-dropdown-content {
        left: auto;
        right: 0;
        transform: translateY(10px);
    }

    #velolia-debug-toolbar .vd-dropdown::after {
        content: '';
        position: absolute;
        bottom: 100%;
        left: 0;
        width: 100%;
        height: 14px;
    }

    #velolia-debug-toolbar .vd-dropdown:hover .vd-dropdown-content {
        visibility: visible;
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    #velolia-debug-toolbar .vd-dropdown:last-child:hover .vd-dropdown-content {
        transform: translateY(0);
    }

    #velolia-debug-toolbar .vd-dropdown-content::-webkit-scrollbar {
        width: 6px;
    }
    #velolia-debug-toolbar .vd-dropdown-content::-webkit-scrollbar-track {
        background: transparent;
    }
    #velolia-debug-toolbar .vd-dropdown-content::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    #velolia-debug-toolbar .vd-dropdown-title {
        color: #fff;
        font-weight: 600;
        border-bottom: 1px solid var(--vd-border);
        padding-bottom: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #velolia-debug-toolbar .vd-dropdown-item {
        padding: 8px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-weight: normal;
        font-size: 12px;
        color: var(--vd-text-muted);
        word-wrap: break-word;
        border-radius: 6px;
        transition: background 0.2s, color 0.2s;
    }
    
    #velolia-debug-toolbar .vd-dropdown-item:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--vd-text);
    }

    #velolia-debug-toolbar .vd-dropdown-item:last-child {
        border-bottom: none;
    }
</style>
<div id="velolia-debug-toolbar">
    <div class="vd-brand">🚀 <span>Velolia</span></div>
    <div class="vd-stat">
        <div class="vd-badge vd-time">Time: <i>{$time} ms</i></div>
        <div class="vd-badge vd-memory">Memory: <i>{$memory} MB</i></div>
        
        <div class="vd-dropdown">
            <div class="vd-badge vd-query">Queries: <i>{$queries}</i></div>
            <div class="vd-dropdown-content">
                <div class="vd-dropdown-title">SQL Queries</div>
                {$queriesHtml}
            </div>
        </div>
        
        <div class="vd-dropdown">
            <div class="vd-badge vd-providers">Providers: <i>{$providersCount}</i></div>
            <div class="vd-dropdown-content">
                <div class="vd-dropdown-title">Loaded Providers</div>
                {$providersHtml}
            </div>
        </div>
    </div>
</div>
HTML;
    }
}