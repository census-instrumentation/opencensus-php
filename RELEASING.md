# Releasing OpenCensus PHP

The PHP library and extension are released independently of each other.

## Packagist Package

1. Bump `VERSION` constant in [`src/Version.php`][version-file]

1. Create a GitHub release.

1. Click `Update` from the admin view of the [opencensus/opencensus][packagist] package.

## PECL Extension

1. Update the `PHP_OPENCENSUS_VERSION` package version constant in `php_opencensus.h`.

1. Update the `releases.yaml` file with a new release and description.

1. Run the extension release script:

    `php scripts/ext_release.php > ext/package.xml`

1. Go to the extension directory:

    `cd ext`

1. Build a PEAR package archive.

    `pear package`

1. Upload the new release to PECL from the [admin console][pecl-upload].

[version-file]: https://github.com/census-instrumentation/opencensus-php/tree/master/src/Version.php
[packagist]: https://packagist.org/packages/opencensus/opencensus
[pecl-upload]: https://pecl.php.net/release-upload.php
