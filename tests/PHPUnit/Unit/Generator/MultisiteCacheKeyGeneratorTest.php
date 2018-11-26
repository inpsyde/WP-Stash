<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit\Generator;

use Inpsyde\WpStash\Generator\MultisiteCacheKeyGenerator;
use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;

class MultisiteCacheKeyGeneratorTest extends AbstractUnitTestcase
{

    public function testBasic()
    {
        $expectedBlogId = 1;
        $expectedKey = 'foo';
        $expectedGroup = 'bar';

        $testee = new MultisiteCacheKeyGenerator($expectedBlogId);
        $result = $testee->create($expectedKey, $expectedGroup);

        static::assertContains($expectedKey, $result);
        static::assertContains($expectedGroup, $result);
        static::assertContains((string)$expectedBlogId, $result);
    }

    public function testAddGlobalGroup()
    {
        $expectedBlogId = 1;
        $expectedGroup = 'foo';

        $testee = new MultisiteCacheKeyGenerator($expectedBlogId);
        $result = $testee->addGlobalGroups($expectedGroup);

        static::assertTrue($result[$expectedGroup]);
    }

    public function testSwitchToBlog()
    {
        $expectedBlogId = 1;

        $testee = new MultisiteCacheKeyGenerator(0);

        static::assertTrue($testee->switchToBlog($expectedBlogId));
    }
}
