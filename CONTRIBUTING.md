# Contributing to OpenCensus for PHP

1. **Sign one of the contributor license agreements below.**
2. Fork the repo, develop, and test your code changes.
3. Send a pull request.

## Contributor License Agreements

We'd love to accept your patches! Before we can take them, we
have to jump a couple of legal hurdles.

Please fill out either the individual or corporate Contributor License Agreement
(CLA).

  * If you are an individual writing original source code and you're sure you
    own the intellectual property, then you'll need to sign an
    [individual CLA](https://developers.google.com/open-source/cla/individual).
  * If you work for a company that wants to allow you to contribute your work,
    then you'll need to sign a
    [corporate CLA](https://developers.google.com/open-source/cla/corporate).

Follow either of the two links above to access the appropriate CLA and
instructions for how to sign and return it. Once we receive it, we'll be able to
accept your pull requests.

## Setup

In order to use OpenCensus for PHP, some setup is required!

1. Install PHP. OpenCensus for PHP requires PHP 5.6 or higher. Installation of
   PHP varies depending on your system. Refer to the
   [PHP installation and configuration documentation](http://php.net/manual/en/install.php)
   for detailed instructions.

1. Install [Composer](https://getcomposer.org/download/).

    Composer is a dependency manager for PHP, and is required to isntall and use
    OpenCensus for PHP.

1. Install the project dependencies.

    ```sh
    $ composer install
    ```

## Tests

Tests are a very important part of OpenCensus for PHP. All contributions should
include tests that ensure the contributed code behaves as expected.

To run all tests, the following command may be invoked:

```sh
$ composer tests
```

## Coding Style

Please follow the established coding style in the library. OpenCensus for PHP
follows the [PSR-2](https://www.php-fig.org/psr/psr-2/) Coding Style.

You can check your code against these rules by running PHPCS with the proper
ruleset, like this:

```sh
$ vendor/bin/phpcs --standard=phpcs-ruleset
```

Coding style checks are run along with the other test suites.