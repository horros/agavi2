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

use Agavi\Config\Util\Dom\XmlConfigDomElement;
use Agavi\Exception\AgaviException;
use Agavi\Util\Toolkit;
use Agavi\Config\Util\Dom\XmlConfigDomDocument;

/**
 * ConfigHandlersConfigHandler allows you to specify configuration handlers
 * for the application or on a module level.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @author     David Zülke <david.zuelke@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class ConfigHandlersConfigHandler extends XmlConfigHandler
{
    const XML_NAMESPACE = 'http://agavi.org/agavi/config/parts/config_handlers/1.1';
    
    /**
     * Execute this configuration handler.
     *
     * @param      XmlConfigDomDocument $document The document to handle.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>UnreadableException</b> If a requested configuration
     *                                        file does not exist or is not
     *                                        readable.
     * @throws     <b>ParseException</b> If a requested configuration file is
     *                                   improperly formatted.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      0.11.0
     */
    public function execute(XmlConfigDomDocument $document)
    {
        // set up our default namespace
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'config_handlers');
        
        // init our data arrays
        $handlers = array();
        
        foreach ($document->getConfigurationElements() as $configuration) {
            if (!$configuration->has('handlers')) {
                continue;
            }
            
            // let's do our fancy work
            /** @var XmlConfigDomElement $handler */
            foreach ($configuration->get('handlers') as $handler) {
                $pattern = $handler->getAttribute('pattern');
                
                $category = Toolkit::normalizePath(Toolkit::expandDirectives($pattern));
                
                $class = $handler->getAttribute('class');
                
                $transformations = array(
                    XmlConfigParser::STAGE_SINGLE => array(),
                    XmlConfigParser::STAGE_COMPILATION => array(),
                );
                if ($handler->has('transformations')) {
                    /** @var XmlConfigDomElement $transformation */
                    foreach ($handler->get('transformations') as $transformation) {
                        $path = Toolkit::literalize($transformation->getValue());
                        $for = $transformation->getAttribute('for', XmlConfigParser::STAGE_SINGLE);
                        $transformations[$for][] = $path;
                    }
                }
                
                $validations = array(
                    XmlConfigParser::STAGE_SINGLE => array(
                        XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE => array(
                            XmlConfigParser::VALIDATION_TYPE_RELAXNG => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_SCHEMATRON => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_XMLSCHEMA => array(
                            ),
                        ),
                        XmlConfigParser::STEP_TRANSFORMATIONS_AFTER => array(
                            XmlConfigParser::VALIDATION_TYPE_RELAXNG => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_SCHEMATRON => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_XMLSCHEMA => array(
                            ),
                        ),
                    ),
                    XmlConfigParser::STAGE_COMPILATION => array(
                        XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE => array(
                            XmlConfigParser::VALIDATION_TYPE_RELAXNG => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_SCHEMATRON => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_XMLSCHEMA => array(
                            ),
                        ),
                        XmlConfigParser::STEP_TRANSFORMATIONS_AFTER => array(
                            XmlConfigParser::VALIDATION_TYPE_RELAXNG => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_SCHEMATRON => array(
                            ),
                            XmlConfigParser::VALIDATION_TYPE_XMLSCHEMA => array(
                            ),
                        ),
                    ),
                );
                if ($handler->has('validations')) {
                    /** @var XmlConfigDomElement $validation */
                    foreach ($handler->get('validations') as $validation) {
                        $path = Toolkit::literalize($validation->getValue());
                        $type = null;
                        if (!$validation->hasAttribute('type')) {
                            $type = $this->guessValidationType($path);
                        } else {
                            $type = $validation->getAttribute('type');
                        }
                        $for = $validation->getAttribute('for', XmlConfigParser::STAGE_SINGLE);
                        $step = $validation->getAttribute('step', XmlConfigParser::STEP_TRANSFORMATIONS_AFTER);
                        $validations[$for][$step][$type][] = $path;
                    }
                }
                
                $handlers[$category] = isset($handlers[$category])
                    ? $handlers[$category]
                    : array(
                        'parameters' => array(),
                        );
                $handlers[$category] = array(
                    'class' => $class,
                    'parameters' => $handler->getAgaviParameters($handlers[$category]['parameters']),
                    'transformations' => $transformations,
                    'validations' => $validations,
                );
            }
        }
        
        $data = array(
            'return ' . var_export($handlers, true),
        );
        
        return $this->generate($data, $document->documentURI);
    }
    
    /**
     * Convenience method to quickly guess the type of a validation file using its
     * file extension.
     *
     * @param      string $path The path to the file.
     *
     * @return     string An XmlConfigParser::VALIDATION_TYPE_* const value.
     *
     * @throws     AgaviException If the type could not be determined.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.0
     */
    protected function guessValidationType($path)
    {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'rng':
                return XmlConfigParser::VALIDATION_TYPE_RELAXNG;
            case 'rnc':
                return XmlConfigParser::VALIDATION_TYPE_RELAXNG;
            case 'sch':
                return XmlConfigParser::VALIDATION_TYPE_SCHEMATRON;
            case 'xsd':
                return XmlConfigParser::VALIDATION_TYPE_XMLSCHEMA;
            default:
                throw new AgaviException(sprintf('Could not determine validation type for file "%s"', $path));
        }
    }
}
