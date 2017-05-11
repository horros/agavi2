<?php

require(__DIR__ . '/../src/testing.php');

require(__DIR__ . '/config.php');

\Agavi\Testing\PhpUnitCli::dispatch($_SERVER['argv']);
