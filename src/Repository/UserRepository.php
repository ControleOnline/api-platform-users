<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?\Symfony\Component\Security\Core\User\UserInterface
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function updatePassword(string $email, string $password): ?User
    {
        if ($user = $this->findOneByEmail($email)) {
            $user->setPassword($password);

            $this->getEntityManager()->persist($user);

            $this->getEntityManager()->flush();

            return $user;
        }
    }

    public function getActiveUserByEmail($email)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email  = :email')
            ->andwhere('u.active = 1')
            ->setParameter('email', $email)
            ->getQuery()->getOneOrNullResult();
    }

    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username  = :username')
            ->setParameter('username', $username)
            ->getQuery()->getOneOrNullResult();
    }
}
