<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerNamespaces([
    'Newsapp\Controllers' => $config->application->controllersDir,
    'Newsapp\Models' => $config->application->modelsDir,
    'Newsapp' => $config->application->libraryDir,
]);

$loader->register();
