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
 * Base command that all Agavi build commands must extend from
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/
namespace Agavi\Build\Console\Command;

use Agavi\Build\Console\Command\Settings;
use Symfony\Component\Console\Command\Command;

abstract class AgaviCommand extends Command
{

    /**
     * The settings
     *
     * @var Settings
     */
    private $settings = null;

    /**
     * Retrieve the settings object
     *
     * @return Settings
     */
    public function &getSettings(): Settings
    {
        if (!is_object($this->settings))
            $this->settings = new Settings();
        return $this->settings;
    }


    private $src_dir;

    /**
     * Get the source dir
     *
     * @return mixed
     */
    public function getSourceDir()
    {
        return $this->src_dir;
    }

    /**
     * @param mixed $src_dir
     */
    public function setSourceDir($src_dir)
    {
        $this->src_dir = $src_dir;
    }



}