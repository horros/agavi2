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

class AgaviVersionTask extends Task
{
	public function main()
	{
		$agaviPath = realpath(getcwd() . '/src/agavi.php');
		
		if(!$agaviPath && !file_exists($agaviPath)) {
			throw new BuildException('Agavi not found.');
		}

		require_once($agaviPath);
		
		$this->project->setUserProperty('agavi.version', Config::get('agavi.version'));
		$this->project->setUserProperty('agavi.pear.version', sprintf("%d.%d.%d%s", 
			Config::get('agavi.major_version'),
			Config::get('agavi.minor_version'),
			Config::get('agavi.micro_version'),
			Config::has('agavi.status') ? Config::get('agavi.status') : ''
		));
		
		$status = Config::get('agavi.status');
		
		if($status == 'dev') {
			$status = 'devel';
		} elseif(strpos($status, 'alpha') !== false) {
			$status = 'alpha';
		} elseif(strpos($status, 'beta') !== false) {
			$status = 'beta';
		} elseif(strpos($status, 'RC') !== false) {
			$status = 'beta';
		} else {
			$status = 'stable';
		}
		
		$this->project->setUserProperty('agavi.status', $status);
	}
}

?>