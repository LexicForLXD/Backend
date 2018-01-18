<?php
/**
 * Created by PhpStorm.
 * User: lionf
 * Date: 12.01.2018
 * Time: 10:54
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Container;
use AppBundle\Event\ContainerCreationEvent;
use AppBundle\Event\ContainerStateEvent;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use Doctrine\ORM\EntityManager;

class ContainerListener
{
    protected $em;
    protected $api;
    protected $stateApi;

    public function __construct(EntityManager $em, ContainerApi $api, ContainerStateApi $stateApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->stateApi = $stateApi;
    }

    /**
     * @param ContainerCreationEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdContainerCreationUpdate(ContainerCreationEvent $event){

        echo "START-CREATION : ContainerId ".$event->getContainerId()." \n";

        echo "CREATING CONTAINER... \n";

        $operationsResponse = $this->api->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->persist($container);
            $this->em->flush($container);
            return;
        }


        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());

        $container->setState('created');

        $this->em->persist($container);
        $this->em->flush($container);

        echo "FINISH-CREATION : ContainerId ".$event->getContainerId()."\n";
    }


    /**
     * @param ContainerStateEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdContainerStateUpdate(ContainerStateEvent $event)
    {
        echo "START-STATE-UPDATE: ContainerId ".$event->getContainerId()." \n";


        echo "UPDATING STATE... \n";

        $operationsResponse = $this->stateApi->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-STATE-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->persist($container);
            $this->em->flush($container);
            return;
        }

        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());

        $result = $this->stateApi->actual($event->getHost(), $container);


        $container->setState($result->body->metadata->status);

        $this->em->persist($container);
        $this->em->flush($container);

        echo "FINISH-STATE-UPDATE : ContainerId ".$event->getContainerId()."\n";
    }

}