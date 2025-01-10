<?php

namespace ControleOnline\Controller\Oauth;

use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class DefaultClientController extends AbstractController
{

    protected $clientId;
    protected $clientSecret;
    protected $provider;

    public function __construct(protected EntityManagerInterface $manager, protected UserService $userService) {}

    protected function connectAction()
    {
        $authUrl = $this->provider->getAuthorizationUrl();
        header('Location: ' . $authUrl);
        exit;
    }



    protected function returnAction(Request $request): JsonResponse
    {
        try {


            if ($request->get('code'))
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);

            if ($request->get('access_token'))
                $token = new AccessToken([
                    'access_token' => $request->get('access_token'),
                ]);

            if ($request->get('code'))
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);

            $ownerDetails = $this->provider->getResourceOwner($token);

            $user = $this->userService->discoveryUser($ownerDetails->getEmail(), md5(microtime()), $ownerDetails->getFirstName(), $ownerDetails->getLasttName());

            $data = [
                'id'        => $user->getPeople()->getId(),
                'username'  => $user->getUsername(),
                'roles'     => $user->getRoles(),
                'api_key'   => $user->getApiKey(),
                'people'    => $user->getPeople()->getId(),
                'mycompany' => $this->userService->getCompanyId($user),
                'realname'  => $ownerDetails->getFirstName(),
                'avatar'    => $user->getPeople()->getImage() ? '/files/' . $user->getPeople()->getImage()->getId() . '/download' : null,
                'email'     => $ownerDetails->getEmail(),
                'active'    => (int) $user->getPeople()->getEnabled(),
            ];



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
