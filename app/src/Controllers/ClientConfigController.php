<?php

namespace alo\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use alo\Config\Config;
use alo\Auth;

class ClientConfigController extends BaseController
{
    public function index(): ResponseInterface
    {
        $auth = Auth::getInstance();
        if (!$auth->isAuthenticated()) {
            return new Response(
                403,
                ['Location' => '/login']
            );
        }

        $user = $auth->getUser();
        if (!$user || $user['role'] == 'editor') {
            return new Response(
                403,
                ['Location' => '/login']
            );
        }

        $config = new Config();
        $data = [
            'appUrl' => $config->get('app.url'),
            'clientUrl' => $config->get('client.url')
        ];

        return $this->render('main/client', $data);
    }
}
