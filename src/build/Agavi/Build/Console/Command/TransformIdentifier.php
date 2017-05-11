<?php
// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2016 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * Represents a transformation for sanitizing a string to a valid PHP
 * identifier.
 *
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 */
namespace Agavi\Build\Console\Command;

class TransformIdentifier
{

    public static function transform($input)
    {
        $identifier = str_replace(' ', '', preg_replace('#[^A-Za-z0-9\x7F-\xFF_ ]#', '_', $input));
        if (ctype_digit($identifier[0])) {
            $identifier = '_' . $identifier;
        }
        return $identifier;
    }
}
