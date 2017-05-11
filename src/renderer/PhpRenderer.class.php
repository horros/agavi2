<?php
namespace Agavi\Renderer;

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
use Agavi\View\TemplateLayer;

/**
 * A renderer produces the output as defined by a View
 *
 * @package    agavi
 * @subpackage renderer
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class PhpRenderer extends Renderer implements ReusableRendererInterface
{
    /**
     * @var        string A string with the default template file extension,
     *                    including the dot.
     */
    protected $defaultExtension = '.php';
    
    /**
     * @var        TemplateLayer Temporary storage for the template layer,
     *                                used during rendering.
     */
    private $layer = null;
    
    /**
     * @var        array Temporary storage for the template layer, used during
     *                   rendering.
     */
    private $attributes = null;
    
    /**
     * @var        array Temporary storage for the template layer, used during
     *                   rendering.
     */
    private $slots = null;
    
    /**
     * @var        array Temporary storage for additional assigns, used during
     *                   rendering.
     */
    private $moreAssigns = null;
    
    /**
     * Render the presentation and return the result.
     *
     * @param      TemplateLayer $layer The template layer to render.
     * @param      array         $attributes The template variables.
     * @param      array         $slots The slots.
     * @param      array         $moreAssigns Associative array of additional assigns.
     *
     * @return     string A rendered result.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function render(TemplateLayer $layer, array &$attributes = array(), array &$slots = array(), array &$moreAssigns = array())
    {
        // DO NOT USE VARIABLES IN HERE, THEY MIGHT INTERFERE WITH TEMPLATE VARS
        $this->layer = $layer;
        $this->attributes =& $attributes;
        $this->slots =& $slots;
        $this->moreAssigns =& self::buildMoreAssigns($moreAssigns, $this->moreAssignNames);
        unset($layer, $attributes, $slots, $moreAssigns);
        
        if ($this->extractVars) {
            extract($this->attributes, EXTR_REFS | EXTR_PREFIX_INVALID, '_');
        } else {
            ${$this->varName} =& $this->attributes;
        }
        
        ${$this->slotsVarName} =& $this->slots;
        
        foreach ($this->assigns as $name => $getter) {
            ${$name} = $this->context->$getter();
        }
        unset($name, $getter);
        
        extract($this->moreAssigns, EXTR_REFS | EXTR_PREFIX_INVALID, '_');
        
        ob_start();
        
        require($this->layer->getResourceStreamIdentifier());
        
        $retval = ob_get_contents();
        ob_end_clean();
        
        unset($this->layer, $this->attributes, $this->slots, $this->moreAssigns);
        
        return $retval;
    }
}
