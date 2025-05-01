<?php

namespace ControleOnline\Controller\Oauth\Instagram;

use ControleOnline\Controller\Oauth\DefaultClientController;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use League\OAuth2\Client\Provider\Instagram;

class InstagramController extends DefaultClientController
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected UserService $userService,
        private DomainService $domainService
    )
    {
        $this->clientId       = $_ENV['OAUTH_INSTAGRAM_CLIENT_ID'];
        $this->clientSecret   = $_ENV['OAUTH_INSTAGRAM_CLIENT_SECRET'];

        $this->provider = new Instagram([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' .$this->domainService->getMainDomain() . '/oauth/instagram/return',
        ]);
    }

    #[Route('/oauth/instagram/connect', name: 'connect_instagram_start')]
    public function connectAction()
    {
        return parent::connectAction();
    }

    #[Route('/oauth/instagram/return', name: 'connect_instagram_return', methods: ['GET', 'POST'])]
    public function returnAction(Request $request): JsonResponse
    {
        return parent::returnAction($request);
    }
}