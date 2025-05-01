<?php

namespace ControleOnline\Controller\Oauth\iFood;

use ControleOnline\Controller\Oauth\DefaultClientController;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class iFoodReturnController extends DefaultClientController
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected UserService $userService,
        private DomainService $domainService
    ) {
        $this->clientId     = $_ENV['OAUTH_IFOOD_CLIENT_ID'];
        $this->clientSecret = $_ENV['OAUTH_IFOOD_CLIENT_SECRET'];

        $this->provider = new GenericProvider([
            'clientId'                => $this->clientId,
            'clientSecret'            => $this->clientSecret,
            'redirectUri'             => 'https://' . $this->domainService->getMainDomain() . '/oauth/ifood/return',
            'urlAuthorize'            => 'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/authorize',
            'urlAccessToken'          => 'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token',
            'urlResourceOwnerDetails' => '', // iFood não usa isso
            'scopes' => 'merchant order catalog financial review logistics shipping item picking promotion events'
        ]);
    }

    #[Route('/oauth/ifood/connect', name: 'ifood_connect', methods: ['GET'])]
    public function connectAction()
    {
        return parent::connectAction();
    }

    #[Route('/oauth/ifood/return', name: 'ifood_return', methods: ['GET', 'POST'])]
    public function returnAction(Request $request): JsonResponse
    {
        return parent::returnAction($request);
    }
}
