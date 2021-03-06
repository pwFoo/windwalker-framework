<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Middleware;

/**
 * End Middleware, this object will not do anything.
 *
 * @since 2.0
 */
class EndMiddleware extends AbstractMiddleware
{
    /**
     * Set next middleware.
     *
     * @param   object $callable The middleware object.
     *
     * @return  EndMiddleware  Return self to support chaining.
     */
    public function setNext($callable)
    {
        return $this;
    }

    /**
     * Get next middleware.
     *
     * @return  mixed
     */
    public function getNext()
    {
        return null;
    }

    /**
     * Call next middleware.
     *
     * @param  array $data
     *
     * @return mixed
     */
    public function execute($data = null)
    {
    }
}
