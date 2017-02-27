<?php

namespace DPI\Controllers\Routes;

use Silex\Application as Application;

function setRoutes(Application $app)
{
    $app->get('/', 'DPI\Controllers\AppController::index');
    $app->get('/oauth-redirect-uri', 'DPI\Controllers\AppController::oauthRedirectURI');
    $app->get('/remove-token', 'DPI\Controllers\AppController::removeToken');
    $app->get('/download/{id}', 'DPI\Controllers\AppController::downloadFile');
}