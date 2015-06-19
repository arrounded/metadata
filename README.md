# Arrounded/Metadata

[![Build Status](http://img.shields.io/travis/arrounded/metadata.svg?style=flat-square)](https://travis-ci.org/arrounded/metadata)
[![Latest Stable Version](http://img.shields.io/packagist/v/arrounded/metadata.svg?style=flat-square)](https://packagist.org/packages/arrounded/metadata)
[![Total Downloads](http://img.shields.io/packagist/dt/arrounded/metadata.svg?style=flat-square)](https://packagist.org/packages/arrounded/metadata)

## Install

Via Composer

``` bash
$ composer require arrounded/metadata
```

## Usage

First add the module's service provider and facade to `config/app.php`:

```php
Arrounded\Metadata\ServiceProvider::class,
```

```php
'Metadata' => Arrounded\Metadata\Facades\Metadata::class,
```

Then somewhere in your service provider, define your application's metadata. Either by passing it an array:

```php
$this->app['arrounded.metadata']->setMetadata([
    ['url' => 'foo.com', 'title' => Homepage', 'description' => 'foobar'],
]);
```

Or by indicating it the path to a CSV file:

**metadata.csv**
```
url,title,description
foo.com,Homepage,foobar
```

```php
$this->app['arrounded.metadata']->setMetadataFromFile('metadata.csv');
```

Then in your views call the `render` method on the facade. It'll look at the current URL and find the correct metadata for the page.
You can also pass it an array of additional metadata:

```twig
{{ Metadata.render() }}
{{ Metadata.render({image: 'foo.com/logo.png'}) }}
```

By default all properties (except core ones such as title, description etc) are also wrapped in Twitter/Facebook graph metadata.
You can disable this behavior by setting which properties should not be wrapped:

```php
$this->app['arrounded.metadata']->setMetadataFromFile('metadata.csv');
$this->app['arrounded.metadata']->setUnwrapped(['property', 'other_property']);
```

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
