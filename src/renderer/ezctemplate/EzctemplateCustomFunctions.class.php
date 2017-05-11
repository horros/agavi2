<?php
namespace Agavi\Renderer\EzcTemplate;

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
use Agavi\Translation\Locale;

/**
 * A renderer produces the output as defined by a View
 *
 * @package    agavi
 * @subpackage renderer
 *
 * @author     Felix Weis <mail@felixweis.com>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class EzctemplateCustomFunctions implements ezcTemplateCustomFunction
{
    /**
     * Get the definition for a CustomFunction (for ezcTemplate parser)
     *
     * @param      string $name Name of the CustomFunction
     *
     * @return     ezcTemplateCustomBlockDefinition Definition of the CustomFunction
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.1
     */
    public static function getCustomFunctionDefinition($name)
    {
        $def = new ezcTemplateCustomFunctionDefinition();
        
        $def->class = __CLASS__;
        $def->sendTemplateObject = true;
        $def->method = $name;
        
        switch ($name) {
            case 'call':
                $def->sendTemplateObject = false;
                return $def;
            
            case 'route':
            case '_':
            case '__':
            case '_c':
            case '_d':
            case '_n':
                return $def;
            
            default:
                return false;
        }
    }

    /**
     * Custom function for calling arbitrary methods on arbitrary methods.
     *
     * @param      string $function The PHP callback to run.
     * @param      array  $param_arr An array of arguments
     *
     * @return     mixed Whatever the called function returns.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function call($function, $param_arr = array())
    {
        return call_user_func_array($function, $param_arr);
    }
    
    /**
     * Translate a message into the current locale.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      mixed       $message The message.
     * @param      string      $domain The domain in which the translation should be done.
     * @param      Locale      $locale The locale which should be used for formatting.
     *                         Defaults to the currently active locale.
     * @param      array       $parameters The parameters which should be used for sprintf on
     *                         the translated string.
     *
     * @return     string The translated message.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function _(ezcTemplate $obj, $message, $domain = null, $locale = null, $parameters = null)
    {
        return $obj->getContext()->getTranslationManager()->_($message, $domain, $locale, $parameters);
    }

    /**
     * Translate a singular/plural message into the current locale.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      string      $singularMessage The message for the singular form.
     * @param      string      $pluralMessage The message for the plural form.
     * @param      int         $amount The amount for which the translation should happen.
     * @param      string      $domain The domain in which the translation should be done.
     * @param      Locale      $locale The locale which should be used for formatting.
     *                         Defaults to the currently active locale.
     * @param      array       $parameters The parameters which should be used for sprintf on
     *                         the translated string.
     *
     * @return     string The translated message.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function __(ezcTemplate $obj, $singularMessage, $pluralMessage, $amount, $domain = null, $locale = null, $parameters = null)
    {
        return $obj->getContext()->getTranslationManager()->__($singularMessage, $pluralMessage, $amount, $domain, $locale, $parameters);
    }
    
    /**
     * Formats a currency amount in the current locale.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      mixed       $number The number to be formatted.
     * @param      string      $domain The domain in which the amount should be formatted.
     * @param      Locale      $locale The locale which should be used for formatting.
     *                         Defaults to the currently active locale.
     *
     * @return     string The formatted number.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function _c(ezcTemplate $obj, $number, $domain = null, $locale = null)
    {
        return $obj->getContext()->getTranslationManager()->_c($number, $domain, $locale);
    }

    /**
     * Formats a date in the current locale.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      mixed       $date The date to be formatted.
     * @param      string      $domain The domain in which the date should be formatted.
     * @param      Locale      $locale The locale which should be used for formatting.
     *                         Defaults to the currently active locale.
     *
     * @return     string The formatted date.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function _d(ezcTemplate $obj, $date, $domain = null, $locale = null)
    {
        return $obj->getContext()->getTranslationManager()->_d($date, $domain, $locale);
    }
    
    /**
     * Formats a number in the current locale.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      mixed       $number The number to be formatted.
     * @param      string      $domain The domain in which the number should be formatted.
     * @param      Locale      $locale The locale which should be used for formatting.
     *                         Defaults to the currently active locale.
     *
     * @return     string The formatted number.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function _n(ezcTemplate $obj, $number, $domain = null, $locale = null)
    {
        return $obj->getContext()->getTranslationManager()->_n($number, $domain, $locale);
    }
    
    /**
     * Generate an Agavi route.
     *
     * @param      ezcTemplate $obj The current template object instance.
     * @param      string      $name The name of the route to generate.
     * @param      array       $params An array of route parameters.
     * @param      array       $options An array of gen options.
     *
     * @return     string The generated route.
     *
     * @author     Felix Weis <mail@felixweis.com>
     * @since      0.11.0
     */
    public static function route(ezcTemplate $obj, $name, array $params = array(), $options = array())
    {
        return $obj->getContext()->getRouting()->gen($name, $params, $options);
    }
}
