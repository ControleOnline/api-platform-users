<?php

namespace ControleOnline\Controller\Oauth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use League\OAuth2\Client\Provider\Instagram;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstagramController extends DefaultClientController
{



    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        parent::__construct($entityManager, $container);
        $this->clientId       = $_ENV['OAUTH_INSTAGRAM_CLIENT_ID'];
        $this->clientSecret   = $_ENV['OAUTH_INSTAGRAM_CLIENT_SECRET'];

        $this->provider = new Instagram([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' . $_SERVER['HTTP_HOST'] . '/oauth/instagram/return',
            //'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Instagram Apps for Business accounts
        ]);
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/oauth/instagram/connect", name="connect_instagram_start")
     */
    public function connectAction()
    {
        return  parent::connectAction();
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/oauth/instagram/return", name="connect_instagram_return" , methods={"GET", "POST"})
     */
    public function returnAction(Request $request): JsonResponse
    {
        return parent::returnAction($request);
    }
}
