<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2010 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

require_once(__DIR__ . '/AgaviTask.php');

/**
 * Transforms a view class base name (like <code>YourController/Success</code>) to
 * a usable base identifier (like <code>YourControllerSuccess</code>).
 *
 * @package    agavi
 * @subpackage build
 *
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
class TransformviewclassbaseTask extends AgaviTask
{
	protected $property = null;
	protected $string = null;
	
	/**
	 * Sets the property that this task will modify.
	 *
	 * @param      string The property to modify.
	 */
	public function setProperty($property)
	{
		$this->property = $property;
	}
	
	/**
	 * Sets the string to transform.
	 *
	 * @param      string The string to transform.
	 */
	public function setString($string)
	{
		$this->string = $string;
	}
	
	/**
	 * Executes the task.
	 */
	public function main()
	{
		if($this->property === null) {
			throw new \Agavi\Build\Exception\BuildException('The property attribute must be specified');
		}
		if($this->string === null) {
			throw new \Agavi\Build\Exception\BuildException('The string attribute must be specified');
		}
		
		$result = str_replace('/', '_', \Agavi\Util\Toolkit::canonicalName($this->string));
		$this->project->setUserProperty($this->property, $result);
	}
}

?>