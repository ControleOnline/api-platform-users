<?php

namespace ControleOnline\Security;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\HttpOperation;

class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $em;
    private $resourceMetadataFactory;

    public function __construct(
        EntityManagerInterface $em,
        ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
    ) {
        $this->em = $em;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function supports(Request $request): ?bool
    {
        // Verifica se a operação do API Platform permite PUBLIC_ACCESS
        if ($this->isPublicAccessOperation($request)) {
            error_log('Path: ' . $request->getPathInfo() . ' is PUBLIC_ACCESS by API Platform operation');
            return false;
        }

        $key = $this->getKey($request);
        error_log('Path: ' . $request->getPathInfo() . ' - API Key present: ' . var_export($key !== null && !empty(trim($key)), true));
        return $key !== null && !empty(trim($key));
    }

    private function isPublicAccessOperation(Request $request): bool
    {
        try {
            // Obtém a classe da entidade e a operação a partir da requisição
            $resourceClass = $request->attributes->get('_api_resource_class');
            $operationName = $request->attributes->get('_api_operation_name');

            if (!$resourceClass || !$operationName) {
                return false;
            }

            // Obtém os metadados da entidade
            $metadata = $this->resourceMetadataFactory->create($resourceClass);

            // Itera sobre as operações disponíveis
            foreach ($metadata as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if ($operation->getName() === $operationName && $operation instanceof HttpOperation) {
                        $security = $operation->getSecurity();
                        if ($security && is_string($security)) {
                            // Verifica se a operação tem is_granted('PUBLIC_ACCESS')
                            return strpos($security, 'is_granted(\'PUBLIC_ACCESS\'') !== false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error checking API Platform operation: ' . $e->getMessage());
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $this->getKey($request);
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new Passport(
            new UserBadge($apiToken, function ($apiToken) {
                $user = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiToken]);
                if (null === $user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }

                return $user;
            }),
            new CustomCredentials(
                fn($credentials, $user) => true,
                $apiToken
            )
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        error_log('Authentication required triggered for path: ' . $request->getPathInfo());
        return new JsonResponse(['message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    private function getKey(Request $request): ?string
    {
        return $request->headers->get('API-KEY') ?? $request->headers->get('API-TOKEN');
    }
}