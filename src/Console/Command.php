<?php

declare(strict_types=1);

namespace Velolia\Console;

abstract class Command
{
    protected ArgvInput $input;
    protected ConsoleOutput $output;
    protected string $name = '';
    protected string $description = '';
    protected array $signature = [];

    public function __construct()
    {
        $this->configure();
    }

    abstract protected function configure(): void;
    abstract public function handle(): int;

    public function setInput(ArgvInput $input): void
    {
        $this->input = $input;
    }

    public function setOutput(ConsoleOutput $output): void
    {
        $this->output = $output;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSignature(): array
    {
        return $this->signature;
    }

    public function execute(): int
    {
        if (!$this->validateOptions()) {
            return 1;
        }

        return $this->handle();
    }

    protected function validateOptions(): bool
    {
        $inputOptions = $this->input->getOptions();
        $allowedOptions = $this->getAllowedOptions();

        foreach (array_keys($inputOptions) as $option) {
            if (!isset($allowedOptions[$option])) {
                $this->output->error("Unknown option: --{$option}");
                $this->showAvailableOptions();
                return false;
            }
        }

        return true;
    }

    private function getAllowedOptions(): array
    {
        $allowed = ['help' => true, 'h' => true];

        if (!isset($this->signature['options'])) {
            return $allowed;
        }

        foreach ($this->signature['options'] as $long => $config) {
            $allowed[$long] = true;
            if (isset($config['short'])) {
                $allowed[$config['short']] = true;
            }
        }

        return $allowed;
    }

    private function showAvailableOptions(): void
    {
        if (empty($this->signature['options'])) {
            $this->output->info("This command has no options.");
            return;
        }

        $this->output->newLine();
        $this->output->info("Available options:");
        
        foreach ($this->signature['options'] as $long => $config) {
            $short = isset($config['short']) ? "-{$config['short']}, " : "    ";
            $desc = $config['description'] ?? '';
            $this->output->line("  {$short}--{$long}  {$desc}");
        }
    }

    protected function argument(string $name, mixed $default = null): mixed
    {
        $index = $this->getArgumentIndex($name);
        return $this->input->getArgument($index, $default);
    }

    private function getArgumentIndex(string $name): int
    {
        if (!isset($this->signature['arguments'])) {
            return 0;
        }

        $index = 0;
        foreach ($this->signature['arguments'] as $argName => $config) {
            if ($argName === $name) {
                return $index;
            }
            $index++;
        }

        return 0;
    }

    protected function option(string $name): mixed
    {
        if (isset($this->signature['options'])) {
            foreach ($this->signature['options'] as $long => $config) {
                if ($name === $long && $this->input->hasOption($long)) {
                    return $this->input->getOption($long);
                }
                if (isset($config['short']) && $name === $long) {
                    if ($this->input->hasOption($config['short'])) {
                        return $this->input->getOption($config['short']);
                    }
                }
            }
        }

        return $this->input->getOption($name, false);
    }

    protected function hasOption(string $name): bool
    {
        return $this->option($name) !== false;
    }

    /**
     * Ask user for confirmation
     * 
     * @param string $question The question to ask
     * @return bool True if user confirms (yes/y), false otherwise
     */
    protected function confirm(string $question): bool
    {
        $this->output->line("{$question} (yes/no): ");
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return false;
        }
        
        $line = fgets($handle);
        fclose($handle);
        
        if ($line === false) {
            return false;
        }
        
        $answer = trim(strtolower($line));
        
        return in_array($answer, ['yes', 'y'], true);
    }

    /**
     * Ask user a question and get input
     * 
     * @param string $question The question to ask
     * @param mixed $default Default value if user doesn't input anything
     * @return string User's input or default value
     */
    protected function ask(string $question, mixed $default = null): string
    {
        $defaultText = $default !== null ? " [{$default}]" : "";
        $this->output->line("{$question}{$defaultText}: ");
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return (string) $default;
        }
        
        $line = fgets($handle);
        fclose($handle);
        
        if ($line === false) {
            return (string) $default;
        }
        
        $answer = trim($line);
        
        return empty($answer) && $default !== null ? (string) $default : $answer;
    }

    // ==========================================
    // ZERO DUPLICATION GENERATORS
    // ==========================================

    protected function generateController(string $name, bool $resource = false): bool
    {
        $name = $this->normalizeClassName($name, 'Controller');
        
        $parts = $this->parseNameWithNamespace($name);
        $namespace = 'App\\Http\\Controllers' . ($parts['namespace'] ? '\\' . $parts['namespace'] : '');
        $className = $parts['class'];
        
        $type = $resource ? 'Resource controller' : 'Controller';
        $stubName = $resource ? 'controller.resource.stub' : 'controller.stub';
        
        $content = $this->getStubContent($stubName, [
            'namespace' => $namespace,
            'class' => $className,
        ]);
        
        $path = $this->getControllerPath($name);
        $relativePath = $this->getRelativePath($path);

        if ($this->createFile($path, $content)) {
            $this->output->success("{$type} created: {$relativePath}");
            return true;
        }
        
        return false;
    }

    protected function generateModel(string $name): bool
    {
        $name = $this->normalizeClassName($name);
        
        $parts = $this->parseNameWithNamespace($name);
        $namespace = 'App\\Models' . ($parts['namespace'] ? '\\' . $parts['namespace'] : '');
        $className = $parts['class'];
        $tableName = $this->getTableName($className);
        
        $content = $this->getStubContent('model.stub', [
            'namespace' => $namespace,
            'class' => $className,
            'table' => $tableName,
        ]);
        
        $path = $this->getModelPath($name);
        $relativePath = $this->getRelativePath($path);

        if ($this->createFile($path, $content)) {
            $this->output->success("Model created: {$relativePath}");
            return true;
        }
        
        return false;
    }

    protected function generateMigration(string $name): bool
    {
        $tableName = $this->parseMigrationTableName($name);
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        
        $content = $this->getStubContent('migration.stub', [
            'table' => $tableName,
        ]);
        
        $path = $this->getMigrationPath($fileName);

        if ($this->createFile($path, $content)) {
            $this->output->success("Migration created: {$fileName}");
            return true;
        }
        
        return false;
    }

    protected function generateSeeder(string $name): bool
    {
        $name = $this->normalizeClassName($name);
        $parts = $this->parseNameWithNamespace($name);
        $namespace = 'Database\Seeders' . ($parts['namespace'] ? '\\' . $parts['namespace'] : '');
        $className = $parts['class'];
        $content = $this->getStubContent('seeder.stub', [
            'namespace' => $namespace,
            'class' => $className
        ]);

        $path = $this->getSeederPath($name);

        $relativePath = $this->getRelativePath($path);

        if ($this->createFile($path, $content)) {
            $this->output->success("Seeder created: {$relativePath}");
            return true;
        }
        
        return false;
    }

    // ==========================================
    // STUB HANDLING
    // ==========================================

    private function getStubContent(string $stubName, array $replacements): string
    {
        $stubPath = $this->getStubPath($stubName);
        
        if (!file_exists($stubPath)) {
            $this->output->error("Stub file not found: {$stubPath}");
            return '';
        }
        
        $content = file_get_contents($stubPath);
        
        // Replace placeholders
        foreach ($replacements as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        
        return $content;
    }

    private function getStubPath(string $stubName): string
    {
        return __DIR__ . '/Commands/stubs/' . $stubName;
    }

    private function parseNameWithNamespace(string $name): array
    {
        // Normalize slashes
        $name = str_replace(['/', '\\'], '/', $name);
        
        // Split by last slash
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $className = array_pop($parts);
            $namespace = implode('\\', $parts);
            
            return [
                'namespace' => $namespace,
                'class' => $className,
            ];
        }
        
        return [
            'namespace' => '',
            'class' => $name,
        ];
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function normalizeClassName(string $name, string $suffix = ''): string
    {
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            $parts = preg_split('/[\/\\\\]/', $name);
            $className = array_pop($parts);
            
            $className = str_replace(['_', '-'], ' ', $className);
            $className = ucwords($className);
            $className = str_replace(' ', '', $className);
            
            if ($suffix && !str_ends_with($className, $suffix)) {
                $className .= $suffix;
            }
            
            $parts[] = $className;
            return implode('/', $parts);
        }
        
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        
        if ($suffix && !str_ends_with($name, $suffix)) {
            $name .= $suffix;
        }

        return $name;
    }

    private function getTableName(string $modelName): string
    {
        if (str_contains($modelName, '/') || str_contains($modelName, '\\')) {
            $parts = preg_split('/[\/\\\\]/', $modelName);
            $modelName = array_pop($parts);
        }
        
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName);
        return strtolower($name);
    }

    private function parseMigrationTableName(string $name): string
    {
        $name = strtolower($name);
        
        // Pattern: create_products_table → products
        if (preg_match('/^create_(.+?)_table$/', $name, $matches)) {
            return $matches[1];
        }
        
        // Pattern: add_xxx_to_products_table → products
        if (preg_match('/^add_.+?_to_(.+?)_table$/', $name, $matches)) {
            return $matches[1];
        }
        
        // Pattern: remove_xxx_from_products_table → products
        if (preg_match('/^remove_.+?_from_(.+?)_table$/', $name, $matches)) {
            return $matches[1];
        }
        
        // Default: gunakan name apa adanya
        return $name;
    }

    private function createFile(string $path, string $content): bool
    {
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path)) {
            $relativePath = $this->getRelativePath($path);
            $this->output->error("File already exists: {$relativePath}");
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }

    private function getRelativePath(string $fullPath): string
    {
        $cwd = getcwd();
        
        $fullPath = str_replace('\\', '/', $fullPath);
        $cwd = str_replace('\\', '/', $cwd);
        
        if (str_starts_with($fullPath, $cwd)) {
            return substr($fullPath, strlen($cwd) + 1);
        }
        
        return $fullPath;
    }

    // ==========================================
    // PATH GETTERS
    // ==========================================

    private function getControllerPath(string $name): string
    {
        return getcwd() . "/app/Http/Controllers/{$name}.php";
    }

    private function getModelPath(string $name): string
    {
        return getcwd() . "/app/Models/{$name}.php";
    }

    private function getMigrationPath(string $fileName): string
    {
        return getcwd() . "/database/migrations/{$fileName}";
    }

    private function getSeederPath(string $name): string
    {
        return getcwd() . "/database/seeders/{$name}.php";
    }
}
