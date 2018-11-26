<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit\Generator;

use Inpsyde\WpStash\Generator\CacheKeyGenerator;
use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;

class CacheKeyGeneratorTest extends AbstractUnitTestcase
{

    public function testBasic()
    {
        $testee = new CacheKeyGenerator();

        $expectedKey = 'foo';

        static::assertContains($expectedKey, $testee->create($expectedKey));
    }
}
