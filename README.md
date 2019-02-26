# Laravel Schema Crawler
A Laravel framework extension to crawl unstructured data from websites.

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Defining the Models](#defining-the-models)
  - [Defining the Attributes & Validation](#defining-the-attributes--validation)
  - [Defining the Adapter](#defining-the-adapter)
- [Usage](#usage)
  - [Sources](#sources)
  - [Data Handling](#data-handling)
  - [Adapters](#adapters)
  - [Running the Crawler](#running-the-crawler)
  - [Events](#events)
- [Advanced Configuration](#advanced-configuration)
  - [Generator](#generator)
  - [Chrome](#chrome)
- [Testing](#testing)

## Introduction

The Schema Crawler package crawls, validates and manipulates data from given websites. To get a better understanding of how this works, let's take a closer look at the lifecycle of the schema crawler.

### Lifecycle

![Lifecycle of the Schema Crawler](https://i.imgur.com/KX1WVhv.png)



#### Source

The source is a website, which contains all the data we want to crawl. The structure of the website has to be the following:

The website must have an **Overview Page**, which has to be a listing of all the schemas that should be crawled. The listing only needs to contain a link to the **Detail Page**, where all the information of the schema can be found.

![Overview Page & Detail Page](https://i.imgur.com/Jc3Cdcb.png)



#### Overview Crawler

The Overview Crawler process gets all the links of the overview page of the website. When it's finished, it will start a Detail Crawler process for each of the links.

#### Detail Crawler

The Detail Crawler process requests the passed detail page url, crawls all the data and puts it into an array.

#### Adapter

The Adapter will get the raw data and manipulates it (e.g. removes unnecessary spaces, parses numbers, etc.).

#### Validation

After the data has been manipulated by the adapter, it will run through a validation. If the validation succeeds, the data will be passed to the schema model and can be persisted there.

## Requirements

This package requires the [Puppeteer Chrome Headless Node library](https://github.com/GoogleChrome/puppeteer).

If you want to install it on Ubuntu 16.04 you can do it like this:
```bash
sudo apt-get update
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get install -y nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget
sudo npm install --global --unsafe-perm puppeteer
sudo chmod -R o+rx /usr/lib/node_modules/puppeteer/.local-chromium
```

## Installation

As this is a private repo, you need to add the repository to your composer.json file.

```json
"repositories": [
    {
        "type": "github",
        "url":  "git@github.com:redahead/schema-crawler.git"
    }
]
```

After you added the repo to your composer configuration, you can install the package via the composer by running the following command:

```bash
composer require redahead/schema-crawler
```

The package will automatically register itself.

To publish the migration and configuration files, you need to run

```bash
php artisan vendor:publish --provider="SchemaCrawler\SchemaCrawlerServiceProvider"
```

 in your project directory.

This will create a config file at `config/schema-crawler.php`.

It is also recommended to create a database table for failed jobs. Laravel already comes with a [predefined table](https://laravel.com/docs/5.6/queues#dealing-with-failed-jobs) for failed jobs. You can generate it by running:

```bash
php artisan queue:failed-table
php artisan migrate
```



## Configuration

### Defining the Models

To get started you need to define your model that will be handled as the default schema. You can do this by adding the `SchemaCrawler\Models\Schema` Trait to your model.

```php
use SchemaCrawler\Models\Schema;

class Book extends Model
{
    use Schema;
```

In addition you have to define your schema model in the config file (`config/schema-crawler.php`).

```php
/*
|--------------------------------------------------------------------------
| Schema Model
|--------------------------------------------------------------------------
|
| Define which model should be handled as the default schema.
|
*/
'schema_model'        => \App\Book::class,
```

Now you have to repeat those two steps with the source model. The source model should include the `SchemaCrawler\Models\Source` trait.

```php
use SchemaCrawler\Models\Source;

class Bookstore extends Model
{
    use Source;
```

Also, you have to define the source model in the config file (`config/schema-crawler.php`).

```php
/*
|--------------------------------------------------------------------------
| Source Model
|--------------------------------------------------------------------------
|
| Define which model should be handled as the web source.
|
*/
'source_model'        => \App\Bookstore::class,
```

### Defining the Attributes & Validation

After you defined the schema and source models, you should define the attributes that should be crawled. You can do this in the config file at `config/schema-crawler.php`.

```php
/*
|--------------------------------------------------------------------------
| Attributes
|--------------------------------------------------------------------------
|
| Define the name and specification of the attributes that should be
| crawled. The attributes will be validated after they have been modified
| by the adapter.
|
*/
'attributes_to_crawl' => [
	'title'		=> 'required',
	'author'	=> 'required',
	'isbn'		=> 'required',
],
```

**Important:** The name of the attributes don't have to correspond to the column name of the schema model! 

In addition of the name of the attribute, you also have to define a validation for the attributes. You can use [all available validation rules by Laravel](https://laravel.com/docs/master/validation#available-validation-rules), or you can even [implement your own](https://laravel.com/docs/master/validation#custom-validation-rules).

The validation will be performed **after** the attributes have been processed by the adapter.

If you want to validate your attributes **before** they will be manipulated by the adapter, you have to set the `raw_validation` in the config file:

```php
/*
|--------------------------------------------------------------------------
| Raw Data Validation
|--------------------------------------------------------------------------
|
| Define the validation of the raw crawled data, before it will be
| manipulated by the adapter.
|
*/
'raw_validation'      => [
     'title' => 'min:2'
],
```

### Defining the Adapter

Everytime the Schema Crawler gets the data from a source, all crawled attributes will be processed by an adapter. Therefore you need to create and define an adapter to get started.

You can create an adapter by using the `php artisan make:adapter` command. For Example:

```bash
php artisan make:adapter BookAdapter
```

This will create a new adapter under the `App\Crawler\Adapters `  namespace.

You also need to define your default adapter in the config file (`config/schema-crawler.php`).

```php
/*
|--------------------------------------------------------------------------
| Default Adapter
|--------------------------------------------------------------------------
|
| Define which adapter should be used by default.
|
*/
'default_adapter'     => \App\Crawler\Adapters\BookAdapter::class,
```

### Scraperapi

If you want to use Scraper API instead of Chrome Headless crawler, you need to set API key in the config file (`config/schema-crawler.php`).

```php
 /*
|--------------------------------------------------------------------------
| Scraperapi API key
|--------------------------------------------------------------------------
|
| Define the scraperapi API key that should be used.
|
*/

'scraperapi_key' => env('SCRAPERAPI_KEY', null),
```



## Usage

### Sources

#### Creating the class file

To create a source, you can use the `php artisan make:source` command. For Example:

```bash
php artisan make:source GoodBooksStore
```

This will create a new source under the `App\Crawler\Adapters `  namespace. In addition to that, it will create a test under the `\Tests\Feature\Crawler\Sources` namespace.

#### Defining the source

After creating the source class, you need to define its attributes.

##### Source Urls

The source urls attribute is an array which contains urls of all overview pages.

```php
/**
 * The urls of the pages that contain links to the schemas.
 * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
 *
 * @var array
 */
protected $sourceUrls = [
    [
        'url' => 'https://www.goodbooksstore.com/all-books'
    ],
];
```

It is also possible to define specific schema attributes for each overview page. Every schema that will be crawled from the defined overview page will have the given attribute. 

```php
protected $sourceUrls = [
    [
		'url'	=> 'https://www.coolbooksstore.com/crime',
		'overwriteAttributes' => [
			'category' => 'crime'
        ],
		'url'	=> 'https://www.coolbooksstore.com/history',
		'overwriteAttributes' => [
			'category' => 'history'
        ],
    ],
];
```

##### Adapter Options

You can specify options that will be passed to the adapter.

```php
/**
 * Options defined here will be accessible in the adapter.
 *
 * @var array
 */
protected $adapterOptions = ['convertIsbn' => true];
```

It is also possible to define a specific adapter. By default the defined adapter in the `config/schema-crawler.php` file will be used.

```php
/**
 * The default adapter that will be used can be overwritten here.
 *
 * @var string
 */
protected $adapter = SpecialAdapter::class;
```

##### Crawler Settings

The schema-crawler needs to know which type of crawler should be used. Right now supported types are: `chrome_headless` and `scraperapi`. You can pass there shop specific `blacklist` (array of regex patterns used to filter requests) and `excluded` (array of resource types used for filtering the requests) settings that will affect `chrome_headless` crawler, while you can also pass `scraperapi_render_js` setting to specify should Javascript be rendered or not if you are using `scraperapi` crawler type . 

⚠️ Global and shop specific settings for `blacklist` and `excluded` will be not used if crawler type is `scraperapi`!

```php
/**
     * Shop specific crawler settings.      
     *
     * @var array
     */
    protected $crawlerSettings = [  
        'type'                  => 'chrome_headless', 
        'scraperapi_render_js'  => false,
        'blacklist'             => [], 
        'excluded'              => [],
    ];
```

##### CSS Selectors

The crawler needs to know where to find the needed elements on the website. Therefore you need to define the CSS selectors of these elements.

```php
/**
 * The CSS selectors of the paging and the attributes of the schema.
 *
 * @var array
 */
protected $cssSelectors = [
    'overview' => [
        'detailPageLink' => '.product-name a',
        'nextPageLink'   => '.pages a.next',
    ],
    'detail'   => [
        'title'    => '.title',
        'author'   => '.author',
        'isbn'     => '.isbn',
        'category' => null
    ],
];
```

The CSS selectors array is split up in two parts: 

`overview`, which defines the needed selectors on the overview page and `detail` for the schema attributes of the detail page.

`detailPageLink` describes the links to the detail page of the schemas that should be crawled. These should be an `<a>` element, as the `href` attribute will be used for the url. It doesn't matter if only relative urls are used here - internally the crawler will automatically create absolute urls.

`nextPageLink`  describes the link to the next page of the overview page. You can set it to `null` if the overview page doesn't use paging.  ⚠️ **Important**: Make sure the element is **not** available on the last page anymore, otherwise the crawler will end in an infinite loop.

When you define the CSS selectors for the schema attributes, the inner text of the element will be used by default. If you want to use the value of a specific element attribute, you can do it like that:

````````````php
'detail'   => [
	'title'    => ['meta[property="og:title"]' => 'content'],
	'author'   => ['.author-element' => 'data-author'],
	'isbn'     => ['[itemprop="mpn"]' => 'value'],
	'category' => null
],
````````````

If you want to get an array of elements for the schema attribute, add the `array` keyword. 

````````````php
'detail'   => [
	'title'    => ['meta[property="og:title"]' => 'content'],
	'isbn'     => ['[itemprop="mpn"]' => 'value'],
	'keywords' => ['.keywords li' => 'array']
	'categories' => ['.categories li' => 'array|data-category']
],
````````````

Sometimes the detail page includes an json array with the schema attributes. For example:

```html
<script type="application/ld+json">
	{
		"@context" : "http://schema.org",
		"@type" : "Book",
		"author" : "Steven Pinker",
		"title" : "The Better Angels of Our Nature : A History of Violence and Humanity",
		"isbn" : "9780141034645"
	}
</script>
```

You can access this easily by using the `json` keyword.

````````````php
'detail'   => [
	'title'		=> ['title' => 'json'],
	'isbn'		=> ['isbn' => 'json'],
	'author'	=> ['author' => 'json']
],
````````````

##### Custom Attributes

If you can't specify an element via a single CSS selector, you can define a custom function for it. You can access the DOM of the website by the passed `Symfony\Component\DomCrawler\Crawler` instance.

```php
public function getIsbn(Crawler $crawler)
{
    $description = $crawler->filter('.short-description');

    if (! $description->count()) {
        return null;
    }

    return str_from($description->text(), "ISBN: ");
}
```

#### Feeds

In addition to normal websites, you can also add XML feeds as sources. You can do this by adding the `--feed` option to the `make:source` command.

```bash
php artisan make:source CoolBooksStore --feed
```

The structure of the feed source looks very similar to the normal web source, but it contains some additional parameters at the feed urls attribute.

`schemaNode` describes the node in the XML file that contains information about the schema. All attributes inside this node will be accessible for the path selectors.

Sometimes feeds are zipped. Therefore you can set the `zipped` attribute if the crawler needs to extract the file first. If this attribute is not given, it will be default to `false`. 

#### Testing the sources

The schema crawler automatically generates tests for each source by default. These are located under `tests\Feature\Crawler\Sources`. Each source test extends the `SchemaCrawler\Testing\WebSourceTest` (for websites) or the `SchemaCrawler\Testing\FeedSourceTest` (for feeds).

Normally you don't need to overwrite the parent tests or add additional tests. **Everything will work out of the box.** But you can extend them if it's needed.

⚠️ Crawler settings specified in the web source class you are testing will be used when you run tests!

You can run a single source test by calling `crawler:test` and specifing the [Route Key Name](https://laravel.com/docs/5.6/routing#implicit-binding) of the source model. By default this is the ID.

```bash
php artisan crawler:test 1
```

⚠️ It is best practice to change the route key to a more readable parameter, as for example the slug. [Read the official Laravel documentation](https://laravel.com/docs/5.6/routing#implicit-binding) to see how to do this.

### Data Handling

After the data has been crawled, validated and manipulated by the adapter, it will be passed to the `createFromCrawlerData` or `updateFromCrawlerData` function of the defined schema model. This is the place where you should persist the data. E.g:

```php
/**
 * This function will be called after the attributes of a schema have been crawled
 * and no existing schema has been found.
 *
 * @param array $data Attributes that have been crawled.
 */
public static function createFromCrawlerData(array $data)
{
    static::create($data);
}
```

```php
/**
 * This function will be called after the attributes of the schema have been crawled.
 *
 * @param array $data Attributes that have been crawled.
 */
public function updateFromCrawlerData(array $data)
{
    $this->update([
        'category' => array_merge($data['category'], $this->category)
    ]);
}
```

The data array contains all the attributes you defined in the source. In addition to that it contains the following attributes:

- `url` The url of the crawled schema
- `sourceId` The database ID of the source
- `adapterOptions` The adapter options that have been specified in the source

### Adapters

Adapters are used to modify and standardize the crawled data. 

You can create an adapter by using the `php artisan make:adapter` command. For Example:

```bash
php artisan make:adapter BookAdapter
```

This will create a new adapter under the `App\Crawler\Adapters `  namespace.

In the adapter you can define a getter method for each attribute you want to modify. You can access the `$rawData` object of the class to get the crawled attributes. Additionally you can get the adapter options via the `getOption()` function of the class. 

```php
/**
 * Manipulate the isbn of the crawled product.
 *
 * @return string
 */
public function getIsbn()
{
    $isbn = trim($this->rawData->isbn);

    if ($this->getOption('convertIsbn', false)) {
        $isbn = '978' . $isbn . '3';
    }

    return $isbn;
}
```

The name of the function has to be `get` followed by the name of the attribute in [camel case](https://laravel.com/docs/5.6/helpers#method-camel-case). 

### Running the Crawler

Before running the crawler, make sure you defined the correct crawler class file name for each source. This should be defined in the source model.

```php
/**
 * Get the crawler class name of the source.
 *
 * @return string
 */
public function getCrawlerClassName(): string
{
    return "App\Crawler\Sources\\".ucfirst(camel_case($this->name));
}
```

#### Using the SchemaCrawler class

One option to run the crawler is by using the `SchemaCrawler\SchemaCrawler` class. 

```php
use SchemaCrawler\SchemaCrawler;

SchemaCrawler::run();
```

You can also run the crawler on a single source by passing the [Route Key Name](https://laravel.com/docs/5.6/routing#implicit-binding) (by default, this should be the ID). 

```php
use SchemaCrawler\SchemaCrawler;

$source = App\Bookstore::first();
SchemaCrawler::runSource($source->id);
```

#### Using the Command Line

It is also possible to start the crawler via the command line. You can run

```bash
php artisan crawler:start
```

to crawl all the sources, or use

```
php artisan crawler:start 1
```

to only crawl a single source.

#### Failed Crawls

If a schema couldn't be crawled successfully due to an invalid format, it will be saved in the `invalid_schemas` database table. The table contains a couple of helpful fields to determine why the schema validation has failed.

`validation_error` displays the message that has been thrown by the validator.

`raw_data` contains all the crawled attributes in the original format from the website. 

`extracted_data` contains all the attributes after they have been processed by the adapter. This field will be `null` if the raw validation has failed.

### Events

After all urls of the overview pages of a source have been crawled, a `SchemaCrawler\Events\UrlsCrawled` event will be submitted. You can listen to this event, by adding a new listener via the `php artisan make:listener`  command and registering it in the `App\Providers\EventServiceProvider`.

```php
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        SchemaCrawler\Events\UrlsCrawled::class => [
            App\Listeners\MarkSoldOutBooks::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
```

## Advanced Configuration

### Generator 

If you use the generator commands to create adapters or sources, you might want to change the default namespace and/or parent class. You can do this in the `config/schema-crawler.php` configuration file.

```php
/*
|--------------------------------------------------------------------------
| Generator Settings
|--------------------------------------------------------------------------
|
| Define the defaults of the classes generated by the Artisan make command.
|
*/
'generator'           => [
    'websource' => [
        'parent_class' => \SchemaCrawler\Sources\WebSource::class,
        'namespace'    => '\App\Crawler\Sources',
        'tests_namespace' => '\Tests\Feature\Crawler\Sources'
    ],
    'feedsource' => [
        'parent_class' => \SchemaCrawler\Sources\FeedSource::class,
        'namespace'    => '\App\Crawler\Sources',
        'tests_namespace' => '\Tests\Feature\Crawler\Sources'
    ],
    'adapter'   => [
        'parent_class' => \SchemaCrawler\Adapters\Adapter::class,
        'namespace'    => '\App\Crawler\Adapters'
    ]
]
```

### Chrome

Internally the crawler uses the [milivojsa/laravel-chrome](https://github.com/milivojsa/laravel-chrome) component to request websites. Therefore, if you want to change the user agent, chrome path, viewport or other parameters, check out the [documentation](https://github.com/milivojsa/laravel-chrome#configuration).

## Testing

You can run the tests by using

```bash
composer test
```