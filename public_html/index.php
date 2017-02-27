<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

/* options */
$config = [];

if (file_exists(__DIR__.'/settings.php')) {
    require_once __DIR__.'/settings.php';
}

$app['config'] = $config;

/* Twig template engine */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates'
));

/* Session */
$app->register(new Silex\Provider\SessionServiceProvider());

/* Connect routes */
DPI\Controllers\Routes\setRoutes($app);
$app->run();