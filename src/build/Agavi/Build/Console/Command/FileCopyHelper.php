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
 * File copy helper class.
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/

namespace Agavi\Build\Console\Command;


class FileCopyHelper
{

	/**
	 * Copy a file and apply callback
	 *
	 * Reads in a file, applies the provided callback function
	 * with the provided parameters, and writes the file to
	 * the destination.
	 *
	 * @param $from string the file to copy
	 * @param $to string the destination (including the file name)
	 * @param callable $callback the callback to apply to the file contents before writing
	 * @param array $params parameters passed to the callback function
	 *
	 * @return int The number of bytes that were written to the file, or false on failure
	 */
	public function copy($from, $to, callable $callback, array $params = array()) {

		$data = file_get_contents($from);
		$data = $callback($data, $params);
		$ret = file_put_contents($to, $data);
		return $ret;
	}
}