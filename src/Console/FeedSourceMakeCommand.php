<?php


namespace SchemaCrawler\Console;


use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;

class FeedSourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:feedsource';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a feed crawler source.';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'FeedSource';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if (!$this->option('no-test')) {
            $this->createTest();
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/feedsource.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return Config::get('schema-crawler.generator.feedsource.namespace');
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
        $class = str_replace('DummyParentFeedSource', '\\' . Config::get('schema-crawler.generator.feedsource.parent_class'), $class);

        $attributes = array_map(function ($e) {
            return "'$e' => ''";
        }, array_keys(Config::get('schema-crawler.attributes_to_crawl')));

        $class = str_replace('\'DummyAttributes\'', implode(",\n\t\t", $attributes), $class);
        return $class;
    }

    /**
     * Create a test class for the web source.
     *
     * @return void
     */
    protected function createTest()
    {
        $this->call('make:feedtest', [
            'name' => $this->argument('name') . 'Test'
        ]);
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['no-test', '-t', InputOption::VALUE_NONE, 'Do not create a test for the web source.'],
        ];
    }
}