<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class ServeStopCommand extends Command
{
    protected string $signature = 'serve:stop [--port=8000]';
    protected string $description = 'Stop the background server running on a specific port';

    public function handle(array $args): int
    {
        $port = '8000';

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--port=')) {
                $port = substr($arg, 7);
            }
        }

        $this->info("Stopping server on port {$port}...");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            exec("netstat -ano | findstr :{$port}", $output);

            if (empty($output)) {
                $this->error("No server found running on port {$port}.");
                return 1;
            }

            $pid = null;
            foreach ($output as $line) {
                if (str_contains($line, 'LISTENING')) {
                    $parts = preg_split('/\s+/', trim($line));
                    $pid = end($parts);
                    break;
                }
            }

            if ($pid) {
                exec("taskkill /F /PID {$pid}", $killOutput, $returnVar);
                if ($returnVar === 0) {
                    $this->success("Server stopped successfully (PID: {$pid}).");
                    return 0;
                } else {
                    $this->error("Failed to stop server (PID: {$pid}). Try running as Administrator.");
                    return 1;
                }
            }
        } else {
            $output = [];
            exec("lsof -t -i:{$port}", $output);
            
            if (!empty($output) && is_numeric($output[0])) {
                $pid = $output[0];
                exec("kill -9 {$pid}", $killOutput, $returnVar);
                if ($returnVar === 0) {
                    $this->success("Server stopped successfully (PID: {$pid}).");
                    return 0;
                } else {
                    $this->error("Failed to stop server (PID: {$pid}).");
                    return 1;
                }
            }
            
            $this->error("No server found running on port {$port}.");
            return 1;
        }

        $this->error("No server found running on port {$port}.");
        return 1;
    }
}
