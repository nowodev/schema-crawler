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

    protected $sourceModelClass = null;

    protected $sourceAttributes = [];

    protected $ignoreFields = [
        'created_at',
        'updated_at'
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->sourceModelClass = config('schema-crawler.source_model');
        $this->sourceAttributes = array_values(
            array_diff(Schema::getColumnListing((new $this->sourceModelClass())->getTable()), $this->ignoreFields)
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sourceModel = new $this->sourceModel();
        foreach ($this->sourceAttributes as $attribute) {
            $value = trim($this->ask("What should be the $attribute of the source? Leave blank for the default value."));
            if (!is_null($value)) {
                $sourceModel->{$attribute} = $value;
            }
        }
        $sourceModel->save();

        $options = [
            'name' => $sourceModel->getCrawlerClassName()
        ];

        if ($this->confirm('Does this source have a feed? (Default: no)')) {
            $options['--feed'] = true;
        }

        $this->call('make:source', $options);
    }
}
