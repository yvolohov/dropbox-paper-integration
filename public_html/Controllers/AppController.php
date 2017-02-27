<?php

namespace DPI\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silex\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AppController
{
    public function index(Request $request, Application $app)
    {
        $authStatus = $app['session']->has('token_params');

        if ($authStatus) {
            return $app['twig']->render('load.twig');
        }
        else {
            $clientId = $app['config']['app_key'];
            $redirectURI = $app['config']['redirect_uri'];
            $authURL = "https://www.dropbox.com/1/oauth2/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectURI}";
            return new RedirectResponse($authURL);
        }
    }

    public function oauthRedirectURI(Request $request, Application $app)
    {
        $code = $request->query->get('code', false);

        if (!$code) {
            $app->abort(400, 'Wrong authorization code');
        }

        $client = new Client();
        
        try {
            $response = $client->post('https://api.dropboxapi.com/1/oauth2/token', [
                'form_params' => [
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'client_id' => $app['config']['app_key'],
                    'client_secret' => $app['config']['app_secret'],
                    'redirect_uri' => $app['config']['redirect_uri']
                ]
            ]);
        }
        catch (RequestException $e) {
            $app->abort(500, $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            $app->abort(500, 'Can\'t get token');
        }

        $tokenParams = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        $app['session']->set('token_params', $tokenParams);
        return new RedirectResponse('/');
    }

    public function removeToken(Request $request, Application $app)
    {
        $app['session']->remove('token_params');
        return new RedirectResponse('/');
    }

    public function downloadFile(Request $request, Application $app, $id)
    {
        return $id;
    }
}