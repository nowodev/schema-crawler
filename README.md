# Laravel Schema Crawler
A Laravel framework extension to crawl unstructured data from websites.

## Requirements

This package requires a stable version of Google Chrome (v63 or higher).

If you want to install it on Ubuntu 16.04 you can do it like this:
```
sudo apt-get update
sudo apt-get install -y libappindicator1 fonts-liberation
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo dpkg -i google-chrome*.deb
```

## Installation

As this is a private repo, you need to add the repository to your composer.json file.

```
"repositories": [
    {
        "type": "github",
        "url":  "git@github.com:helloiamlukas/schema-crawler.git"
    }
]
```

After you added the repo to your composer configuration, you can install the package via the composer by running the following command:

```
composer require helloiamlukas/schema-crawler
```

The package will automatically register itself.

To publish the migration and configuration files, you need to run

```
php artisan vendor:publish --provider="SchemaCrawler\SchemaCrawlerServiceProvider"
```

 in your project directory.

This will create a config file at `config/schema-crawler.php`.

## Configuration

### Defining the Models

To get started you need to define your model that will be handled as the default schema. You can do this by adding the `SchemaCrawler\Models\Schema` Trait to your model.

```
use SchemaCrawler\Models\Schema;

class Book extends Model
{
    use Schema;
```

In addition you have to define your schema model in the config file (`config/schema-crawler.php`).

```
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

```
use SchemaCrawler\Models\Source;

class Bookstore extends Model
{
    use Source;
```

Also, you have to define the source model in the config file (`config/schema-crawler.php`).

```
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

```
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

In addition of the name of the attribute, you also have to define a validation. You can use [all available validation rules by Laravel](https://laravel.com/docs/master/validation#available-validation-rules), or you can even [implement your own](https://laravel.com/docs/master/validation#custom-validation-rules).

The validation will be performed **after** the attributes have been processed by the adapter.

If you want to validate your attributes **before** they will manipulated by the adapter, you have to set the `raw_validation` in the config file:

```
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

Every time the Schema Crawler gets the data from a source, all crawled attributes will be processed by an adapter. Therefore you need to create and define an adapter to get started.

You can create an adapter by using the `php artisan make:adapter` command. For Example:

```
php artisan make:adapter BookAdapter
```

This will create a new adapter under the `App\Crawler\Adapters `  namespace.

You also need to define your default adapter in the config file (`config/schema-crawler.php`).

```
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

