<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;
use Sami\Parser\Filter\FilterInterface;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\PropertyReflection;

$root_dir = __DIR__ . '/../';

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/../src');

$versions = GitVersionCollection::create($root_dir)
    ->addFromTags('v0.*')
    ->add('master', 'master branch');

$sami = new Sami($iterator, [
    'versions'  => $versions,
    'build_dir' => __DIR__ . '/../docs/public/api/%version%/',
    'title' => 'OpenCensus PHP API',
    'cache_dir' => __DIR__ . '/../cache/%version%/'
]);

class VisibleFilter implements FilterInterface
{
    public function acceptClass(ClassReflection $class)
    {
        return true;
    }

    public function acceptMethod(MethodReflection $method)
    {
        return !$method->isPrivate() && empty($method->getTags('internal'));
    }
    public function acceptProperty(PropertyReflection $property)
    {
        return !$property->isPrivate();
    }
}

$sami['filter'] = function () {
    return new \VisibleFilter();
};

return $sami;
