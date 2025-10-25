<?php

declare(strict_types=1);

namespace Velolia\Console;

class Console
{
    private CommandLoader $loader;
    private ConsoleOutput $output;
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        $this->loader = new CommandLoader($this->basePath);
        $this->output = new ConsoleOutput();
    }

    public function handle(array $argv): int
    {
        $input = new ArgvInput($argv);

        if (!$input->getCommandName() || $input->hasOption('help') || $input->hasOption('h')) {
            $this->showHelp();
            return 0;
        }

        $commands = $this->loader->loadCommands();

        $commandName = $input->getCommandName();

        if (!$this->loader->hasCommand($commandName)) {
            $this->output->error("Command '{$commandName}' not found.");
            $this->output->newLine();
            $this->showAvailableCommands($commands);
            return 1;
        }

        return $this->executeCommand($commandName, $input);
    }

    private function executeCommand(string $commandName, ArgvInput $input): int
    {
        $className = $this->loader->getCommand($commandName);
        
        try {
            $command = new $className();
            
            if (!$command instanceof Command) {
                $this->output->error("Invalid command class: {$className}");
                return 1;
            }

            $command->setInput($input);
            $command->setOutput($this->output);

            return $command->execute();

        } catch (\Throwable $e) {
            $this->output->error("Command failed: " . $e->getMessage());
            $this->output->comment($e->getTraceAsString());
            return 1;
        }
    }

    private function showHelp(): void
    {
        $this->output->title('Velolia CLI v1.0.0');
        $this->output->line('A Velolia CLI system');
        $this->output->newLine();

        $this->output->info('Usage:');
        $this->output->line('  php pool <command> [arguments] [options]');
        $this->output->newLine();

        $commands = $this->loader->loadCommands();
        $this->showAvailableCommands($commands);
        
        $this->output->newLine();
        $this->output->info('Global Options:');
        $this->output->line('  -h, --help     Display help for the command');
        $this->output->newLine();
    }

    private function showAvailableCommands(array $commands): void
    {
        if (empty($commands)) {
            $this->output->warning('No commands found in system/Console/Commands/');
            return;
        }

        $this->output->info('Available Commands:');

        $maxLength = 0;
        $commandDetails = [];

        foreach ($commands as $name => $className) {
            $instance = new $className();
            $commandDetails[$name] = [
                'description' => $instance->getDescription(),
                'signature' => $instance->getSignature()
            ];
            $maxLength = max($maxLength, strlen($name));
        }

        foreach ($commandDetails as $name => $details) {
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            $description = $details['description'] ?: 'No description';
            
            $formattedName = "\033[36m{$name}\033[0m";
            $this->output->line("  {$formattedName}{$padding}{$description}");

            if (!empty($details['signature']['options'])) {
                $options = [];
                foreach ($details['signature']['options'] as $long => $config) {
                    $short = isset($config['short']) ? "-{$config['short']}" : '';
                    $options[] = $short ? "{$short}|--{$long}" : "--{$long}";
                }
                $this->output->comment("    Options: " . implode(', ', $options));
            }
        }
    }
}