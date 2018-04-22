<?php


namespace SchemaCrawler\Console;


use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;

class AdapterMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:adapter {name}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a crawler adapter.';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Adapter';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/adapter.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {

        return $rootNamespace . '\\' . Config::get('schema-crawler.generator.adapter.namespace');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $class = parent::buildClass($name);
        $class = str_replace('DummyParentAdapter', Config::get('schema-crawler.generator.adapter.parent_class'), $class);

        $attribute = camel_case(array_keys(Config::get('schema-crawler.attributes_to_crawl'))[0]);
        $class = str_replace('\'DummyAttribute\'', ucfirst($attribute));
        $class = str_replace('\'dummyAttribute\'', $attribute);
        return $class;
    }
}