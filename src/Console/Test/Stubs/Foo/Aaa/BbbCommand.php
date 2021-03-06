<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Console\Test\Stubs\Foo\Aaa;

use Windwalker\Console\Command\Command;

/**
 * Class BbbCommand
 *
 * @since  2.0
 */
class BbbCommand extends Command
{
    /**
     * Initialise command.
     *
     * @return void
     *
     * @since  2.0
     */
    public function init()
    {
        $this->setName('bbb');
    }

    /**
     * doExecute
     *
     * @return int
     *
     * @since  2.0
     */
    public function doExecute()
    {
        $this->out('Bbb Command', false);

        return 99;
    }
}
