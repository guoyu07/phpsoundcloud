# Alcohol\SoundCloud

A client written in PHP for SoundCloud's API.

[![Build Status](https://img.shields.io/travis/alcohol/phpsoundcloud/master.svg?style=flat-square)](https://travis-ci.org/alcohol/phpsoundcloud)
[![License](https://img.shields.io/packagist/l/alcohol/phpsoundcloud.svg?style=flat-square)](https://packagist.org/packages/alcohol/phpsoundcloud)
[![Version](https://img.shields.io/packagist/v/alcohol/phpsoundcloud.svg?style=flat-square)](https://packagist.org/packages/alcohol/phpsoundcloud)


## Installing

Either install directly from command line using composer:

``` sh
$ composer require "alcohol/phpsoundcloud:~2.0"
```

or manually include it as a dependency in your composer.json:

``` javascript
{
    "require": {
        "alcohol/phpsoundcloud": "~2.0"
    }
}
```

## Using

``` php
<?php

use Alcohol\SoundCloud;

$options = [
    'client_id' => 'yourId',
    'client_secret' => 'yourSecret',
    'redirect_uri' => 'http://domain.tld/redirect'
];

$soundcloud = new SoundCloud($options);

```

Documentation might be available [here](http://alcohol.github.io/docs).

## Contributing

Feel free to submit a pull request or create an issue.

## License

Alcohol\SoundCloud is licensed under the MIT license.
