<?php

namespace ControleOnline\Controller\Oauth\Google;

use ControleOnline\Controller\Oauth\DefaultClientController;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use League\OAuth2\Client\Provider\Google;

class GoogleConnectController extends DefaultClientController
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected UserService $userService,
        private DomainService $domainService
    ) {
        $this->clientId       = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $this->clientSecret   = $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'];

        $this->provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' . $this->domainService->getMainDomain() . '/oauth/google/return',
            //'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts

        ]);
    }

    #[Route('/oauth/google/connect', name: 'google_connect', methods: ['GET'])]
    public function connectAction()
    {
        return parent::connectAction();
    }

    #[Route('/oauth/google/return', name: 'google_return', methods: ['GET', 'POST'])]
    public function returnAction(Request $request): JsonResponse
    {
        return parent::returnAction($request);
    }


}
