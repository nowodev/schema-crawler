<?php

namespace SchemaCrawler\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class SourceTestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:sourcetest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a crawler source test.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'WebSourceTest';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->option('feed')) {
            $this->type = 'FeedSourceTest';
        }
        if ($this->option('json')) {
            $this->type = 'JsonSourceTest';
        }

        parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/' . strtolower($this->type) . '.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return Config::get('schema-crawler.generator.' . strtolower(str_replace('Test', '', $this->type)) . '.tests_namespace');
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return base_path('tests') . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Tests';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['feed', '-f', InputOption::VALUE_NONE, 'Create a feed source test.'],
            ['json', '-j', InputOption::VALUE_NONE, 'Create a json source test.'],
        ];
    }
}