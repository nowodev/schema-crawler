<?php


namespace SchemaCrawler\Console;


use Illuminate\Console\GeneratorCommand;

class WebSourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:websource';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a crawler source.';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'WebSource';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/websource.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . config('schema-crawler.generator.websource.namespace');
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
        $class = str_replace('DummyParentWebSource', config('schema-crawler.generator.websource.parent_class'), $class);

        $attributes = array_map(function ($e) {
            return "'$e' => '',";
        }, array_keys(config('schema-crawler.attributes_to_crawl')));

        $class = str_replace('\'DummyAttributes\'', implode("\n", $attributes));
        return $class;
    }
}