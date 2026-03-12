<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration {name}';
    protected string $description = 'Create a new migration file';

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Migration name is required.');
            return 1;
        }

        $directory = $this->app->basePath() . '/database/migrations';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $path = $directory . '/' . $filename;

        $table = 'table_name';
        $isCreate = str_starts_with($name, 'create_');
        
        if (preg_match('/create_(.*)_table/', $name, $matches)) {
            $table = $matches[1];
        } elseif (preg_match('/(?:add|remove)_.*_to_(.*)_table/', $name, $matches)) {
            $table = $matches[1];
        }

        $content = "<?php

use Velolia\Database\Schema\Blueprint;
use Velolia\Database\Schema\Schema;
use Velolia\Database\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
";
        if ($isCreate) {
            $content .= "        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });";
        } else {
            $content .= "        Schema::table('{$table}', function (Blueprint \$table) {
            // \$table->string('column_name');
        });";
        }

        $content .= "
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
";
        if ($isCreate) {
            $content .= "        Schema::dropIfExists('{$table}');";
        } else {
            $content .= "        Schema::table('{$table}', function (Blueprint \$table) {
            // \$table->dropColumn('column_name');
        });";
        }

        $content .= "
    }
};
";
        file_put_contents($path, $content);

        $this->success("Migration [{$filename}] created successfully.");
        return 0;
    }
}
