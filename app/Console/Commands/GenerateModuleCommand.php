<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Genera un módulo en app/Modules con soporte para submódulos';
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        // Obtener el argumento y limpiar la ruta
        $inputName = trim($this->argument('name'), '/');
        
        // Convertir la ruta en formato correcto: "Core/Auth" => ["Core", "Auth"]
        $nameParts = explode('/', $inputName);
        $moduleName = Str::studly(array_pop($nameParts)); // Último segmento como nombre del módulo
        $namespacePath = implode('/', array_map('Str::studly', $nameParts)); // Rutas anteriores como namespace
        $fullModulePath = app_path("Modules/{$namespacePath}/{$moduleName}");

        $this->createDirectories($fullModulePath);
        $this->createFiles($fullModulePath, $moduleName, $namespacePath);

        $this->info("Módulo {$namespacePath}/{$moduleName} generado correctamente.");
    }

    protected function createDirectories(string $modulePath)
    {
        $directories = [
            'Database/Migrations',
            'Database/Seeder',
            'Http/Controllers',
            'Http/Requests',
            'Http/Resources',
            'Models',
            'Providers',
            'Routes',
        ];

        foreach ($directories as $dir) {
            $this->files->makeDirectory("{$modulePath}/{$dir}", 0755, true, true);
        }
    }

    protected function createFiles(string $modulePath, string $moduleName, string $namespacePath)
    {
        $stubPath = base_path('LnxScaffold/stubs');
        $fullNamespace = $namespacePath ? "App\Modules\\{$namespacePath}\\{$moduleName}" : "App\Modules\\{$moduleName}";

        $replacements = [
            '{{moduleNamespace}}' => $fullNamespace,
            '{{moduleName}}' => $moduleName,
            '{{moduleVariable}}' => Str::camel($moduleName),
            '{{moduleTable}}' => Str::snake(Str::plural($moduleName)),
        ];

        $stubs = [
            'Migration.stub' => "Database/Migrations/0001_create_{{moduleTable}}.php",
            'Seeder.stub' => "Database/Seeder/{{moduleTable}}Seeder.php",
            'Controller.stub' => "Http/Controllers/{{moduleName}}Controller.php",
            'Request.stub' => "Http/Requests/{{moduleName}}SaveRequest.php",
            'DataTableResource.stub' => "Http/Resources/{{moduleName}}DataTableItemResource.php",
            'FormItemResource.stub' => "Http/Resources/{{moduleName}}FormItemResource.php",
            'Model.stub' => "Models/{{moduleName}}.php",
            'Provider.stub' => "Providers/{{moduleName}}ServiceProvider.php",
            'Routes.stub' => "Routes/api.php",
        ];

        foreach ($stubs as $stub => $target) {
            $content = $this->files->get("{$stubPath}/{$stub}");
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
            $filePath = str_replace(array_keys($replacements), array_values($replacements), "{$modulePath}/{$target}");
            $this->files->put($filePath, $content);
        }
    }
}
