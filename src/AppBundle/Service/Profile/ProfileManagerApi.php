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

}