<?php

namespace DPI\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silex\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Dompdf\Dompdf;
use Dompdf\Options;

class AppController
{
    public function index(Request $request, Application $app)
    {
        $authStatus = $app['session']->has('token_params');

        if ($authStatus) {
            return $app['twig']->render('load.twig', [
                'token_params' => json_encode($app['session']->get('token_params'))
            ]);
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
        if (!$app['session']->has('token_params')) {
            return new JsonResponse(['success' => false, 'message' => 'Dropbox token not found'], 400);
        }

        $tokenParams = $app['session']->get('token_params');
        $client = new Client();

        try {
            $response = $client->post('https://api.dropboxapi.com/2/paper/docs/download', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenParams['access_token'],
                    'Dropbox-API-Arg' => \GuzzleHttp\json_encode([
                        'doc_id' => $id,
                        'export_format' => 'html'
                    ])
                ]
            ]);
        }
        catch (RequestException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['success' => false, 'message' => 'Wrong response from Dropbox Paper'], 500);
        }

        try {
            /* в этом заголовке возвращается имя файла и другие параметры */
            $fileProps = $response->getHeader('Dropbox-Api-Result');
            //error_log(print_r($fileProps, True));

            file_put_contents("files/{$id}.html", $response->getBody());
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dpdf = new Dompdf($options);
            $dpdf->loadHtml($response->getBody());
            $dpdf->render();
            $output = $dpdf->output();
            file_put_contents("files/{$id}.pdf", $output);
        }
        catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true, 'message' => 'OK'], 200);
    }
}