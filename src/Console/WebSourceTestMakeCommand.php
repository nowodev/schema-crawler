<?php


namespace SchemaCrawler\Console;


use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;

class WebSourceTestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:websource:test {name}';
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
    protected $type = 'SourceTest';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/websource_test.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return Config::get('schema-crawler.generator.websource.tests_namespace');
    }
}