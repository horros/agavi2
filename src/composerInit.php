<?php

class ComposerLoaderShim
{
    protected $triggerClasses = array(
        'Agavi\\Config\\Config' => true,
        'Agavi\\Core\\Agavi' => true,
        'Agavi\\Util\\Autoloader' => true,
        'Agavi\\Util\\Inflector' => true,
        'Agavi\\Util\\ArrayPathDefinition' => true,
        'Agavi\\Util\\VirtualArrayPath' => true,
        'Agavi\\Util\\ParameterHolder' => true,
        'Agavi\\Config\\ConfigCache' => true,
        'Agavi\\Exception\\AgaviException' => true,
        'Agavi\\Exception\\AutoloadException' => true,
        'Agavi\\Exception\\CacheException' => true,
        'Agavi\\Exception\\ConfigurationException' => true,
        'Agavi\\Exception\\UnreadableException' => true,
        'Agavi\\Exception\\ParseException' => true,
        'Agavi\\Util\\Toolkit' => true,
    );
    
    public function trigger($className)
    {
        if (!empty($this->triggerClasses[$className])) {
            require_once(__DIR__ . '/agavi.php');
        }
    }
}

spl_autoload_register(array(new ComposerLoaderShim(), 'trigger'));
