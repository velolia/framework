<?php

declare(strict_types=1);

namespace Velolia\Console;

use Velolia\Core\Application;

abstract class Command
{
    /**
     * The console command signature.
     */
    protected string $signature = '';

    /**
     * The console command description.
     */
    protected string $description = '';

    /**
     * Create a new command instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Execute the console command.
     */
    abstract public function handle(array $args): int;

    /**
     * Write a string as standard output.
     */
    protected function line(string $string, ?string $style = null): void
    {
        $styles = [
            'success' => "\033[32m", // Green
            'error'   => "\033[31m", // Red
            'info'    => "\033[34m", // Blue
            'comment' => "\033[33m", // Yellow
            'warning' => "\033[33m", // Yellow
        ];

        $end = "\033[0m";
        $start = $styles[$style] ?? '';

        echo $start . $string . $end . PHP_EOL;
    }

    /**
     * Write a string as information output.
     */
    protected function info(string $string): void
    {
        $this->line($string, 'info');
    }

    /**
     * Write a string as success output.
     */
    protected function success(string $string): void
    {
        $this->line($string, 'success');
    }

    /**
     * Write a string as error output.
     */
    protected function error(string $string): void
    {
        $this->line($string, 'error');
    }

    /**
     * Write a string as comment output.
     */
    protected function comment(string $string): void
    {
        $this->line($string, 'comment');
    }

    /**
     * Prompt the user for input.
     */
    protected function ask(string $question, ?string $default = null): string
    {
        echo $question . ($default ? " [{$default}]" : "") . ": ";
        $input = trim(fgets(STDIN));
        return $input === '' ? (string) $default : $input;
    }

    /**
     * Confirm a question with the user.
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $choices = $default ? " [Y/n]" : " [y/N]";
        echo $question . $choices . ": ";
        $input = strtolower(trim(fgets(STDIN)));

        if ($input === '') {
            return $default;
        }

        return in_array($input, ['y', 'yes']);
    }

    protected function ensureDatabaseExists(): void
    {
        try {
            $this->app->make('db')->getPdo();
        } catch (\PDOException $e) {
            if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                $config = $this->app->make('config')->get('database.connections.' . env('DB_CONNECTION', 'mysql'));
                $database = $config['database'];

                if ($this->confirm("Database \"{$database}\" does not exist. Do you want to create it?", true)) {
                    $this->createDatabase($config);
                    $this->app->singleton('db', \Velolia\Database\Manager::class);
                } else {
                    $this->error("Database \"{$database}\" does not exist. Aborting.");
                    exit(1);
                }
            } else {
                throw $e;
            }
        }
    }

    protected function createDatabase(array $config): void
    {
        $this->comment("Creating database \"{$config['database']}\"...");

        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("CREATE DATABASE `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE {$config['collation']};");
        
        $this->success("Database \"{$config['database']}\" created successfully.");
    }

    /**
     * Get the command signature.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Get the command description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}