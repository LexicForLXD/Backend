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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function enableProfileForContainer(Profile $profile, Container $container){
        $profile->addContainer($container);
        $host = $container->getHost();
        if($profile->isHostLinked($host)){
            return;
        }

        $this->injectedService->createProfileOnHost($host, $profile);

        $profile->addHost($host);

        $this->em->persist($profile);
        $this->em->flush();
    }

    /**
     * Used internally to remove the link from a Profile to a Container and Host, it will also remove the Profile from the Host
     * if this was the last Container using it
     *
     * @param Profile $profile
     * @param Container $container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function disableProfileForContainer(Profile $profile, Container $container){
        $host = $container->getHost();
        //Check if this container was the only one using this profile on the host
        if($profile->numberOfContainersMatchingProfile($host->getContainers()) == 1){
            $profile->removeHost($host);
            $this->injectedService->deleteProfileOnHost($host, $profile);
        }
        //ELSE LXC-Profile should remain on Host

        //General operation
        $profile->removeContainer($container);
        $this->em->persist($profile);
        $this->em->flush();
    }

}