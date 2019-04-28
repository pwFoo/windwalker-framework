<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

// phpcs:disable

//---------------------------------------------------------------
/**
 * UTF-8 aware alternative to strcasecmp
 * A case insensivite string comparison
 * Note: requires utf8_strtolower
 *
 * @param string
 * @param string
 *
 * @return int
 * @see     http://www.php.net/strcasecmp
 * @see     utf8_strtolower
 * @package utf8
 */
function utf8_strcasecmp($strX, $strY)
{
    $strX = utf8_strtolower($strX);
    $strY = utf8_strtolower($strY);

    return strcmp($strX, $strY);
}

