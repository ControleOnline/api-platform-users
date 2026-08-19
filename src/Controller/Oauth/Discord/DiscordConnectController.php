<?php

namespace ControleOnline\Controller\Oauth\Discord;

use ControleOnline\Controller\Oauth\DefaultClientController;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wohali\OAuth2\Client\Provider\Discord;

class DiscordConnectController extends DefaultClientController
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected UserService $userService,
        private DomainService $domainService
    ) {
        $this->clientId       = $_ENV['OAUTH_DISCORD_CLIENT_ID'] ?? '';
        $this->clientSecret   = $_ENV['OAUTH_DISCORD_CLIENT_SECRET'] ?? '';

        $this->provider = new Discord([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => 'https://' . $this->domainService->getMainDomain() . '/oauth/discord/return',
        ]);
    }

    #[Route('/oauth/discord/connect', name: 'discord_connect', methods: ['GET'])]
    public function connectAction()
    {
        return parent::connectAction();
    }

    #[Route('/oauth/discord/return', name: 'discord_return', methods: ['GET', 'POST'])]
    public function returnAction(Request $request): JsonResponse
    {
        try {
            $token = null;

            if ($request->get('code')) {
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
            }

            if ($request->get('access_token')) {
                $token = new AccessToken([
                    'access_token' => $request->get('access_token'),
                ]);
            }

            if (empty($token)) {
                throw new Exception('Missing code or access_token');
            }

            $ownerDetails = $this->provider->getResourceOwner($token);

            $email = method_exists($ownerDetails, 'getEmail') ? $ownerDetails->getEmail() : null;
            if (!$email) {
                $data = $ownerDetails->toArray();
                $email = $data['email'] ?? null;
            }
            if (!$email) {
                throw new Exception('Discord account has no email (scope email required)');
            }

            $username = method_exists($ownerDetails, 'getUsername')
                ? $ownerDetails->getUsername()
                : ($ownerDetails->toArray()['username'] ?? 'Discord User');

            $user = $this->userService->discoveryUser(
                $email,
                md5(microtime()),
                $username,
                ''
            );

            $data = $this->userService->getUserSession($user);

            return new JsonResponse([
                'response' => [
                    'data'    => $data,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
}
