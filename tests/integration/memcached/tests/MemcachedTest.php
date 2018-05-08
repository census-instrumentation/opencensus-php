<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Tests\Integration\Trace\Exporter;

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Memcached as MemcachedIntegration;
use PHPUnit\Framework\TestCase;
use Memcached;

class MemcachedTest extends TestCase
{
    private $tracer;
    private static $memcachedHost;
    private static $memcachedPort;

    public static function setUpBeforeClass()
    {
        MemcachedIntegration::load();
        self::$memcachedHost = getenv('MEMCACHED_HOST') ?: '127.0.0.1';
        self::$memcachedPort = (int) (getenv('MEMCACHED_PORT') ?: 11211);
    }

    public function setUp()
    {
        if (!extension_loaded('opencensus')) {
            $this->markTestSkipped('Please enable the opencensus extension.');
        }
        opencensus_trace_clear();
        $exporter = $this->prophesize(ExporterInterface::class);
        $this->tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
    }

    public function testAddGetReplaceDelete()
    {
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->add('key1', 'bar');
        $this->assertEquals('bar', $m->get('key1'));
        $m->replace('key1', 'foo');
        $this->assertEquals('foo', $m->get('key1'));
        $m->delete('key1');
        $this->assertEquals(null, $m->get('key1'));

        $spans = $this->getSpans();

        $this->assertCount(7, $spans);
        $addSpan = $spans[1];
        $this->assertEquals('Memcached::add', $addSpan->name());
        $this->assertEquals('key1', $addSpan->attributes()['key']);
        $getSpan = $spans[2];
        $this->assertEquals('Memcached::get', $getSpan->name());
        $this->assertEquals('key1', $getSpan->attributes()['key']);
        $replaceSpan = $spans[3];
        $this->assertEquals('Memcached::replace', $replaceSpan->name());
        $this->assertEquals('key1', $replaceSpan->attributes()['key']);
        $deleteSpan = $spans[5];
        $this->assertEquals('Memcached::delete', $deleteSpan->name());
        $this->assertEquals('key1', $deleteSpan->attributes()['key']);
    }

    public function testAddGetDeleteByServerKey()
    {
        $serverKey = 'testkey';
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->addByKey($serverKey, 'key2', 'bar');
        $this->assertEquals('bar', $m->getByKey($serverKey, 'key2'));
        $m->replaceByKey($serverKey, 'key2', 'foo');
        $this->assertEquals('foo', $m->getByKey($serverKey, 'key2'));
        $m->deleteByKey($serverKey, 'key2');
        $this->assertEquals(null, $m->getByKey($serverKey, 'key2'));

        $spans = $this->getSpans();

        $this->assertCount(7, $spans);
        $addSpan = $spans[1];
        $this->assertEquals('Memcached::addByKey', $addSpan->name());
        $this->assertEquals('key2', $addSpan->attributes()['key']);
        $this->assertEquals('testkey', $addSpan->attributes()['serverKey']);
        $getSpan = $spans[2];
        $this->assertEquals('Memcached::getByKey', $getSpan->name());
        $this->assertEquals('key2', $getSpan->attributes()['key']);
        $this->assertEquals('testkey', $getSpan->attributes()['serverKey']);
        $replaceSpan = $spans[3];
        $this->assertEquals('Memcached::replaceByKey', $replaceSpan->name());
        $this->assertEquals('key2', $replaceSpan->attributes()['key']);
        $this->assertEquals('testkey', $replaceSpan->attributes()['serverKey']);
        $deleteSpan = $spans[5];
        $this->assertEquals('Memcached::deleteByKey', $deleteSpan->name());
        $this->assertEquals('key2', $deleteSpan->attributes()['key']);
        $this->assertEquals('testkey', $deleteSpan->attributes()['serverKey']);
    }

    public function testSetAppendPrepend()
    {
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->setOption(Memcached::OPT_COMPRESSION, false);
        $m->set('key3', 'abc');
        $m->append('key3', 'def');
        $m->prepend('key3', 'xyz');
        $this->assertEquals('xyzabcdef', $m->get('key3'));

        $spans = $this->getSpans();

        $this->assertCount(5, $spans);
        $setSpan = $spans[1];
        $this->assertEquals('Memcached::set', $setSpan->name());
        $this->assertEquals('key3', $setSpan->attributes()['key']);
        $appendSpan = $spans[2];
        $this->assertEquals('Memcached::append', $appendSpan->name());
        $this->assertEquals('key3', $appendSpan->attributes()['key']);
        $prependSpan = $spans[3];
        $this->assertEquals('Memcached::prepend', $prependSpan->name());
        $this->assertEquals('key3', $prependSpan->attributes()['key']);
    }

    public function testSetAppendPrependByKey()
    {
        $serverKey = 'testkey';
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->setOption(Memcached::OPT_COMPRESSION, false);
        $m->setByKey($serverKey, 'key4', 'abc');
        $m->appendByKey($serverKey, 'key4', 'def');
        $m->prependByKey($serverKey, 'key4', 'xyz');
        $this->assertEquals('xyzabcdef', $m->get('key4'));

        $spans = $this->getSpans();

        $this->assertCount(5, $spans);
        $setSpan = $spans[1];
        $this->assertEquals('Memcached::setByKey', $setSpan->name());
        $this->assertEquals('key4', $setSpan->attributes()['key']);
        $this->assertEquals('testkey', $setSpan->attributes()['serverKey']);
        $appendSpan = $spans[2];
        $this->assertEquals('Memcached::appendByKey', $appendSpan->name());
        $this->assertEquals('key4', $appendSpan->attributes()['key']);
        $this->assertEquals('testkey', $appendSpan->attributes()['serverKey']);
        $prependSpan = $spans[3];
        $this->assertEquals('Memcached::prependByKey', $prependSpan->name());
        $this->assertEquals('key4', $prependSpan->attributes()['key']);
        $this->assertEquals('testkey', $prependSpan->attributes()['serverKey']);
    }

    public function testFlushIncrementDecrement()
    {
        $m = new Memcached();
        $m->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->flush();
        $m->increment('key5', 1, 1);
        $m->increment('key5', 1);
        $this->assertEquals(2, $m->get('key5'));
        $m->decrement('key5', 1);
        $this->assertEquals(1, $m->get('key5'));

        $spans = $this->getSpans();
        $this->assertCount(7, $spans);
        $flushSpan = $spans[1];
        $this->assertEquals('Memcached::flush', $flushSpan->name());
        $incrementSpan = $spans[2];
        $this->assertEquals('Memcached::increment', $incrementSpan->name());
        $this->assertEquals('key5', $incrementSpan->attributes()['key']);
        $decrementSpan = $spans[5];
        $this->assertEquals('Memcached::decrement', $decrementSpan->name());
        $this->assertEquals('key5', $decrementSpan->attributes()['key']);
    }

    public function testFlushIncrementDecrementByKey()
    {
        $serverKey = 'testkey';
        $m = new Memcached();
        $m->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->flush();
        $m->incrementByKey($serverKey, 'key5', 1, 1);
        $m->incrementByKey($serverKey, 'key5', 1);
        $this->assertEquals(2, $m->getByKey($serverKey, 'key5'));
        $m->decrementByKey($serverKey, 'key5', 1);
        $this->assertEquals(1, $m->getByKey($serverKey, 'key5'));

        $spans = $this->getSpans();
        $this->assertCount(7, $spans);
        $flushSpan = $spans[1];
        $this->assertEquals('Memcached::flush', $flushSpan->name());
        $incrementSpan = $spans[2];
        $this->assertEquals('Memcached::incrementByKey', $incrementSpan->name());
        $this->assertEquals('key5', $incrementSpan->attributes()['key']);
        $this->assertEquals('testkey', $incrementSpan->attributes()['serverKey']);
        $decrementSpan = $spans[5];
        $this->assertEquals('Memcached::decrementByKey', $decrementSpan->name());
        $this->assertEquals('key5', $decrementSpan->attributes()['key']);
        $this->assertEquals('testkey', $decrementSpan->attributes()['serverKey']);
    }

    public function testCas()
    {
        $cas = null;
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $ips = $m->get('ip_block', null, $cas);
        $m->cas($cas, 'ip_block', ['127.0.0.1']);

        $spans = $this->getSpans();
        $this->assertCount(3, $spans);
        $casSpan = $spans[2];
        $this->assertEquals('Memcached::cas', $casSpan->name());
        $this->assertEquals('ip_block', $casSpan->attributes()['key']);
    }

    public function testCasByKey()
    {
        $serverKey = 'serverkey';
        $cas = null;
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $ips = $m->get('ip_block', null, $cas);
        $m->casByKey($cas, $serverKey, 'ip_block', ['127.0.0.1']);

        $spans = $this->getSpans();
        $this->assertCount(3, $spans);
        $casSpan = $spans[2];
        $this->assertEquals('Memcached::casByKey', $casSpan->name());
        $this->assertEquals('ip_block', $casSpan->attributes()['key']);
    }

    public function testSetGetMulti()
    {
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->setMulti([
            'foo' => 'bar',
            'asdf' => 'qwer'
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'asdf' => 'qwer'
        ], $m->getMulti(['foo', 'asdf']));

        $spans = $this->getSpans();
        $this->assertCount(3, $spans);
        $setSpan = $spans[1];
        $this->assertEquals('Memcached::setMulti', $setSpan->name());
        $this->assertEquals('foo,asdf', $setSpan->attributes()['key']);
        $getSpan = $spans[2];
        $this->assertEquals('Memcached::getMulti', $getSpan->name());
        $this->assertEquals('foo,asdf', $getSpan->attributes()['key']);
    }

    public function testSetGetMultiByKey()
    {
        $serverKey = 'testkey';
        $m = new Memcached();
        $m->addServer(self::$memcachedHost, self::$memcachedPort);
        $m->setMultiByKey($serverKey, [
            'foo' => 'bar',
            'asdf' => 'qwer'
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'asdf' => 'qwer'
        ], $m->getMultiByKey($serverKey, ['foo', 'asdf']));

        $spans = $this->getSpans();
        $this->assertCount(3, $spans);
        $setSpan = $spans[1];
        $this->assertEquals('Memcached::setMultiByKey', $setSpan->name());
        $this->assertEquals('foo,asdf', $setSpan->attributes()['key']);
        $this->assertEquals('testkey', $setSpan->attributes()['serverKey']);
        $getSpan = $spans[2];
        $this->assertEquals('Memcached::getMultiByKey', $getSpan->name());
        $this->assertEquals('foo,asdf', $getSpan->attributes()['key']);
        $this->assertEquals('testkey', $getSpan->attributes()['serverKey']);
    }

    private function getSpans()
    {
        $this->tracer->onExit();
        return $this->tracer->tracer()->spans();
    }
}
