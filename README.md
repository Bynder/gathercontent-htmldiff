# Htmldiff

[![Build Status](https://travis-ci.org/gathercontent/htmldiff.png?branch=master)](https://travis-ci.org/gathercontent/htmldiff)

Compare two HTML strings. This library converts HTML into a form that can be diffed using the algorithm found in Git or Linux (comparing entire lines of code) to achieve greater accuracy, then decodes it back into a valid HTML.


## Requirements

- PHP 5.3.0 or later
- [Tidy](http://php.net/manual/en/intro.tidy.php) extension for PHP


## Installation

Add Htmldiff to your `composer.json` file:

```json
"require": {
    "gathercontent/htmldiff": "0.2.*"
}
```

Get composer to install the package:

```bash
$ composer update gathercontent/htmldiff
```


## Usage

The input is going to be parsed by Tidy before diffing. Here's a few examples:

```php
$old = '<span>This is a string</span>';
$new = '<span>This is a text</span>';

$htmldiff = new Htmldiff;
$result = $htmldiff->diff($old, $new);

// result: <span>This is a <del>string</del><ins>text</ins></span>
```

```php
$old = '<p>Hello world, how do you do</p>';
$new = '<p>Hello world, how do <strong>you</strong> do</p>';

$htmldiff = new Htmldiff;
$result = $htmldiff->diff($old, $new);

// result: <p>Hello world, how do <del>you</del><strong><ins>you</ins></strong> do</p>
```

```php
$old = '<p>Hello world</p><p>How do you do</p>';
$new = '<p>Hello world</p><ul><li>first point</li><li>second point</li></ul><p>How do you do</p>';

$htmldiff = new Htmldiff;
$result = $htmldiff->diff($old, $new);

// result: <p>Hello world</p><ul><li><ins>first point</ins></li><li><ins>second point</ins></li></ul><p>How do you do</p>
```

## Testing

Run unit tests:

``` bash
$ ./vendor/bin/phpunit
```

Test compliance with [PSR2 coding style guide](http://www.php-fig.org/psr/psr-2/):

``` bash
$ ./vendor/bin/phpcs --standard=PSR2 ./src
```


# Licence

The MIT License (MIT) - see `README.md`

