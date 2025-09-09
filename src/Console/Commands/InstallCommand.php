<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'naptab:install 
                            {--force : Overwrite existing files}';

    protected $description = 'Install NapTab configuration and service provider';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Installing NapTab...');

        $this->publishServiceProvider();
        
        $this->info('NapTab installed successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Register app/Providers/NapTabServiceProvider.php in bootstrap/providers.php');
        $this->line('2. Customize configuration in app/Providers/NapTabServiceProvider.php');
        $this->line('3. Use <livewire:naptab> in your Blade templates');
        $this->line('');
        $this->warn('Remember: Add App\\Providers\\NapTabServiceProvider::class to bootstrap/providers.php');
        $this->line('');
        $this->info('Configuration uses package enums - see comments in service provider for available values.');

        return self::SUCCESS;
    }

    protected function publishServiceProvider(): void
    {
        $stub = $this->getStub('service-provider');
        $path = app_path('Providers/NapTabServiceProvider.php');

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->warn('NapTabServiceProvider already exists. Use --force to overwrite.');
            return;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);

        $this->info('Published: ' . $path);
    }


    protected function getStub(string $name): string
    {
        $stubPath = __DIR__ . "/../../stubs/{$name}.stub";
        
        if (!$this->files->exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return $this->files->get($stubPath);
    }
}