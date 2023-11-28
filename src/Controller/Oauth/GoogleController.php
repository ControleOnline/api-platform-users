<?php

namespace ControleOnline\Controller\Oauth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use League\OAuth2\Client\Provider\Google;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GoogleController extends DefaultClientController
{



    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        parent::__construct($entityManager, $container);

        $this->clientId       = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $this->clientSecret   = $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'];

        $this->provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' . $_SERVER['HTTP_HOST'] . '/oauth/google/return',
            //'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
        ]);
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/oauth/google/connect", name="connect_google_start")
     */
    public function connectAction()
    {
        return  parent::connectAction();
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/oauth/google/return", name="connect_google_return" , methods={"GET", "POST"})
     */
    public function returnAction(Request $request): JsonResponse
    {
        return parent::returnAction($request);
    }
}
