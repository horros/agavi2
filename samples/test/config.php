<?php

Config::set('core.testing_dir', realpath(__DIR__));
Config::set('core.app_dir', realpath(__DIR__.'/../app/'));
Config::set('core.cache_dir', Config::get('core.app_dir') . '/cache'); // for the clearCache() before bootstrap()

?>