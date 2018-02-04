<?php

namespace AppBundle\Service\Profile;


use AppBundle\Entity\Container;
use AppBundle\Entity\Profile;
use AppBundle\Service\LxdApi\ProfileApi;
use Doctrine\ORM\EntityManager;

class ProfileManagerApi
{
    protected $injectedService;
    protected $em;

    public function __construct(EntityManager $em, ProfileApi $injectedService)
    {
        $this->injectedService = $injectedService;
        $this->em = $em;
    }

    /**
     * Used internally in the container creation process to link the profile to host and container
     * and publish the profile to the host if needed
     *
     * @param Profile $profile
     * @param Container $container
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function enableProfileForContainer(Profile $profile, Container $container) : bool {
        $profile->addContainer($container);
        $host = $container->getHost();
        if($profile->isHostLinked($host)){
            return true;
        }

        $result = $this->injectedService->createProfileOnHost($host, $profile);

        if($result->code != 201 && $result->code != 400){
            return false;
        }
        //HTTP 400 - Profile is already located on the Host but was not linked in Lexic - create link in Lexic

        $profile->addHost($host);

        $this->em->persist($profile);
        $this->em->flush();
        return true;
    }

    /**
     * Used internally to remove the link from a Profile to a Container and Host, it will also remove the Profile from the Host
     * if this was the last Container using it
     *
     * @param Profile $profile
     * @param Container $container
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function disableProfileForContainer(Profile $profile, Container $container) : bool {
        $host = $container->getHost();
        //Check if this container was the only one using this profile on the host
        if($profile->numberOfContainersMatchingProfile($host->getContainers()) == 1){
            $profile->removeHost($host);
            $result = $this->injectedService->deleteProfileOnHost($host, $profile);
            if($result->code != 200){
                return false;
            }
        }
        //ELSE LXC-Profile should remain on Host

        //General operation
        $profile->removeContainer($container);
        $this->em->persist($profile);
        $this->em->flush();
        return true;
    }

}