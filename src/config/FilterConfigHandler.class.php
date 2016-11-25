<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

namespace Agavi\Config;

use Agavi\Config\Util\Dom\XmlConfigDomDocument;
use Agavi\Config\Util\Dom\XmlConfigDomElement;
use Agavi\Exception\ConfigurationException;
use Agavi\Exception\FactoryException;
use Agavi\Util\Toolkit;

/**
 * FilterConfigHandler allows you to register filters with the system.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
class FilterConfigHandler extends XmlConfigHandler
{
	const XML_NAMESPACE = 'http://agavi.org/agavi/config/parts/filters/1.1';
	
	/**
	 * Execute this configuration handler.
	 *
	 * @param      XmlConfigDomDocument $document The document to parse.
	 *
	 * @return     string Data to be written to a cache file.
	 *
	 * @throws     <b>AgaviParseException</b> If a requested configuration file is
	 *                                        improperly formatted.
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function execute(XmlConfigDomDocument $document)
	{
		// set up our default namespace
		$document->setDefaultNamespace(self::XML_NAMESPACE, 'filters');
		
		$config = $document->documentURI;
		
		$filters = array();
		
		foreach($document->getConfigurationElements() as $cfg) {
			if($cfg->has('filters')) {
				/** @var XmlConfigDomElement $filter */
				foreach($cfg->get('filters') as $filter) {
					$name = $filter->getAttribute('name', Toolkit::uniqid());
					
					if(!isset($filters[$name])) {
						$filters[$name] = array('params' => array(), 'enabled' => Toolkit::literalize($filter->getAttribute('enabled', true)));
					} else {
						$filters[$name]['enabled'] = Toolkit::literalize($filter->getAttribute('enabled', $filters[$name]['enabled']));
					}
					
					if($filter->hasAttribute('class')) {
						$filters[$name]['class'] = $filter->getAttribute('class');
					}
					
					$filters[$name]['params'] = $filter->getAgaviParameters($filters[$name]['params']);
				}
			}
		}
		
		$data = array();

		foreach($filters as $name => $filter) {
			if(stripos($name, 'agavi') === 0) {
				throw new ConfigurationException('Filter names must not start with "agavi".');
			}
			if(!isset($filter['class'])) {
				throw new ConfigurationException('No class name specified for filter "' . $name . '" in ' . $config);
			}
			if($filter['enabled']) {
				$rc = new \ReflectionClass($filter['class']);
				$if = 'Agavi\\Filter\\' . ucfirst(strtolower(substr(basename($config), 0, strpos(basename($config), '_filters')))) . 'FilterInterface';
				if(!$rc->implementsInterface($if)) {
					throw new FactoryException('Filter "' . $name . '" does not implement interface "' . $if . '"');
				}
				$data[] = '$filter = new ' . $filter['class'] . '();';
				$data[] = '$filter->initialize($this->context, ' . var_export($filter['params'], true) . ');';
				$data[] = '$filters[' . var_export($name, true) . '] = $filter;';
			}
		}

		return $this->generate($data, $config);
	}
}

?>