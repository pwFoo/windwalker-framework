<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Utilities\Test\Stub;

use Windwalker\Utilities\Classes\OptionAccessTrait;
use Windwalker\Utilities\Test\Stub\Attrs\StubWrapper;

/**
 * The StubObject class.
 */
@@StubWrapper
class StubObject
{
    use OptionAccessTrait;

    /**
     * @var StubAccessible|null
     */
    public ?StubAccessible $stub;

    /**
     * StubObject constructor.
     *
     * @param  StubAccessible|null  $stub
     * @param  array                $options
     */
    public function __construct(?StubAccessible $stub = null, array $options = [])
    {
        $this->stub = $stub;
        $this->prepareOptions([], $options);
    }
}
