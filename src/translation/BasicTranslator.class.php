<?php
namespace Agavi\Translation;

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
use Agavi\Core\Context;

/**
 * BasicTranslator defines some base functions for all translators.
 *
 * @package    agavi
 * @subpackage translation
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
abstract class BasicTranslator implements TranslatorInterface
{
    /**
     * @var        Context A Context instance.
     */
    protected $context = null;

    /**
     * Retrieve the current application context.
     *
     * @return     Context The current Context instance.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Initialize this Translator.
     *
     * @param      Context $context The current application context.
     * @param      array   $parameters An associative array of initialization parameters
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        $this->context = $context;
    }

    /**
     * This method gets called by the translation manager when the default locale
     * has been changed.
     *
     * @param      Locale $newLocale The new default locale.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function localeChanged($newLocale)
    {
    }
}
