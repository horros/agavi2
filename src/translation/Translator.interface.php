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

namespace Agavi\Translation;

use Agavi\Core\Context;

/**
 * AgaviITranslator defines the interface for different translator
 * implementations (like gettext, XLIFF, ...)
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
interface TranslatorInterface
{
    /**
     * Retrieve the current application context.
     *
     * @return     Context The current Context instance.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getContext();

    /**
     * Initialize this Translator.
     *
     * @param      Context $context    The current application context.
     * @param      array   $parameters An associative array of initialization parameters
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function initialize(Context $context, array $parameters = array());

    /**
     * Translates a message into the defined language.
     *
     * @param      mixed  $message The message to be translated.
     * @param      string $domain  The domain of the message.
     * @param      Locale $locale  The locale to which the message should be
     *                         translated.
     *
     * @return     string The translated message.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function translate($message, $domain, Locale $locale = null);

    /**
     * This method gets called by the translation manager when the default locale
     * has been changed.
     *
     * @param      Locale $newLocale The new default locale.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function localeChanged($newLocale);
}
