<?php
use \Agavi\Config\Config;

Config::set('core.testing_dir', realpath(__DIR__));
Config::set('core.app_dir', realpath(__DIR__.'/sandbox/app/'));
Config::set('core.cache_dir', Config::get('core.app_dir') . '/cache'); // for the clearCache() before bootstrap()
Config::set('app.namespace', 'Sandbox');

// Make sure we have a clean cache
\Agavi\Util\Toolkit::clearCache();
