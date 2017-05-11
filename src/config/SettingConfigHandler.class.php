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
use Agavi\Util\Toolkit;

/**
 * SettingConfigHandler handles the settings.xml file
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
class SettingConfigHandler extends XmlConfigHandler
{
    const XML_NAMESPACE = 'http://agavi.org/agavi/config/parts/settings/1.1';
    
    /**
     * Execute this configuration handler.
     *
     * @param      XmlConfigDomDocument $document The document to parse.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>ConfigurationException</b> If a requested configuration
     *                                           file does not exist or is not
     *                                           readable.
     * @throws     <b>ParseException</b> If a requested configuration file is
     *                                   improperly formatted.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function execute(XmlConfigDomDocument $document)
    {
        // set up our default namespace
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'settings');
        
        // init our data array
        $data = array();
        
        $prefix = 'core.';
        
        foreach ($document->getConfigurationElements() as $cfg) {
            // let's do our fancy work
            if ($cfg->has('system_controllers')) {
                foreach ($cfg->get('system_controllers') as $controller) {
                    $name = $controller->getAttribute('name');
                    $data[sprintf('controllers.%s_module', $name)] = $controller->getChild('module')->getValue();
                    $data[sprintf('controllers.%s_controller', $name)] = $controller->getChild('controller')->getValue();
                }
            }
            
            // loop over <setting> elements; there can be many of them
            /** @var XmlConfigDomElement $setting */
            foreach ($cfg->get('settings') as $setting) {
                $localPrefix = $prefix;
                
                // let's see if this buddy has a <settings> parent with valuable information
                if ($setting->parentNode->localName == 'settings') {
                    if ($setting->parentNode->hasAttribute('prefix')) {
                        $localPrefix = $setting->parentNode->getAttribute('prefix');
                    }
                }
                
                $settingName = $localPrefix . $setting->getAttribute('name');
                if ($setting->hasAgaviParameters()) {
                    $data[$settingName] = $setting->getAgaviParameters();
                } else {
                    $data[$settingName] = $setting->getLiteralValue();
                }
            }
            
            if ($cfg->has('exception_templates')) {
                foreach ($cfg->get('exception_templates') as $exception_template) {
                    $tpl = Toolkit::expandDirectives($exception_template->getValue());
                    if (!is_readable($tpl)) {
                        throw new ConfigurationException('Exception template "' . $tpl . '" does not exist or is unreadable');
                    }
                    if ($exception_template->hasAttribute('context')) {
                        foreach (array_map('trim', explode(' ', $exception_template->getAttribute('context'))) as $ctx) {
                            $data['exception.templates.' . $ctx] = $tpl;
                        }
                    } else {
                        $data['exception.default_template'] = Toolkit::expandDirectives($tpl);
                    }
                }
            }
        }

        $code = 'Agavi\\Config\\Config::fromArray(' . var_export($data, true) . ');';

        return $this->generate($code, $document->documentURI);
    }
}
