# Laravel Schema Crawler
A Laravel framework extension to crawl unstructured data from websites.

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
        "url":  "git@github.com:helloiamlukas/schema-crawler.git"
    }
]
```

After you added the repo to your composer configuration, you can install the package via the composer by running the following command:

```bash
composer require helloiamlukas/schema-crawler
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
