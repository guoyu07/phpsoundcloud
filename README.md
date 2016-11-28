# ABANDONED

This package is not maintained. If you would like to take ownership, open a Github issue and a transfer can be arranged.

# alcohol/phpsoundcloud

A client written in PHP for SoundCloud's API.

[![Build Status](https://img.shields.io/travis/alcohol/phpsoundcloud/master.svg?style=flat-square)](https://travis-ci.org/alcohol/phpsoundcloud)
[![License](https://img.shields.io/packagist/l/alcohol/phpsoundcloud.svg?style=flat-square)](https://packagist.org/packages/alcohol/phpsoundcloud)

## Installing

Either install directly from command line using composer:

``` sh
$ composer require "alcohol/phpsoundcloud:~4.0"
```

or manually include it as a dependency in your composer.json:

``` javascript
{
    "require": {
        "alcohol/phpsoundcloud": "~4.0"
    }
}
```

## Using

``` php
<?php

use Alcohol\SoundCloud\Client;

$parameters = [
    'client_id' => 'yourId',
    'client_secret' => 'yourSecret',                // optional, but required
    'redirect_uri' => 'http://domain.tld/redirect'  // for retrieving oauth token
];

$soundcloud = new Client($parameters);
$soundcloud->login($username, $password);

$stream = $soundcloud->getStream();

// see class for further documentation

```

## Contributing

Feel free to submit a pull request or create an issue.

## License

Alcohol\SoundCloud is licensed under the MIT license.
