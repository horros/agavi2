<?php
namespace Agavi\Config;

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


use Agavi\Util\SchematronProcessor;
use Agavi\Util\Toolkit;
use Agavi\Exception\ParseException;
use Agavi\Exception\UnreadableException;
use Agavi\Config\Util\Dom\XmlConfigDomDocument;
use Agavi\Config\Util\Dom\XmlConfigDomElement;
use Agavi\Util\XsltProcessor;

/**
 * XmlConfigParser handles both Agavi and foreign XML configuration files,
 * deals with XIncludes, XSL transformations and validation as well as filtering
 * and ordering of configuration blocks and parent file resolution and parsing.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */

class XmlConfigParser
{
    const NAMESPACE_AGAVI_ENVELOPE_0_11 = 'http://agavi.org/agavi/1.0/config';
    
    const NAMESPACE_AGAVI_ENVELOPE_1_0 = 'http://agavi.org/agavi/config/global/envelope/1.0';
    
    const NAMESPACE_AGAVI_ENVELOPE_1_1 = 'http://agavi.org/agavi/config/global/envelope/1.1';
    
    const NAMESPACE_AGAVI_ENVELOPE_LATEST = self::NAMESPACE_AGAVI_ENVELOPE_1_1;
    
    const NAMESPACE_AGAVI_ANNOTATIONS_1_0 = 'http://agavi.org/agavi/config/global/annotations/1.0';
    
    const NAMESPACE_AGAVI_ANNOTATIONS_LATEST = self::NAMESPACE_AGAVI_ANNOTATIONS_1_0;
    
    const VALIDATION_TYPE_XMLSCHEMA = 'xml_schema';
    
    const VALIDATION_TYPE_RELAXNG = 'relax_ng';
    
    const VALIDATION_TYPE_SCHEMATRON = 'schematron';
    
    const NAMESPACE_SCHEMATRON_ISO = 'http://purl.oclc.org/dsdl/schematron';
    
    const NAMESPACE_SVRL_ISO = 'http://purl.oclc.org/dsdl/svrl';
    
    const NAMESPACE_XML_1998 = 'http://www.w3.org/XML/1998/namespace';
    
    const NAMESPACE_XMLNS_2000 = 'http://www.w3.org/2000/xmlns/';
    
    const NAMESPACE_XSL_1999 = 'http://www.w3.org/1999/XSL/Transform';
    
    const NAMESPACE_XINCLUDE_2001 = 'http://www.w3.org/2001/XInclude';
    
    const STAGE_SINGLE = 'single';
    
    const STAGE_COMPILATION = 'compilation';
    
    const STEP_TRANSFORMATIONS_BEFORE = 'transformations_before';
    
    const STEP_TRANSFORMATIONS_AFTER = 'transformations_after';
    
    /**
     * @var        array A list of XML namespaces for Agavi configuration files as
     *                   keys and their associated XPath namespace prefix (value).
     */
    public static $agaviEnvelopeNamespaces = array(
        self::NAMESPACE_AGAVI_ENVELOPE_0_11 => 'agavi_envelope_0_11',
        self::NAMESPACE_AGAVI_ENVELOPE_1_0 => 'agavi_envelope_1_0',
        self::NAMESPACE_AGAVI_ENVELOPE_1_1 => 'agavi_envelope_1_1',
    );
    
    /**
     * @var        array A list of all XML namespaces that are used internally by
     *                   the configuration parser.
     */
    public static $agaviNamespaces = array(
        self::NAMESPACE_AGAVI_ENVELOPE_0_11 => 'agavi_envelope_0_11',
        self::NAMESPACE_AGAVI_ENVELOPE_1_0 => 'agavi_envelope_1_0',
        self::NAMESPACE_AGAVI_ENVELOPE_1_1 => 'agavi_envelope_1_1',
        self::NAMESPACE_AGAVI_ANNOTATIONS_1_0 => 'agavi_annotations_1_0',
    );
    
    /**
     * @var        string Path to the config file we're parsing in this instance.
     */
    protected $path = '';
    
    /**
     * @var        string The name of the current environment.
     */
    protected $environment = '';
    
    /**
     * @var        string The name of the current context.
     */
    protected $context = null;
    
    /**
     * @var        \DOMDocument|XmlConfigDomDocument The document we're parsing here.
     */
    protected $doc = null;
    
    /**
     * Test if the given document looks like an Agavi config file.
     *
     * @param      \DOMDocument $doc The document to test.
     *
     * @return     bool True, if it is an Agavi config document, false otherwise.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.0
     */
    public static function isAgaviConfigurationDocument(\DOMDocument $doc)
    {
        return $doc->documentElement && $doc->documentElement->localName == 'configurations' && self::isAgaviEnvelopeNamespace($doc->documentElement->namespaceURI);
    }
    
    /**
     * Check if the given namespace URI is a valid Agavi envelope namespace.
     *
     * @param      string $namespaceUri The namespace URI.
     *
     * @return     bool True, if the given URI is a valid namespace URI, or false.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    public static function isAgaviEnvelopeNamespace($namespaceUri)
    {
        return isset(self::$agaviEnvelopeNamespaces[$namespaceUri]);
    }
    
    /**
     * Check if a given namespace URI is a valid Agavi namespace.
     *
     * @param      string $namespaceUri The namespace URI.
     *
     * @return     bool True if the given URI is a valid namespace URI,
     *                  false otherwise.
     *
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function isAgaviNamespace($namespaceUri)
    {
        return isset(self::$agaviNamespaces[$namespaceUri]);
    }
    
    /**
     * Retrieves an XPath namespace prefix based on a given namespace URI.
     *
     * @param      string $namespaceUri The namespace URI.
     *
     * @return     string The prefix for the namespace URI, or null if none
     *                    exists.
     *
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function getAgaviNamespacePrefix($namespaceUri)
    {
        if (self::isAgaviNamespace($namespaceUri)) {
            return self::$agaviNamespaces[$namespaceUri];
        }
        return null;
    }
    
    /**
     * Register Agavi namespace prefixes in a given document.
     *
     * @param      XmlConfigDomDocument $document The document.
     *
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function registerAgaviNamespaces(XmlConfigDomDocument $document)
    {
        $xpath = $document->getXpath();
        
        foreach (self::$agaviNamespaces as $namespaceUri => $prefix) {
            $xpath->registerNamespace($prefix, $namespaceUri);
        }
        
        /* Register the latest namespaces. */
        $xpath->registerNamespace('agavi_envelope_latest', self::NAMESPACE_AGAVI_ENVELOPE_LATEST);
        $xpath->registerNamespace('agavi_annotations_latest', self::NAMESPACE_AGAVI_ANNOTATIONS_LATEST);
    }
                                                     
    /**
     * @param      string $path               An absolute filesystem path to a configuration file.
     * @param      string $environment        The environment name.
     * @param      string $context            The optional context name.
     * @param      array  $transformationInfo An associative array of transformation information.
     * @param      array  $validationInfo     An associative array of validation information.
     *
     * @return     XmlConfigDomDocument A properly merged DOMDocument.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function run($path, $environment, $context = null, array $transformationInfo = array(), array $validationInfo = array())
    {
        $isAgaviConfigFormat = true;
        // build an array of documents (this one, and the parents)
        $docs = array();
        $previousPaths = array();
        $nextPath = $path;
        while ($nextPath !== null) {
            // run the single stage parser
            $parser = new XmlConfigParser($nextPath, $environment, $context);
            $doc = $parser->execute($transformationInfo[self::STAGE_SINGLE], $validationInfo[self::STAGE_SINGLE]);
            
            // put the new document in the list
            $docs[] = $doc;
            
            // make sure it (still) is a <configurations> file with the proper Agavi namespace
            if ($isAgaviConfigFormat) {
                $isAgaviConfigFormat = self::isAgaviConfigurationDocument($doc);
            }
            
            // is it an Agavi <configurations> element? does it have a parent attribute? yes? good. parse that next
            // TODO: support future namespaces
            if ($isAgaviConfigFormat && $doc->documentElement->hasAttribute('parent')) {
                $theNextPath = Toolkit::literalize($doc->documentElement->getAttribute('parent'));
                
                // no infinite loop plz, kthx
                if ($nextPath === $theNextPath) {
                    throw new ParseException(sprintf("Agavi detected an infinite loop while processing parent configuration files of \n%s\n\nFile\n%s\nincludes itself as a parent.", $path, $theNextPath));
                } elseif (isset($previousPaths[$theNextPath])) {
                    throw new ParseException(sprintf("Agavi detected an infinite loop while processing parent configuration files of \n%s\n\nFile\n%s\nhas previously been included by\n%s", $path, $theNextPath, $previousPaths[$theNextPath]));
                } else {
                    $previousPaths[$theNextPath] = $nextPath;
                    $nextPath = $theNextPath;
                }
            } else {
                $nextPath = null;
            }
        }
        
        // TODO: use our own classes here that extend DOM*
        $retval = new XmlConfigDomDocument();
        foreach (self::$agaviEnvelopeNamespaces as $envelopeNamespaceUri => $envelopeNamespacePrefix) {
            $retval->getXpath()->registerNamespace($envelopeNamespacePrefix, $envelopeNamespaceUri);
        }
        
        if ($isAgaviConfigFormat) {
            // if it is an Agavi config, we'll create a new document with all files' <configuration> blocks inside
            $retval->appendChild(new XmlConfigDomElement('configurations', null, self::NAMESPACE_AGAVI_ENVELOPE_LATEST));
            
            // reverse the array - we want the parents first!
            $docs = array_reverse($docs);
            
            $configurationElements = array();
            
            // TODO: I bet this leaks memory due to the nodes being taken out of the docs. beware circular refs!
            /** @var XmlConfigDomDocument $doc */
            foreach ($docs as $doc) {
                // iterate over all nodes (attributes, <sandbox>, <configuration> etc) inside the document element and append them to the <configurations> element in our final document
                foreach ($doc->documentElement->childNodes as $node) {
                    if ($node->nodeType == XML_ELEMENT_NODE && $node->localName == 'configuration' && self::isAgaviEnvelopeNamespace($node->namespaceURI)) {
                        // it's a <configuration> element - put that on a stack for processing
                        $configurationElements[] = $node;
                    } else {
                        // import the node, recursively, and store the imported node
                        $importedNode = $retval->importNode($node, true);
                        // now append it to the <configurations> element
                        $retval->documentElement->appendChild($importedNode);
                    }
                }
                // if it's a <configurations> element, then we need to copy the attributes from there
                if ($doc->isAgaviConfiguration()) {
                    $namespaces = $doc->getXPath()->query('namespace::*');
                    foreach ($namespaces as $namespace) {
                        if ($namespace->localName !== 'xml' && $namespace->localName != 'xmlns') {
                            $retval->documentElement->setAttributeNS(self::NAMESPACE_XMLNS_2000, 'xmlns:' . $namespace->localName, $namespace->namespaceURI);
                        }
                    }
                    foreach ($doc->documentElement->attributes as $attribute) {
                        // but not the "parent" attributes...
                        if ($attribute->namespaceURI === null && $attribute->localName === 'parent') {
                            continue;
                        }
                        $importedAttribute = $retval->importNode($attribute, true);
                        $retval->documentElement->setAttributeNode($importedAttribute);
                    }
                }
            }
            
            // generic <configuration> first, then those with an environment attribute, then those with context, then those with both
            $configurationOrder = array(
                'count(self::node()[@agavi_annotations_latest:matched and not(@environment) and not(@context)])',
                'count(self::node()[@agavi_annotations_latest:matched and @environment and not(@context)])',
                'count(self::node()[@agavi_annotations_latest:matched and not(@environment) and @context])',
                'count(self::node()[@agavi_annotations_latest:matched and @environment and @context])',
            );
            
            // now we sort the nodes according to the rules
            foreach ($configurationOrder as $xpath) {
                // append all matching nodes from the order array...
                foreach ($configurationElements as &$element) {
                    // ... if the xpath matches, that is!
                    if ($element->ownerDocument->getXpath()->evaluate($xpath, $element)) {
                        // it did, so import the node and append it to the result doc
                        $importedNode = $retval->importNode($element, true);
                        $retval->documentElement->appendChild($importedNode);
                    }
                }
            }
            
            // run the compilation stage parser
            $retval = self::executeCompilation($retval, $environment, $context, $transformationInfo[self::STAGE_COMPILATION], $validationInfo[self::STAGE_COMPILATION]);
        } else {
            // it's not an agavi config file. just pass it through then
            $retval->appendChild($retval->importNode($doc->documentElement, true));
        }
        
        // cleanup attempt
        unset($docs);
        
        // set the pseudo-document URI
        $retval->documentURI = $path;
        
        return $retval;
    }
    
    /**
     * Builds a proper regular expression from the input pattern to test against
     * the given subject. This is for "environment" and "context" attributes of
     * configuration blocks in the files.
     *
     * @param      string $pattern A regular expression chunk without delimiters/anchors.
     * @param      string $subject The subject to test against
     *
     * @return     bool Whether or not the subject matched the pattern.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    public static function testPattern($pattern, $subject)
    {
        // four backslashes mean one literal backslash
        $pattern = preg_replace('/\\\\+#/', '\\#', $pattern);
        return (preg_match('#^(' . implode('|', array_map('trim', explode(' ', $pattern))) . ')$#', $subject) > 0);
    }
    
    /**
     * The constructor.
     * Will make a DOMDocument instance using the given path.
     *
     * @param      string $path The path to the configuration file.
     * @param      string $environment The optional name of the current environment.
     * @param      string $context The optional name of the current context.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public function __construct($path, $environment = null, $context = null)
    {
        // store environment...
        if ($environment === null) {
            $environment = Config::get('core.environment');
        }
        $this->environment = $environment;
        // ... and context names
        $this->context = $context;
        
        if (!is_readable($path)) {
            $error = 'Configuration file "' . $path . '" does not exist or is unreadable';
            throw new UnreadableException($error);
        }
        
        // store path to the config file
        $this->path = $path;
        
        // AgaviXmlConfigDomDocument has convenience methods!
        try {
            $this->doc = new XmlConfigDomDocument();
            $this->doc->substituteEntities = true;
            $this->doc->load($path);
        } catch (\DOMException $dome) {
            throw new ParseException(sprintf('Configuration file "%s" could not be parsed: %s', $path, $dome->getMessage()), 0, $dome);
        }
    }
    
    /**
     * Destructor to do the cleaning up.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    public function __destruct()
    {
        unset($this->doc);
    }
    
    /**
     * @param      array $transformationInfo An array of XSL paths for transformation.
     * @param      array $validationInfo An associative array of validation information.
     *
     * @return     \DOMDocument Our DOMDocument.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public function execute(array $transformationInfo = array(), array $validationInfo = array())
    {
        // resolve xincludes
        self::xinclude($this->doc);
        
        // validate XMLSchema-instance declarations
        self::validateXsi($this->doc);
        
        // validate pre-transformation
        self::validate($this->doc, $this->environment, $this->context, $validationInfo[XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE]);
        
        // mark document for merging
        self::match($this->doc, $this->environment, $this->context);
        
        if (!Config::get('core.skip_config_transformations', false)) {
            // run inline transformations
            $this->doc = self::transformProcessingInstructions($this->doc, $this->environment, $this->context);
            
            // perform XSL transformations
            $this->doc = self::transform($this->doc, $this->environment, $this->context, $transformationInfo);
            
            // resolve xincludes again, since transformations may have introduced some
            self::xinclude($this->doc);
        }
        
        // validate post-transformation
        self::validate($this->doc, $this->environment, $this->context, $validationInfo[XmlConfigParser::STEP_TRANSFORMATIONS_AFTER]);
        
        // clean up the document
        self::cleanup($this->doc);
        
        return $this->doc;
    }
    
    /**
     * Executes the parser for a compilation document.
     *
     * @param      XmlConfigDomDocument $document           The document to act upon.
     * @param      string               $environment        The environment name.
     * @param      string               $context            The context name.
     * @param      array                $transformationInfo An array of XSL paths for transformation.
     * @param      array                $validationInfo     An associative array of validation information.
     *
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function executeCompilation(XmlConfigDomDocument $document, $environment, $context, array $transformationInfo = array(), array $validationInfo = array())
    {
        // resolve xincludes
        self::xinclude($document);
        
        // validate pre-transformation
        self::validate($document, $environment, $context, $validationInfo[XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE]);
        
        if (!Config::get('core.skip_config_transformations', false)) {
            // perform XSL transformations
            $document = self::transform($document, $environment, $context, $transformationInfo);
            
            // resolve xincludes again, since transformations may have introduced some
            self::xinclude($document);
        }
        
        // validate post-transformation
        self::validate($document, $environment, $context, $validationInfo[XmlConfigParser::STEP_TRANSFORMATIONS_AFTER]);
        
        return $document;
    }
    
    /**
     * Resolve xinclude directives on a given document.
     *
     * @param      XmlConfigDomDocument $document The document to act upon.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function xinclude(XmlConfigDomDocument $document)
    {
        // expand directives, resolve globs and encode paths in XInclude href attributes
        $elements = $document->getElementsByTagNameNS(self::NAMESPACE_XINCLUDE_2001, 'include');
        $length = $elements->length;
        // we can't foreach() over the DOMNodeList as we're modifying it further below
        // see http://php.net/manual/en/class.domnodelist.php#83178
        for ($i = 0; $i < $length; $i++) {
            $element = $elements->item($i);
            if ($element->hasAttribute('href')) {
                $attribute = $element->getAttributeNode('href');
                $parts = explode('#', $attribute->nodeValue, 2);
                $parts[0] = str_replace('\\', '/', Toolkit::expandDirectives($parts[0]));
                $attribute->nodeValue = rawurlencode($parts[0]) . (isset($parts[1]) ? '#' . $parts[1] : '');
                if (strpos($parts[0], '*') !== false || strpos($parts[0], '{') !== false) {
                    $glob = glob($parts[0], GLOB_BRACE);
                    if ($glob) {
                        $glob = array_unique($glob); // it could be that someone used /path/to/{Foo,*}/burp.xml so Foo would come before all others, that's why we need to remove duplicates as the * would match Foo again
                        foreach ($glob as $path) {
                            $new = $element->cloneNode(true);
                            $new->setAttribute('href', rawurlencode($path) . (isset($parts[1]) ? '#' . $parts[1] : ''));
                            $element->parentNode->insertBefore($new, $element);
                            ++$i;
                        }
                        $element->parentNode->removeChild($element);
                    }
                }
            }
        }
        
        // perform xincludes
        try {
            $document->xinclude();
        } catch (\DOMException $dome) {
            throw new ParseException(sprintf('Configuration file "%s" could not be parsed: %s', $document->documentURI, $dome->getMessage()), 0, $dome);
        }
        
        // remove all xml:base attributes inserted by XIncludes
        $nodes = $document->getXpath()->query('//@xml:base', $document);
        foreach ($nodes as $node) {
            $node->ownerElement->removeAttributeNode($node);
        }
    }
    
    /**
     * Annotate the document with matched attributes against each configuration
     * element that matches the given context and environment.
     *
     * @param      XmlConfigDomDocument $document    The document to act upon.
     * @param      string               $environment The environment name.
     * @param      string               $context     The context name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function match(XmlConfigDomDocument $document, $environment, $context)
    {
        if ($document->isAgaviConfiguration()) {
            // it's an agavi config, so we need to set "matched" flags on all <configuration> elements where "context" and "environment" attributes match the values below
            $testAttributes = array(
                'context' => $context,
                'environment' => $environment,
            );
            
            foreach ($document->getConfigurationElements() as $configuration) {
                // assume that the element counts as matched, in case it doesn't have "context" or "environment" attributes
                $matched = true;
                foreach ($testAttributes as $attributeName => $attributeValue) {
                    if ($configuration->hasAttribute($attributeName)) {
                        $matched = $matched && self::testPattern($configuration->getAttribute($attributeName), $attributeValue);
                    }
                }
                if ($matched) {
                    // if all was fine, we set the attribute. the element will then be kept in the merged result doc later
                    $configuration->setAttributeNS(self::NAMESPACE_AGAVI_ANNOTATIONS_LATEST, 'agavi_annotations_latest:matched', 'true');
                }
            }
        }
    }
    
    /**
     * Transform the document using info from embedded processing instructions
     * and given stylesheets.
     *
     * @param      XmlConfigDomDocument $document The document to act upon.
     * @param      string $environment The environment name.
     * @param      string $context The context name.
     * @param      array  $transformationInfo An array of transformation information.
     * @param      array  $transformations An array of XSL stylesheets in DOMDocument instances.
     *
     * @return     XmlConfigDomDocument The transformed document.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function transform(XmlConfigDomDocument $document, $environment, $context, array $transformationInfo = array(), $transformations = array())
    {
        // loop over all the paths we found and load the files
        foreach ($transformationInfo as $href) {
            try {
                $xsl = new XmlConfigDomDocument();
                $xsl->load($href);
            } catch (\DOMException $dome) {
                throw new ParseException(sprintf('Configuration file "%s" could not be parsed: Could not load XSL stylesheet "%s": %s', $document->documentURI, $href, $dome->getMessage()), 0, $dome);
            }
            
            // add them to the list of transformations to be done
            $transformations[] = $xsl;
        }
        
        // now let's perform the transformations
        foreach ($transformations as $xsl) {
            // load the stylesheet document into an XSLTProcessor instance
            try {
                $proc = new XsltProcessor();
                $proc->registerPHPFunctions();
                $proc->importStylesheet($xsl);
            } catch (\Exception $e) {
                throw new ParseException(sprintf('Configuration file "%s" could not be parsed: Could not import XSL stylesheet "%s": %s', $document->documentURI, $xsl->documentURI, $e->getMessage()), 0, $e);
            }
            
            // set some info (config file path, context name, environment name) as params
            // first arg is the namespace URI, which PHP doesn't support. awesome. see http://bugs.php.net/bug.php?id=30622 for the sad details
            // we could use "agavi:context" etc, that does work even without such a prefix being declared in the stylesheet, but that would be completely non-XML-ish, confusing, and against the spec. so we use dots instead.
            // the string casts are required for hhvm ($context could be null for example and hhvm bails out on that)
            $proc->setParameter('', array(
                'agavi.config_path' => (string)$document->documentURI,
                'agavi.environment' => (string)$environment,
                'agavi.context' => (string)$context,
            ));
            
            try {
                // transform the doc
                $newdoc = $proc->transformToDoc($document);
            } catch (\Exception $e) {
                throw new ParseException(sprintf('Configuration file "%s" could not be parsed: Could not transform the document using the XSL stylesheet "%s": %s', $document->documentURI, $xsl->documentURI, $e->getMessage()), 0, $e);
            }
            
            // no errors and we got a document back? excellent. this will be our new baby from now. time to kill the old one
            
            // get the old document URI
            $documentUri = $document->documentURI;
            
            // and assign the new document to the old one
            $document = $newdoc;
            
            // save the old document URI just in case
            $document->documentURI = $documentUri;
        }
        
        return $document;
    }
    
    /**
     * Transforms a given document according to xml-stylesheet processing
     * instructions
     *
     * @param      XmlConfigDomDocument $document The document to act upon.
     * @param      string $environment The environment name.
     * @param      string $context The context name.
     *
     * @return     XmlConfigDomDocument The transformed document.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function transformProcessingInstructions(XmlConfigDomDocument $document, $environment, $context)
    {
        $transformations = array();
        $transformationInfo = array();
        
        $xpath = $document->getXpath();
        
        // see if there are <?xml-stylesheet... processing instructions
        $stylesheetProcessingInstructions = $xpath->query("//processing-instruction('xml-stylesheet')", $document);
        foreach ($stylesheetProcessingInstructions as $pi) {
            // yes! alright. trick: we create a doc fragment with the contents so we don't have to parse things by hand...
            $fragment = $document->createDocumentFragment();
            $fragment->appendXml('<foo ' . $pi->data . ' />');
            $type = $fragment->firstChild->getAttribute('type');
            // we process only the types below...
            if (in_array($type, array('text/xml', 'text/xsl', 'application/xml', 'application/xsl+xml'))) {
                $href = $href = $fragment->firstChild->getAttribute('href');
                
                if (strpos($href, '#') === 0) {
                    // the href points to an embedded XSL stylesheet (with ID reference), so let's see if we can find it
                    $stylesheets = $xpath->query("//*[@id='" . substr($href, 1) . "']", $document);
                    if ($stylesheets->length) {
                        // excellent. make a new doc from that element!
                        try {
                            $xsl = new XmlConfigDomDocument();
                            $xsl->appendChild($xsl->importNode($stylesheets->item(0), true));
                        } catch (\DOMException $dome) {
                            throw new ParseException(sprintf('Configuration file "%s" could not be parsed: Could not load XSL stylesheet "%s": %s', $document->documentURI, $href, $dome->getMessage()), 0, $dome);
                        }
                        
                        // and append to the list of XSLs to process
                        // TODO: spec mandates that external XSLs be processed first!
                        $transformations[] = $xsl;
                    } else {
                        throw new ParseException(sprintf('Configuration file "%s" could not be parsed because the inline stylesheet "%s" referenced in the "xml-stylesheet" processing instruction could not be found in the document.', $document->documentURI, $href));
                    }
                } else {
                    // href references an xsl file, remember the path
                    $transformationInfo[] = Toolkit::expandDirectives($href);
                }
                
                // remove the processing instructions after we dealt with them
                $pi->parentNode->removeChild($pi);
            }
        }
        
        return self::transform($document, $environment, $context, $transformationInfo, $transformations);
    }
    
    /**
     * Perform validation on a given document.
     *
     * @param      XmlConfigDomDocument $document       The document to act upon.
     * @param      string               $environment    The environment name.
     * @param      string               $context        The context name.
     * @param      array                $validationInfo An array of validation information.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function validate(XmlConfigDomDocument $document, $environment, $context, array $validationInfo = array())
    {
        // bail out right away if validation is disabled
        if (Config::get('core.skip_config_validation', false)) {
            return;
        }
        
        $errors = array();
        
        foreach ($validationInfo as $type => $files) {
            try {
                switch ($type) {
                    case self::VALIDATION_TYPE_XMLSCHEMA:
                        self::validateXmlschema($document, (array) $files);
                        break;
                    case self::VALIDATION_TYPE_RELAXNG:
                        self::validateRelaxng($document, (array) $files);
                        break;
                    case self::VALIDATION_TYPE_SCHEMATRON:
                        self::validateSchematron($document, $environment, $context, (array) $files);
                        break;
                }
            } catch (ParseException $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        if ($errors) {
            throw new ParseException(sprintf('Validation of configuration file "%s" failed:' . "\n\n%s", $document->documentURI, implode("\n\n", $errors)));
        }
    }

    /**
     * Clean up a given document.
     *
     * @param      XmlConfigDomDocument $document The document to clean up.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public static function cleanup(XmlConfigDomDocument $document)
    {
        // remove top-level <sandbox> element
        if ($sandbox = $document->getSandbox()) {
            $sandbox->parentNode->removeChild($sandbox);
        }
    }
    
    /**
     * Validate a given document according to XMLSchema-instance (xsi)
     * declarations.
     *
     * @param      XmlConfigDomDocument $document The document to act upon.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function validateXsi(XmlConfigDomDocument $document)
    {
        // next, find (and validate against) XML schema instance declarations
        $sources = array();
        if ($document->documentElement->hasAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation')) {
            // find locations. for namespaces, they are space separated pairs of a namespace URI and a schema location
            $locations = preg_split('/\s+/', $document->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation'));
            for ($i = 1; $i < count($locations); $i = $i + 2) {
                $sources[] = $locations[$i];
            }
        }
        // no namespace? then it's only one schema location in this attribute
        if ($document->documentElement->hasAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'noNamespaceSchemaLocation')) {
            $sources[] = $document->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'noNamespaceSchemaLocation');
        }
        if ($sources) {
            // we have instances to validate against...
            $schemas = array();
            foreach ($sources as &$source) {
                // so for each location, we need to grab the file and validate against this grabbed source code, as libxml often has a hard time retrieving stuff over HTTP
                $source = Toolkit::expandDirectives($source);
                if (parse_url($source, PHP_URL_SCHEME) === null && !Toolkit::isPathAbsolute($source)) {
                    // the schema location is relative to the XML file
                    $source = dirname($document->documentURI) . DIRECTORY_SEPARATOR . $source;
                }
                $schema = @file_get_contents($source);
                if ($schema === false) {
                    throw new UnreadableException(sprintf('XML Schema validation file "%s" for configuration file "%s" does not exist or is unreadable', $source, $document->documentURI));
                }
                $schemas[] = $schema;
            }
            // now validate them all
            self::validateXmlschemaSource($document, $schemas);
        }
    }
    
    /**
     * Validate the document against the given list of XML Schema files.
     *
     * @param      XmlConfigDomDocument $document        The document to act upon.
     * @param      array                $validationFiles An array of file names to validate against.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function validateXmlschema(XmlConfigDomDocument $document, array $validationFiles = array())
    {
        foreach ($validationFiles as $validationFile) {
            if (!is_resource($validationFile) && !is_readable($validationFile)) {
                throw new UnreadableException(sprintf('XML Schema validation file "%s" for configuration file "%s" does not exist or is unreadable', $validationFile, $document->documentURI));
            }
            
            try {
                $document->schemaValidate($validationFile);
            } catch (\DOMException $dome) {
                throw new ParseException(sprintf('XML Schema validation of configuration file "%s" failed:' . "\n\n%s", $document->documentURI, $dome->getMessage()), 0, $dome);
            }
        }
    }
    
    /**
     * Validate the document against the given list of XML Schema documents.
     *
     * @param      XmlConfigDomDocument $document          The document to act upon.
     * @param      array                $validationSources An array of schema documents to validate against.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      1.0.0
     */
    public static function validateXmlschemaSource(XmlConfigDomDocument $document, array $validationSources = array())
    {
        foreach ($validationSources as $validationSource) {
            try {
                $document->schemaValidateSource($validationSource);
            } catch (\DOMException $dome) {
                throw new ParseException(sprintf('XML Schema validation of configuration file "%s" failed:' . "\n\n%s", $document->documentURI, $dome->getMessage()), 0, $dome);
            }
        }
    }
    
    /**
     * Validate the document against the given list of RELAX NG files.
     *
     * @param      XmlConfigDomDocument $document        The document to act upon.
     * @param      array                $validationFiles An array of file names to validate against.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function validateRelaxng(XmlConfigDomDocument $document, array $validationFiles = array())
    {
        foreach ($validationFiles as $validationFile) {
            if (!is_readable($validationFile)) {
                throw new UnreadableException(sprintf('RELAX NG validation file "%s" for configuration file "%s" does not exist or is unreadable', $validationFile, $document->documentURI));
            }
            
            try {
                $document->relaxNGValidate($validationFile);
            } catch (\DOMException $dome) {
                throw new ParseException(sprintf('RELAX NG validation of configuration file "%s" failed:' . "\n\n%s", $document->documentURI, $dome->getMessage()), 0, $dome);
            }
        }
    }
    
    /**
     * Validate the document against the given list of Schematron files.
     *
     * @param      XmlConfigDomDocument $document        The document to act upon.
     * @param      string               $environment     The environment name.
     * @param      string               $context         The context name.
     * @param      array                $validationFiles An array of file names to validate against.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Noah Fontes <noah.fontes@bitextender.com>
     * @since      0.11.0
     */
    public static function validateSchematron(XmlConfigDomDocument $document, $environment, $context, array $validationFiles = array())
    {
        if (Config::get('core.skip_config_transformations', false)) {
            return;
        }
        
        // load the schematron processor
        $schematron = new SchematronProcessor();
        $schematron->setNode($document);
        // set some info (config file path, context name, environment name) as params
        // first arg is the namespace URI, which PHP doesn't support. awesome. see http://bugs.php.net/bug.php?id=30622 for the sad details
        // we could use "agavi:context" etc, that does work even without such a prefix being declared in the stylesheet, but that would be completely non-XML-ish, confusing, and against the spec. so we use dots instead.
        $schematron->setParameters(array(
            'agavi.config_path' => $document->documentURI,
            'agavi.environment' => $environment,
            'agavi.context' => $context,
        ));
        
        // loop over all validation files. those are .sch schematron schemas, which we transform to an XSL document that is then used to validate the source document :)
        foreach ($validationFiles as $href) {
            if (!is_readable($href)) {
                throw new UnreadableException(sprintf('Schematron validation file "%s" for configuration file "%s" does not exist or is unreadable', $href, $document->documentURI));
            }
            
            // load the .sch file
            try {
                $sch = new XmlConfigDomDocument();
                $sch->load($href);
            } catch (\DOMException $dome) {
                throw new ParseException(sprintf('Schematron validation of configuration file "%s" failed: Could not load schema file "%s": %s', $document->documentURI, $href, $dome->getMessage()), 0, $dome);
            }
            
            // perform the validation transformation
            try {
                $result = $schematron->transform($sch);
            } catch (\Exception $e) {
                throw new ParseException(sprintf('Schematron validation of configuration file "%s" failed: Transformation failed: %s', $document->documentURI, $e->getMessage()), 0, $e);
            }
            
            // validation ran okay, now we need to look at the result document to see if there are errors
            /** @var \DOMXPath $xpath */
            $xpath = $result->getXpath();
            $xpath->registerNamespace('svrl', self::NAMESPACE_SVRL_ISO);
            
            $results = $xpath->query('/svrl:schematron-output/svrl:failed-assert/svrl:text');
            if ($results->length) {
                $errors = array('Failed assertions:');
                
                foreach ($results as $result) {
                    $errors[] = $result->nodeValue;
                }
                
                $results = $xpath->query('/svrl:schematron-output/svrl:successful-report/svrl:text');
                if ($results->length) {
                    $errors[] = '';
                    $errors[] = 'Successful reports:';
                    foreach ($results as $result) {
                        $errors[] = $result->nodeValue;
                    }
                }
                
                throw new ParseException(sprintf('Schematron validation of configuration file "%s" failed:' . "\n\n%s", $document->documentURI, implode("\n", $errors)));
            }
        }
    }
}
