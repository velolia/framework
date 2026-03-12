<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Exception;

class KeyGenerateCommand extends Command
{
    protected string $signature = 'key:generate';
    protected string $description = 'Set the application key';

    public function handle(array $args): int
    {
        try {
            $key = $this->generateRandomKey();

            if (!$this->setKeyInEnvironmentFile($key)) {
                $this->error('Failed to set application key.');
                return 1;
            }

            $this->info('Application key set successfully.');

            return 0;
        } catch (Exception $e) {
            $this->error('Error generating key: ' . $e->getMessage());
            return 1;
        }
    }

    protected function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    protected function setKeyInEnvironmentFile(string $key): bool
    {
        $envPath = $this->app->basePath('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file does not exist.');
            return false;
        }

        $contents = file_get_contents($envPath);

        $pattern = '/^APP_KEY=.*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, 'APP_KEY=' . $key, $contents);
        } else {
            $contents .= PHP_EOL . 'APP_KEY=' . $key . PHP_EOL;
        }

        return file_put_contents($envPath, $contents) !== false;
    }
}
