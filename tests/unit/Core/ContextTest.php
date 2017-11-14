<?php
/**
 * Copyright 2017 OpenCensus Authors
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

namespace OpenCensus\Tests\Unit\Core;

use OpenCensus\Core\Context;

/**
 * @group core
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Context::reset();
    }

    public function tearDown()
    {
        Context::reset();
    }

    public function testBackgroundGeneratesEmptyContext()
    {
        $context = Context::background();
        $this->assertInstanceOf(Context::class, $context);
        $this->assertEquals([], $context->values());
    }

    public function testCurrentAlwaysReturnsAContext()
    {
        $context = Context::current();
        $this->assertInstanceOf(Context::class, $context);
    }

    public function testAttachingCurrentContext()
    {
        $context = new Context(['foo' => 'bar']);
        $prevContext = $context->attach();

        $current = Context::current();
        $this->assertInstanceOf(Context::class, $current);
        $this->assertEquals($context, $current);
    }

    public function testRestoringCurrentContext()
    {
        $context = new Context(['foo' => 'bar']);
        $prevContext = $context->attach();

        Context::current()->detach($prevContext);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testRestoringCurrentContextRequiresSameObject()
    {
        $context = new Context(['foo' => 'bar']);
        $prevContext = $context->attach();

        $other = new Context(['foo' => 'bar']);
        $this->assertFalse($prevContext === $other);

        $other->detach($other);
    }

    public function testContextValuesAreCopied()
    {
        $data = ['foo' => 'bar'];
        $context = new Context($data);
        $data['foo'] = 'asdf';

        $this->assertNotEquals($data, $context->values());
    }

    public function testWithValueInheritsPreviousContext()
    {
        $context = new Context(['foo' => 'bar']);
        $newContext = $context->withValue('asdf', 'qwer');
        $this->assertEquals(['foo' => 'bar', 'asdf' => 'qwer'], $newContext->values());
    }

    public function testChangingExistingValueDoesNotAffectOtherContexts()
    {
        $context = new Context(['foo' => 'bar']);
        $newContext = $context->withValue('foo', 'baz');
        $this->assertEquals(['foo' => 'baz'], $newContext->values());
        $this->assertEquals(['foo' => 'bar'], $context->values());
    }
}
