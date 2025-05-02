<?php

namespace alo\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use alo\Config\Config;
use alo\Auth;
use MatthiasMullie\Minify\JS;

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

    /**
     * Minify JavaScript content using matthiasmullie/minify library
     *
     * @param string $content JavaScript content to minify
     * @return string Minified JavaScript content
     */
    private function minifyJavaScript(string $content): string
    {
        $minifier = new JS();
        $minifier->add($content);
        return $minifier->minify();
    }

    public function clientSDK(): ResponseInterface
    {
        $content = file_get_contents(__DIR__ . '/../../sdk/clientSDK.js');
        $content = $this->replaceEnvironmentVariables($content);
        $content = $this->minifyJavaScript($content);

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
        $content = $this->minifyJavaScript($content);

        return new Response(
            200,
            ['Content-Type' => 'application/javascript'],
            $content
        );
    }

    public function downloadaloSW(): ResponseInterface
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

        $content = file_get_contents(__DIR__ . '/../../sdk/aloSW.js');
        $content = $this->replaceEnvironmentVariables($content);
        $content = $this->minifyJavaScript($content);

        return new Response(
            200,
            [
                'Content-Type' => 'application/javascript',
                'Content-Disposition' => 'attachment; filename=aloSW.js'
            ],
            $content
        );
    }
}
