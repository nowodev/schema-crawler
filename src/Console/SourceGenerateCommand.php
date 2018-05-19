<?php

namespace SchemaCrawler\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SourceGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'source:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new source.';

    protected $sourceAttributes = [];

    protected $ignoreFields = [
        'created_at',
        'updated_at',
    ];

    protected $source = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $sourceModelClass = config('schema-crawler.source_model');

        $this->source = new $sourceModelClass();

        $this->sourceAttributes = array_values(
            array_diff(Schema::getColumnListing((new $sourceModelClass())->getTable()), $this->ignoreFields)
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function handle()
    {

        $sourceModelName = strtolower((new \ReflectionClass($this->source))->getShortName());

        foreach ($this->sourceAttributes as $attribute) {
            $value = trim($this->ask("What should be the $attribute field of the $sourceModelName? Leave blank for the default value."));
            if (! is_null($value) AND $value != '') {
                $this->source->{$attribute} = $value;
            }
        }

        $this->source->save();

        $options = [
            'name' => array_slice(explode("\\", $this->source->getCrawlerClassName()), -1)[0],
        ];

        if ($this->confirm("Does this $sourceModelName have a feed?")) {
            $options['--feed'] = true;
        }

        $this->call('make:source', $options);
    }
}
