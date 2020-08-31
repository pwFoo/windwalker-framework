<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Renderer\Test\Stub;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The StubTwigExtension class.
 *
 * @since  2.0
 */
class StubTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'stub';
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return [
            new TwigFilter('armor', [$this, 'armor']),
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('flower', [$this, 'flower']),
        ];
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {
        return [
            'olive' => 'peace',
        ];
    }

    /**
     * flower
     *
     * @return  string
     */
    public function flower()
    {
        return 'sakura';
    }

    /**
     * armor
     *
     * @return  string
     */
    public function armor()
    {
        return 'Iron Man';
    }
}
