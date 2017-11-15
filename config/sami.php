<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$root_dir = __DIR__ . '/../';

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/../src');

$versions = GitVersionCollection::create($root_dir)
    ->addFromTags('v2.0.*')
    ->add('master', 'master branch');

return new Sami($iterator, [
    'versions'  => $versions,
    'build_dir' => __DIR__ . '/../docs'
]);
