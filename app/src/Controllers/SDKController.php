<?php

namespace Pushbase\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Pushbase\Config\Config;
use Pushbase\Auth;

class SDKController extends BaseController
{
    private function replaceEnvironmentVariables(string $content): string
    {
        $config = new Config();
        $replacements = [
            '{{APP_URL}}' => $config->get('app.url'),
            '{{CLIENT_URL}}' => $config->get('client.url'),
            '{{VAPID_PUBLIC_KEY}}' => $config->get('firebase.vapid.public'),
            '{{FIREBASE_APIKEY}}' => $config->get('firebase.apiKey'),
            '{{FIREBASE_AUTHDOMAIN}}' => $config->get('firebase.authDomain'),
            '{{FIREBASE_PROJECTID}}' => $config->get('firebase.projectId'),
            '{{FIREBASE_STORAGEBUCKET}}' => $config->get('firebase.storageBucket'),
            '{{FIREBASE_MESSAGINGSENDERID}}' => $config->get('firebase.messagingSenderId'),
            '{{FIREBASE_APPID}}' => $config->get('firebase.appId'),
            '{{FIREBASE_MEASUREMENTID}}' => $config->get('firebase.measurementId')
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function clientSDK(): ResponseInterface
    {
        $content = file_get_contents(__DIR__ . '/../../sdk/clientSDK.js');
        $content = $this->replaceEnvironmentVariables($content);

        return new Response(
            200,
            ['Content-Type' => 'application/javascript'],
            $content
        );
    }

    public function serviceWorker(): ResponseInterface
    {
        $content = file_get_contents(__DIR__ . '/../../sdk/serviceWorker.js');
        $content = $this->replaceEnvironmentVariables($content);

        return new Response(
            200,
            ['Content-Type' => 'application/javascript'],
            $content
        );
    }

    public function downloadPushBaseSW(): ResponseInterface
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

        $content = file_get_contents(__DIR__ . '/../../sdk/pushBaseSW.js');
        $content = $this->replaceEnvironmentVariables($content);

        return new Response(
            200,
            [
                'Content-Type' => 'application/javascript',
                'Content-Disposition' => 'attachment; filename=pushBaseSW.js'
            ],
            $content
        );
    }
}
