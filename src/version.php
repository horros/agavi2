<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

use Agavi\Config\Config;
/**
 * Version initialization script.
 *
 * @package    agavi
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */

Config::set('agavi.name', 'Agavi');

Config::set('agavi.major_version', '2');
Config::set('agavi.minor_version', '0');
Config::set('agavi.micro_version', '0');
Config::set('agavi.status', 'dev');
Config::set('agavi.branch', 'master');

Config::set('agavi.version',
	Config::get('agavi.major_version') . '.' .
	Config::get('agavi.minor_version') . '.' .
	Config::get('agavi.micro_version') .
	(Config::has('agavi.status')
		? '-' . Config::get('agavi.status')
		: '')
);

Config::set('agavi.release',
	Config::get('agavi.name') . '/' .
	Config::get('agavi.version')
);

Config::set('agavi.url', 'http://www.agavi.org');

Config::set('agavi_info',
	Config::get('agavi.release') . ' (' .
	Config::get('agavi.url') . ')'
);

?>