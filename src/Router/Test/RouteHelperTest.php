<?php declare(strict_types=1);
/**
 * Part of Windwalker project Test files.  @codingStandardsIgnoreStart
 *
 * @copyright  Copyright (C) 2019 LYRASOFT Taiwan, Inc.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Router\Test;

use Windwalker\Router\RouteHelper;

/**
 * Test class of RouteHelper
 *
 * @since 2.0
 */
class RouteHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Method to test sanitize().
     *
     * @return void
     *
     * @covers \Windwalker\Router\RouteHelper::sanitize
     */
    public function testSanitize()
    {
        $this->assertEquals('/foo/bar/baz', RouteHelper::sanitize('/foo/bar/baz'));
        $this->assertEquals('/foo/bar/baz', RouteHelper::sanitize('http://flower.com/foo/bar/baz/?olive=peace'));
    }

    /**
     * Method to test normalise()
     *
     * @return  void
     *
     * @covers \Windwalker\Router\RouteHelper::normalise
     */
    public function testNormalise()
    {
        $this->assertEquals('/foo/bar/baz', RouteHelper::sanitize('foo/bar/baz/'));
    }

    /**
     * testGetVariables
     *
     * @return  void
     *
     * @covers \Windwalker\Router\RouteHelper::getVariables
     */
    public function testGetVariables()
    {
        $array = [
            0 => 5,
            'id' => 5,
            1 => 'foo',
            'bar' => 'foo',
        ];

        $this->assertEquals(['id' => 5, 'bar' => 'foo'], RouteHelper::getVariables($array));

        $vars = [
            'flower' => 'sakura',
        ];

        $this->assertEquals(
            ['flower' => 'sakura', 'id' => 5, 'bar' => 'foo'],
            RouteHelper::getVariables($array, $vars)
        );
    }
}
