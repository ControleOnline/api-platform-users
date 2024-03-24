<?php

namespace ControleOnline\Controller\Oauth;

use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class DefaultClientController extends AbstractController
{

    protected  $clientId;
    protected $clientSecret;
    protected $provider;
    /** //
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    protected $manager = null;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    protected function connectAction()
    {
        $authUrl = $this->provider->getAuthorizationUrl();
        header('Location: ' . $authUrl);
        exit;
    }


    protected function discoveryPeople($ownerDetails)
    {
        $email = $this->manager->getRepository(Email::class)
            ->findOneBy([
                'email'       => $ownerDetails->getEmail(),
            ]);
        if ($email) {
            $people = $email->getPeople();
        } else {
            $email = new Email();
            $email->setEmail($ownerDetails->getEmail());
            $this->manager->persist($email);
        }

        if (!$people) {

            $lang = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
            $people = new People();
            $people->setAlias($ownerDetails->getFirstName());
            $people->setName($ownerDetails->getLastName());
            $people->setLanguage($lang);
            $people->setBilling(0);
            $people->setBillingDays('daily');
            $people->setPaymentTerm(1);
            $people->setIcms(0);
            $email->setPeople($people);
            $this->manager->persist($email);
        }

        $this->manager->persist($people);
        $this->manager->flush();
        return $people;
    }

    protected function createUser($ownerDetails)
    {
        $people = $this->discoveryPeople($ownerDetails);

        $user = new User();
        $user->setPeople($people);
        $user->setHash('');
        $user->setUsername($ownerDetails->getEmail());

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }
    protected function discoveryUser($token)
    {
        $ownerDetails = $this->provider->getResourceOwner($token);

        $user = $this->manager->getRepository(User::class)
            ->findOneBy([
                'username'       => $ownerDetails->getEmail(),
            ]);
        if (!$user)


            $user = $this->createUser($ownerDetails);



        $data = [
            'username'  => $user->getUsername(),
            'roles'     => $user->getRoles(),
            'api_key'   => $user->getApiKey(),
            'people'    => $user->getPeople()->getId(),
            'mycompany' => $this->getCompanyId($user),
            'realname'  => $ownerDetails->getFirstName(),
            'avatar'    => $user->getPeople()->getFile() ? '/files/download/' . $user->getPeople()->getFile()->getId() : null,
            'email'     => $ownerDetails->getEmail(),
            'active'    => (int) $user->getPeople()->getEnabled(),
        ];

        return  $data;
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


            $data = $this->discoveryUser($token);
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

    private function getCompany(User $user)
    {
        $peopleLink = $user->getPeople()->getLink()->first();

        if ($peopleLink !== false && $peopleLink->getCompany() instanceof People)
            return $peopleLink->getCompany();
    }

    private function getCompanyId(User $user)
    {
        $company = $this->getCompany($user);
        return $company ? $company->getId() : null;
    }
}
