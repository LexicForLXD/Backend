<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 09.11.2017
 * Time: 22:35
 */

namespace AppBundle\Provider;

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;


class UserProvider implements UserProviderInterface
{
    protected $userRepository;

    public function __construct(EntityRepository $userRepository){
        $this->userRepository = $userRepository;
    }

    public function loadUserByUsername($username)
    {

        $user = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }

}