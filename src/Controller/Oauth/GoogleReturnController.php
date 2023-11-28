<?php

namespace ControleOnline\Controller\Oauth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use League\OAuth2\Client\Provider\Google;

class GoogleReturnController extends DefaultClientController
{



    public function __construct(EntityManagerInterface $entityManager, Request $request)
    {
        $this->manager = $entityManager;
        $this->clientId       = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $this->clientSecret   = $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'];

        $this->provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' . $_SERVER['HTTP_HOST'] . '/oauth/google/return',
            //'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
        ]);
        return parent::returnAction($request);
    }
}
